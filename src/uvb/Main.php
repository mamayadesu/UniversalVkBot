<?php

declare(ticks = 1);

namespace uvb;

use Data\String\BackgroundColors;
use Data\String\ColoredString;
use Data\String\ForegroundColors;
use \Exception;
use HttpServer\Exceptions\ServerStartException;
use IO\Console;
use Scheduler\AsyncTask;
use \Throwable;
use \Application\Application;
use IO\FileDirectory;
use \Phar;
use \RecursiveIteratorIterator;
use Threading\SuperGlobalArray;
use uvb\Events\CommandPreProcessEvent;
use uvb\Events\InGroupUserAction\UserJoinGroupEvent;
use uvb\Events\InGroupUserAction\UserLeftGroupEvent;
use uvb\Events\Messages\BotJoinEvent;
use uvb\Events\Messages\NewConversationMessageEvent;
use uvb\Events\Messages\NewPrivateMessageEvent;
use uvb\Events\Messages\UserAddEvent;
use uvb\Events\Messages\UserJoinEvent;
use uvb\Events\Messages\UserKickEvent;
use uvb\Events\Messages\UserLeftEvent;
use uvb\Events\UnregisteredVkEvent;
use uvb\Handlers\InGroupUserAction;
use uvb\Handlers\SystemCommandsHandler;
use uvb\Handlers\Unregistered;
use uvb\Models\Attachments\AttachmentTypes;
use uvb\Models\Command;
use uvb\Models\Entity;
use uvb\Models\Geolocation;
use uvb\Models\Message;
use uvb\Models\User;
use uvb\Models\UserSex;
use uvb\Plugin\CommandManager;
use uvb\Plugin\Plugin;
use uvb\Plugin\PluginManager;
use uvb\Protection\AddressBlocker;
use uvb\Services\RamController;
use uvb\Services\UserCache;
use uvb\System\CrashHandler;
use uvb\System\SystemConfigResource;
use uvb\System\Update\Updater;
use uvb\Threads\CpuChecker;
use uvb\Utils\AttachmentParser;
use uvb\Utils\CpuUsage;
use VK\Client\VKApiClient;
use uvb\Handlers\NewMessage;
use uvb\Handlers\CommandHandler;
use \HttpServer\Server;
use \HttpServer\Request;
use \HttpServer\Response;
use Threading\Threaded;
use uvb\System\SystemConfig;

/**
 * @ignore
 */

final class Main
{
    private static bool $mainInitialized = false;
    private static Main $instance;

    private float $mt_start;
    private int $timestart = 0;
    public SuperGlobalArray $sga;

    /**
     * @var array<Threaded>
     */
    private array/*<Threaded>*/ $threads = [];
    public array $config;
    private int $cmdPid = -1;
    private string $cmdNextKey = "";
    private string $title = "";
    public ConsoleMessagesManager $consoleMessagesManager;
    public ConversationIds $conversationIds;
    public VKApiClient $api;
    public NewMessage $newMessage;
    public CommandHandler $commandHandler;
    public CommandManager $commandManager;
    public Unregistered $unregistered;
    public ?PluginManager $pluginManager = null;
    public UserCache $userCache;
    public ?Bot $bot = null;
    private static User $consoleUser;
    public SystemCommandsHandler $sch;
    public InGroupUserAction $inGroupUserAction;
    public ?Server $server = null;
    private SystemLogger $sl;
    private Logger $logger;
    public Updater $updater;
    public RamController $ramController;

    public function __construct(array $args)
    {
        self::$instance = $this;
        if (self::$mainInitialized)
        {
            throw new Exception("You cannot initialize the main class.");
        }

        // запускаем свой контроллер потребления ОЗУ
        $this->ramController = new RamController($this);
        if (!extension_loaded("curl"))
        {
            Console::WriteLine("UniversalVkBot requires cURL extension!", ForegroundColors::RED);
            exit(0);
        }

        $this->sga = SuperGlobalArray::GetInstance();
        new CpuUsage();
        if (!IS_WINDOWS)
        {
            $this->sga->Set(["cpu_usage"], 0);

            // запускаем параллельный класс, который будет чекать потребление ЦПУ
            $this->threads[] = CpuChecker::Run([], $this);
        }

        gc_enable();
        self::$mainInitialized = true;
        $this->mt_start = microtime(true);
        $this->sga->Set(["exitCode"], 0);

        $pargs = Application::ParseArguments($args, "--");
        $colorsEnabled = true;
        if (isset($pargs["arguments"]["colors"]) && ($pargs["arguments"]["colors"] == "0" || $pargs["arguments"]["colors"] == "false" || $pargs["arguments"]["colors"] == "no"))
        {
            $colorsEnabled = false;
        }

        // служба кэширования пользователей
        $this->userCache = new UserCache($this);

        // логгер-мастер
        $this->sl = new SystemLogger($colorsEnabled); 

        // логгер бота
        $this->logger = new Logger("", $this->sl); 

        $this->updater = new Updater($this, $this->logger, $this->sl);

        $this->bot = new Bot($this, $this->logger, new Logger("CONSOLE", $this->sl), $this->sl);
        new AsyncTask($this, 50, false, [$this, "UpdateTitle"]);
        // устанавливаем свой обработчик Ctrl+C
        if (IS_WINDOWS)
        {
            sapi_windows_set_ctrl_handler(function(int $event) : void
            {
                if ($event == PHP_WINDOWS_EVENT_CTRL_C)
                    $this->CtrlHandler();
            }, true);
        }
        else
        {
            pcntl_signal(SIGINT, function() : void
            {
                $this->CtrlHandler();
            });
        }
        cmm::$bot = $this->bot;
        $this->consoleMessagesManager = new ConsoleMessagesManager($this);

        cmm::l("main.starting");
        cmm::l("main.programversion", [Application::GetVersion()]);
        cmm::l("main.apiversion", [APIVersions::Last()]);

        // загружаем config.json
        $this->LoadData();
        if (!in_array(0, $this->config["admins"]))
        {
            $this->config["admins"][] = 0;
        }
        SystemConfigResource::Init($this->config);

        /**
         * Стартуем HTTP-сервер. После его старта продолжаем загружать всё остальное
         *
         * Сделано это для того, чтобы, в случае ошибки запуска HTTP-сервера, то бот сразу завершил процесс,
         * а не после того, как уже всё загрузилось
         */
        cmm::l("main.startinghttpserver"); 
        $this->server = new Server(SystemConfig::Get("server_addr"), SystemConfig::Get("server_port")); 
        $this->server->On("start", function(Server $server) { $this->Server_Start($server);}); 
        $this->server->On("shutdown", function(Server $server) { $this->Server_Stop($server);}); 
        $this->server->On("request", function(Request $request, Response $response)
        {
            // в случае какой-то необработанно ошибки, кидаем 500 ошибку,
            // чтобы клиент не ждал бесконечное количество лет
            try
            {
                $this->Server_Request($request, $response);
            }
            catch (Throwable $e)
            {
                if (!$response->IsClosed())
                {
                    $response->Status(500);
                    $response->End("Internal Server Error");
                }
            }
        }); 

        /**
         * Пробуем запустить HTTP-сервер
         *
         * В случае неудачи - завершаем работу бота
         */
        $this->server->DataReadTimeout = 2;
        try
        {
            $this->server->Start(true);
        }
        catch (ServerStartException $e)
        {
            $this->bot->GetLogger()->Critical("*******************************");
            cmm::c("bot.failedtobindport", [SystemConfig::Get("server_addr"), SystemConfig::Get("server_port")]);
            $this->bot->GetLogger()->Critical("*******************************");
            sleep(1);
            cmm::l("bot.shuttingdown");
            sleep(1);
            exit(0);
        }
        catch (Throwable $e)
        {
            $this->logger->Critical("Unhandled " . get_class($e) . " \"" . $e->getMessage() . "\" in " . $e->getFile() . " on line " . $e->getLine());
            CrashHandler::Handle($e);
            $this->sga->Set(["exitCode"], 255);
            $this->bot->Shutdown();
        }
        $this->StartCommandHandler();
    }

    public function CtrlHandler() : void
    {
        // Первое нажатие - завершаем работу бота
        if (!$this->bot->IsShuttingDown())
        {
            if (SystemConfig::Get("restart_on_ctrl_c"))
            {
                $this->bot->Reboot();
            }
            else
            {
                $this->bot->Shutdown();
            }
        }
        // Второе нажатие - "убиваем" процесс сервера
        else
        {
            exit(SystemConfig::Get("restart_on_ctrl_c") ? 2 : 0);
        }
    }

    public function StartCommandHandler() : void
    {
        while (true)
        {
            $input = Console::ReadLine();
            $this->bot->DispatchPrivateCommand(User::Get(0), $input);
        }
    }

    public function InstallUpdate() : void
    {
        $p = null;
        $pathToPackage = Application::GetExecutableDirectory() . "update" . DIRECTORY_SEPARATOR . "update.phar";
        $fullPath = "";
        $strlen = 0;
        $cstrlen = 0;
        $files = [];
        $classes = [];
        $class = "";
        try
        {
            $p = new Phar($pathToPackage, 0);
            foreach (new RecursiveIteratorIterator($p) as $file)
            {
                $fullPath = $file->getPathName();
                $fullPath = str_replace("\\", "/", $fullPath);
                $strlen = strlen($fullPath);
                if (strtolower(substr($fullPath, $strlen - 4, 4)) != ".php")
                {
                    continue;
                }
                $class = $fullPath;
                $class = str_replace("phar://" . str_replace("\\", "/", $pathToPackage) . "/", "", $class);
                $class = str_replace("/", "\\", $class);
                $cstrlen = strlen($class);
                $class = substr($class, 0, $cstrlen - 4);
                $cstrlen = strlen($class);
                if ($class == "autoload" || $class == "thread" || ($cstrlen > 11 && substr($class, 0, 11) == "__xrefcore\\"))
                {
                    continue;
                }

                $classes[] = $class;
                $files[] = $fullPath;
            }
        }
        catch (Exception $e)
        {
            return;
        }
        $_file = "";
        $_class = "";
        for ($i = 0; $i < count($classes); $i++)
        {
            if ($classes[$i] == "uvb\\System\\Update\\Packages\\" . $this->updater->GetCurrentPackage())
            {
                $_file = $files[$i];
                $_class = $classes[$i];
                break;
            }
        }
        if (!class_exists($_class))
        {
            require_once $_file;
        }
        $this->updater->Log("Preparing to install package " . $this->updater->GetCurrentPackage() . "...");
        forward_static_call(array($_class, "PreUpdateStart"), $this);
        FileDirectory::Delete(Application::GetExecutableFileName());

        rename(Application::GetExecutableDirectory() . "update" . DIRECTORY_SEPARATOR . "update.phar", Application::GetExecutableFileName());

        $this->updater->Log("UniversalVkBot will be restarted right now");
    }

    public function KillThreads() : void
    {
        foreach ($this->threads as $thread)
        {if(!$thread instanceof Threaded)continue;
            $thread->Kill();
        }
    }

    private function Server_Stop(Server $server) : void
    {
        cmm::l("http.shuttingdown");
        if ($this->sga->Get(["exitCode"]) != 3)
        {
            $this->sl->CloseLogger();
        }
        $exitCode = $this->sga->Get(["exitCode"]);
        if (!IS_WINDOWS) FileDirectory::Delete(Application::GetExecutableDirectory() . "server.lock");
        exit($exitCode);
    }

    // продолжаем запуск бота
    private function Server_Start(Server $server) : void
    {
        cmm::l("http.addr", [SystemConfig::Get("server_addr"), SystemConfig::Get("server_port")]);
        cmm::l("http.uri", [SystemConfig::Get("server_addr"), SystemConfig::Get("server_port"), SystemConfig::Get("requests_uri")]);

        $this->api = new VKApiClient();
        $this->newMessage = new NewMessage($this); 
        $this->commandHandler = new CommandHandler($this); 
        $this->unregistered = new Unregistered($this); 
        $this->pluginManager = new PluginManager($this, $this->sl); 
        $this->commandManager = new CommandManager($this); 

        // запускаем псевдо-плагин, обрабатывающий системные команды бота
        $this->sch = new SystemCommandsHandler("System", "", "", [], $this->bot, $this->logger);
        $this->inGroupUserAction = new InGroupUserAction($this); 
        $this->sch->SetCmdMgr($this->commandManager);
        $this->sch->SetPlgMgr($this->pluginManager);
        $this->sch->SetMain($this);

        // регистрируем базовые команды бота в системе
        $this->sch->RegisterSystemCommands();
        $this->conversationIds = new ConversationIds();
        ConversationIdsResource::$conversationIds = $this->conversationIds;
        cmm::l("bot.loadingusers");
        $this->userCache->Load(true);

        self::$consoleUser = new User(0, ["nom" => "CONSOLE"], ["nom" => ""], UserSex::MALE, "", "", "", "", "");

        // Загружаем плагины из папки "/plugins"
        cmm::l("main.loadingplugins");
        $this->pluginManager->LoadPlugins();
        $plugins = $this->pluginManager->GetPlugins();
        $pluginsName = [];
        foreach ($plugins as $plugin)
        {if(!$plugin instanceof Plugin)continue;
            $pluginsName[] = $plugin->GetPluginName();
        }
        cmm::l("main.pluginsloaded", [count($plugins), implode(", ", $pluginsName)]);

        if (!$this->updater->UpdateWasFinished())
        {
            $this->updater->UpdateCommand();
        }

        // устанавливаем время запуска
        $this->timestart = time(); 

        // подсчитываем за сколько секунд запустился UniversalVkBot
        $t = (microtime(true) - $this->mt_start);
        $t = $t * 10000;
        $t = round($t);
        $t = $t / 10000;
        cmm::l("main.started", [$t]);
        if ($this->updater->UpdateWasFinished())
        {
            $this->bot->GetLogger()->Log(ColoredString::Get(cmm::g("system.update.updated", [Application::GetVersion()]), ForegroundColors::DARK_GREEN, BackgroundColors::BLACK));
        }
    }

    public function GetUptime() : int
    {
        return time() - $this->timestart;
    }

    /**
     * @return array<string, string>
     */
    public function GetParsedUptime() : array
    {
        $c = time() - $this->timestart;
        $days1 = floor(($c) / 86400);
        $hours1 = floor(($c) / 60 / 60 - $days1 * 24);
        $minutes1 = floor(($c) / 60 - floor(($c) / 60 / 60) * 60);
        $seconds1 = ((($c) - $hours1 * 3600 - $days1 * 86400 - $minutes1 * 60));

        $days = "$days1";
        $hours = "$hours1";
        $minutes = "$minutes1";
        $seconds = "$seconds1";

        if (strlen($minutes) < 2)
        {
            $minutes = "0" . $minutes;
        }
        if (strlen($seconds) < 2)
        {
            $seconds = "0" . $seconds;
        }
        return array(
            "d" => $days,
            "h" => $hours,
            "m" => $minutes,
            "s" => $seconds
        );
    }

    public function UpdateTitle() : void
    {
        $title = $this->timestart > 0 ? $this->UpdateTitleStarted() : $this->UpdateTitleNotStarted();
        if ($this->title != $title)
        {
            $this->title = $title;
            Application::SetTitle($title);
        }
    }

    private function UpdateTitleNotStarted() : string
    {
        $cpu = $this->bot->GetCpuUsage();
        $title = "UniversalVkBot | RAM usage: " . Main::GetFormattedMemory($this->ramController->GetUsage()) . " / " . Main::GetFormattedMemory($this->ramController->GetAllocatedMemory()) . " (" . $this->ramController->GetUsagePercent() . "%) | Users cached: " . count($this->userCache->GetUsers());
        if (!IS_WINDOWS) $title .= " | CPU " . $cpu . "%";
        return $title;
    }

    private function UpdateTitleStarted() : string
    {
        $puptime = $this->GetParsedUptime();
        $uptime_text = cmm::g("main.uptime", [$puptime["d"], $puptime["h"], $puptime["m"], $puptime["s"]]);

        $title = "";
        $cpu = $this->bot->GetCpuUsage();
        $title = "UVB | Uptime: " . $uptime_text . " | RAM usage: " . Main::GetFormattedMemory($this->ramController->GetUsage()) . " / " . Main::GetFormattedMemory($this->ramController->GetAllocatedMemory()) . " (" . $this->ramController->GetUsagePercent() . "%) | Users cached: " . count($this->userCache->GetUsers());
        if (!IS_WINDOWS) $title .= " | CPU " . $cpu . "%";
        return $title;
    }

    public static function GetFormattedMemory(int $mu) : string
    {
        $memory_usage = "";
        if ($mu == -1)
        {
            return "∞";
        }
        if ($mu < 1024)
        {
            $memory_usage = $mu . " B";
        }
        else if ($mu < (1024 * 1024))
        {
            $memory_usage = round($mu / 1024, 2) . " KB";
        }
        else if ($mu < (1024 * 1024 * 1024))
        {
            $memory_usage = round($mu / 1024 / 1024, 2) . " MB";
        }
        else if ($mu < (1024 * 1024 * 1024 * 1024))
        {
            $memory_usage = round($mu / 1024 / 1024 / 1024, 2) . " GB";
        }
        return $memory_usage;
    }

    public function GetStatusAsString() : string
    {
        $uptime = $this->GetUptime();
        $puptime = $this->GetParsedUptime();
        $uptime_text = cmm::g("main.uptime", [$puptime["d"], $puptime["h"], $puptime["m"], $puptime["s"]]);
        $pid = -1;
        if (function_exists("getmypid"))
        {
            $pid = getmypid();
        }
        $log = cmm::g("main.status", [SystemConfig::Get("server_addr"), SystemConfig::Get("server_port"), $uptime, $uptime_text, self::GetFormattedMemory($this->ramController->GetUsage()), self::GetFormattedMemory($this->ramController->GetAllocatedMemory()), $this->ramController->GetUsagePercent(), count($this->userCache->GetUsers()), ($pid != -1 ? $pid : "?"), $this->bot->GetCpuUsage()]);
        return $log;
    }

    public static function GetConsoleAsUser() : User
    {
        return self::$consoleUser;
    }

    private function Server_Request(Request $request, Response $response) : void
    {
        if ($this->bot->IsShuttingDown())
        {
            $response->Header("Content-Type", "text/plain");
            $response->End("Bot is shutting down");
            return;
        }
        $data = json_decode($request->GetRawContent(), true);
        $AllowedAddresses = ["127.0.0.1", SystemConfig::Get("server_addr"), "192.168.1.1", "192.168.100.1", "192.168.0.1"];
        if (!in_array($request->RemoteAddress, $AllowedAddresses))
        {
            if ($this->bot->GetAddressBlocker()->IsBanned($request->RemoteAddress))
            {
                $response->Status(308);
                $response->Header("Location", "https://google.ru");
                $response->End("");
                return;
            }
            $uriCheck1 = AddressBlocker::UriHasThreat($request->RequestUri);
            $uriCheck2 = AddressBlocker::UriHasThreat($request->GetRawContent());
            if ($uriCheck1 || ($uriCheck2 && ($data == null || !isset($data["secret"]) || !isset($data["group_id"]))))
            {
                $response->Status(308);
                $response->Header("Location", "https://google.ru");
                $response->End("");
                $this->bot->GetAddressBlocker()->Ban($request->RemoteAddress);
                $this->bot->GetAddressBlocker()->Save();
                $uhtmsg = "";
                if ($uriCheck1)
                {
                    $uhtmsg = "request.threatdetected";
                }
                else
                {
                    $uhtmsg = "request.bodythreatdetected";
                }
                cmm::w("request.threatdetected", [$request->RemoteAddress]);
                $this->bot->GetLogger()->Warn($request->RequestUri);
                return;
            }
        }
        $input = "";
        if ($request->RequestUri == "/cmd" && in_array($request->RemoteAddress, $AllowedAddresses))
        {
            if ($data["first"] && $this->cmdPid == -1)
            {
                $this->cmdPid = $data["pid"];
                $this->cmdNextKey = md5(time() . " " . rand(-100, 100) . md5(rand(-100, 100) . SystemConfig::Get("server_addr")));
                $response->End($this->cmdNextKey);
                return;
            }
            else if (!$data["first"] && $this->cmdPid != $data["pid"])
            {
                $response->End("fail");
                return;
            }
            else if (!$data["first"] && $data["key"] != $this->cmdNextKey)
            {
                $response->End("fail");
                return;
            }
            else if (!$data["first"] && $data["key"] == $this->cmdNextKey && $data["pid"] == $this->cmdPid)
            {
                $this->cmdNextKey = md5(time() . " " . rand(-100, 100) . md5(rand(-100, 100) . SystemConfig::Get("server_addr")));
                $input1 = explode(' ', $data["cmd"]);
                $commandName = $input1[0];
                array_shift($input1);
                $command = new Command($commandName, $input1, self::GetConsoleAsUser(), 0);
                $_event = new CommandPreProcessEvent($command, true, 0);
                $this->newMessage->OnCommandPreProcess($_event);
                if (!$_event->IsCancelled())
                {
                    $this->commandHandler->OnCommand($command);
                }
                $response->End($this->cmdNextKey);
                return;
            }
            else
            {
                $response->End("fail");
                return;
            }
        }
        if ($input != "")
        {
            $input1 = explode(' ', $input);
            $commandName = $input1[0];
            array_shift($input1);
            $command = new Command($commandName, $input1, self::GetConsoleAsUser(), 0);
            $_event = new CommandPreProcessEvent($command, true, 0);
            $this->newMessage->OnCommandPreProcess($_event);
            if (!$_event->IsCancelled())
            {
                $this->commandHandler->OnCommand($command);
            }
        }
        $response->Header("Content-Type", "text/plain");

        if ($request->RequestUri != "/" . SystemConfig::Get("requests_uri"))
        {
            $this->bot->GetLogger()->Error("*******************");
            cmm::e("request.wronguri", [$request->RequestUri]);
            $this->bot->GetLogger()->Error($request->GetRawContent());
            $this->bot->GetLogger()->Error($request->RemoteAddress);
            $response->End("Wrong URI");
            $this->bot->GetLogger()->Error("*******************");
            return;
        }

        if ($data == null)
        {
            cmm::e("request.incorrectdata");
            $this->bot->GetLogger()->Error($request->GetRawContent());
            $response->End("Bad request");
            return;
        }
        if (!isset($data["group_id"]) || $data["group_id"] != SystemConfig::Get("group_id"))
        {
            cmm::w("request.invalidgroup");
            $response->End("Invalid group");
            return;
        }
        if (!isset($data["secret"]) || $data["secret"] != SystemConfig::Get("secret_key"))
        {
            cmm::e("request.wrongsecretkey");
            $response->End("Invalid secret key");
            return;
        }
        if ($data["type"] == "confirmation")
        {
            $this->bot->GetLogger()->Log(ColoredString::Get(cmm::g("request.confirm"), ForegroundColors::PURPLE));
            $confirmResponse = $this->bot->GetConfirmResponse();
            $response->End($confirmResponse);
            if ($confirmResponse == "")
            {
                cmm::e("command.confirmresponse.get.empty");
                return;
            }
            $this->bot->GetLogger()->Log(ColoredString::Get(cmm::g("request.confirmed"), ForegroundColors::GREEN));
            return;
        }
        else
        {
            $obj = array();
            $event = null;
            $response->End("ok");
            switch ($data["type"])
            {
                case "message_new":
                    $obj = $data["object"];
                    $date = $obj["date"];
                    $fromId = $obj["from_id"];
                    $peer_id = $obj["peer_id"];
                    $conversation_message_id = $obj["conversation_message_id"];
                    $from = User::Get($fromId);
                    $text = $obj["text"];
                    $attachments1 = $obj["attachments"];
                    $attachments = [];
                    $attachment = null;
                    $geolocation = null;

                    /*
                     * #dev
                     */
                    $myvar = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $myfile = fopen(Application::GetExecutableDirectory() . "test.json", "w");
                    fwrite($myfile, $myvar);
                    fclose($myfile);
                    /*
                     * ################
                     */

                    foreach ($attachments1 as $attachment1)
                    {
                        $attachment = AttachmentParser::Parse($attachment1);
                        if ($attachment != null && $attachment->GetMediaType() != "unknown")
                        {
                            $attachments[] = $attachment;
                        }
                    }

                    if (isset($obj["geo"]))
                    {
                        $geolocation = Geolocation::FromVkArray($obj["geo"]);
                    }

                    $msgid = 0;
                    if (isset($obj["action"]))
                    {
                        break;
                    }

                    if (isset($obj["action"]))
                    {
                        if ($obj["action"]["type"] == "chat_invite_user")
                        {
                            if (-$obj["action"]["member_id"] == SystemConfig::Get("group_id"))
                            {
                                $event = new BotJoinEvent($from, $peer_id);
                                $this->newMessage->OnBotJoin($event);
                            }
                            else if ($obj["action"]["member_id"] > 0 && $obj["action"]["member_id"] != $fromId)
                            {
                                $event = new UserAddEvent($from, User::Get($obj["action"]["member_id"]), $peer_id);
                                $this->newMessage->OnUserAdd($event);
                            }
                            else if ($obj["action"]["member_id"] == $fromId)
                            {
                                $event = new UserJoinEvent($from, $peer_id);
                                $this->newMessage->OnUserJoin($event);
                            }
                        }
                        else if ($obj["action"]["type"] == "chat_kick_user")
                        {
                            if ($obj["action"]["member_id"] > 0 && -$fromId != SystemConfig::Get("group_id"))
                            {
                                if ($obj["action"]["member_id"] != $fromId)
                                {
                                    $event = new UserKickEvent($from, User::Get($obj["action"]["member_id"]), $peer_id);
                                    $this->newMessage->OnUserKick($event);
                                }
                                else
                                {
                                    $event = new UserLeftEvent($from, $peer_id);
                                    $this->newMessage->OnUserLeft($event);
                                }
                            }
                        }
                        break;
                    }

                    try
                    {
                        $inboxMsg = new Message($msgid, $date, $from, $text, $peer_id, $attachments, $conversation_message_id, $geolocation);
                    }
                    catch (\Exception $e)
                    {
                        $this->bot->GetLogger()->Critical($e->getMessage());
                        $response->End("ok");
                        return;
                    }

                    if ($inboxMsg->IsPrivate())
                    {
                        if (substr($text, 0, 1) == "/")
                        {
                            $rawcmd = substr($text, 1, strlen($text) - 1);
                            $arrcmd = explode(' ', $rawcmd);
                            $cmdname = $arrcmd[0];
                            array_shift($arrcmd);
                            $cmd = new Command($cmdname, $arrcmd, $from, 0);
                            $event = new CommandPreProcessEvent($cmd, true, $peer_id);
                            $this->newMessage->OnCommandPreProcess($event);
                            if (!$event->IsCancelled())
                            {
                                $this->commandHandler->OnCommand($cmd);
                            }
                        }
                        else
                        {
                            $event = new NewPrivateMessageEvent($inboxMsg, $request->GetRawContent());
                            $this->newMessage->OnNewPrivateMessage($event);
                        }
                    }
                    else
                    {
                        if (substr($text, 0, 1) == "/")
                        {
                            $rawcmd = substr($text, 1, strlen($text) - 1);
                            $arrcmd = explode(' ', $rawcmd);
                            $cmdname = $arrcmd[0];
                            array_shift($arrcmd);
                            $cmd = new Command($cmdname, $arrcmd, $from, $peer_id);
                            $event = new CommandPreProcessEvent($cmd, false, $peer_id);
                            $this->newMessage->OnCommandPreProcess($event);
                            if (!$event->IsCancelled())
                            {
                                $this->commandHandler->OnConversationCommand($cmd, $peer_id);
                            }
                        }
                        else
                        {
                            $event = new NewConversationMessageEvent($inboxMsg, $peer_id, $request->GetRawContent());
                            $this->newMessage->OnNewConversationMessage($event);
                        }
                    }
                    break;

                case "group_join":
                    $obj = $data["object"];
                    $user = User::Get($obj["user_id"]);
                    $__join = false;
                    $__request = false;
                    $__approved = false;
                    switch ($obj["join_type"])
                    {
                        case "join":
                            $__join = true;
                            break;

                        case "request":
                            $__request = true;
                            break;

                        case "approved":
                            $__approved = true;
                            break;
                    }
                    $event = new UserJoinGroupEvent($user, $__join, $__request, $__approved);
                    $this->inGroupUserAction->OnUserJoinGroup($event);
                    break;

                case "group_leave":
                    $obj = $data["object"];
                    $user = User::Get($obj["user_id"]);
                    $leftBySelf = $obj["self"] == 1;
                    $event = new UserLeftGroupEvent($user, $leftBySelf);
                    $this->inGroupUserAction->OnUserLeftGroup($event);
                    break;

                default:
                    $event = new UnregisteredVkEvent($request->GetRawContent(), json_decode($request->GetRawContent(), true), $data["type"]);
                    $this->unregistered->OnUnregistered($event);
                    break;
            }
        }
    }

    public function LoadData() : void
    {
        $path = Application::GetExecutableDirectory() . "config.json";
        $dcfg = $this->GetDefaultConfig();
        $this->config = $dcfg;
        $somethingWrong = false;
        if (!file_exists($path))
        {
            cmm::w("config.notfound");
            $f = fopen($path, "w");
            fwrite($f, json_encode($dcfg, JSON_PRETTY_PRINT));
            fclose($f);
            $somethingWrong = true;
        }
        else
        {
            $data = file_get_contents($path);
            $this->config = json_decode($data, true);
            foreach ($dcfg as $key => $value)
            {
                if (!isset($this->config[$key]))
                {
                    cmm::w("config.parameternotfound", [$key]);
                    $this->config[$key] = $value;
                }
            }
            $this->ramController->SetAllocatedMemory(RamController::ParseMemory($this->config["memory_limit"]));
            $f = fopen($path, "w");
            fwrite($f, json_encode($this->config, JSON_PRETTY_PRINT));
            fclose($f);
        }
        if (strlen($this->config["access_token"]) != 85)
        {
            cmm::e("config.accesstoken");
            $somethingWrong = true;
        }
        if (intval($this->config["group_id"]) < 1)
        {
            cmm::e("config.groupid");
            $somethingWrong = true;
        }
        if (strlen($this->config["main_admin_access_token"]) != 85)
        {
            cmm::e("config.mainadminaccesstoken");
            $somethingWrong = true;
        }
        if ($somethingWrong)
        {
            cmm::w("config.somethingwrong");
            cmm::l("bot.shuttingdown");
            $this->sl->CloseLogger();
            exit;
        }
    }

    public function GetDefaultConfig() : array
    {
        return array
        (
            "access_token" => "",
            "main_admin_access_token" => "",
            "group_id" => -1,
            "server_addr" => "0.0.0.0",
            "server_port" => 80,
            "requests_uri" => "request",
            "secret_key" => "",
            "admins" => [

            ],
            "memory_limit" => "512M",
            "restart_on_ctrl_c" => true
        );
    }
}