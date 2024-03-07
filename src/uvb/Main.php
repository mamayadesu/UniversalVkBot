<?php
declare(ticks = 1);

namespace uvb;

use Data\String\BackgroundColors;
use Data\String\ColoredString;
use Data\String\ForegroundColors;
use IO\Console\Exceptions\ReadInterruptedException;
use \Exception;
use HttpServer\Exceptions\ServerStartException;
use HttpServer\ServerEvents;
use IO\Console;
use Scheduler\AsyncTask;
use Scheduler\NoAsyncTaskParameters;
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
use uvb\Events\Messages\BotLeftEvent;
use uvb\Events\Messages\NewConversationMessageEvent;
use uvb\Events\Messages\NewPrivateMessageEvent;
use uvb\Events\Messages\UserAddEvent;
use uvb\Events\Messages\UserJoinEvent;
use uvb\Events\Messages\UserKickEvent;
use uvb\Events\Messages\UserLeftEvent;
use uvb\Events\ServerRequestEvent;
use uvb\Events\UnregisteredVkEvent;
use uvb\Events\Wall\NewCommentEvent;
use uvb\Events\Wall\NewPostEvent;
use uvb\Handlers\GroupWall;
use uvb\Handlers\InGroupUserAction;
use uvb\Handlers\SystemCommandsHandler;
use uvb\Handlers\Common;
use uvb\Models\Attachments\AttachmentTypes;
use uvb\Models\Command;
use uvb\Models\Conversation;
use uvb\Models\Entity;
use uvb\Models\Geolocation;
use uvb\Models\Group;
use uvb\Models\Message;
use uvb\Models\User;
use uvb\Models\UserSex;
use uvb\Models\Wall\Comment;
use uvb\Models\Wall\Post;
use uvb\Plugin\CommandManager;
use uvb\Plugin\Plugin;
use uvb\Plugin\PluginManager;
use uvb\Services\RamController;
use uvb\Services\ResourcesWatcher;
use uvb\Services\UserCache;
use uvb\System\CrashHandler;
use uvb\System\ServerQueueTask;
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

    /** @var ?ServerQueueTask[] */
    private array $serverQueue = [];

    /** @var Threaded[] */
    private array $threads = [];
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
    public Common $common;
    public ?PluginManager $pluginManager = null;
    public UserCache $userCache;
    public ?Bot $bot = null;
    private static User $consoleUser;
    public SystemCommandsHandler $sch;
    public InGroupUserAction $inGroupUserAction;
    public GroupWall $groupWall;
    public ?Server $server = null;
    private SystemLogger $sl;
    private Logger $logger;
    public Updater $updater;
    public RamController $ramController;
    public ?ResourcesWatcher $resourcesWatcher = null;
    public int $exitCode;

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

        if (!IS_WINDOWS)
        {
            new CpuUsage();
            $this->sga = SuperGlobalArray::GetInstance();
            $this->sga->Set(["cpu_usage"], 0);

            // запускаем параллельный класс, который будет чекать потребление ЦПУ
            $this->threads[] = CpuChecker::Run([], $this);
        }

        gc_enable();
        self::$mainInitialized = true;
        $this->mt_start = microtime(true);
        $this->exitCode = 0;

        $pargs = Application::ParseArguments($args, "--");
        $colorsEnabled = true;
        if (isset($pargs["arguments"]["colors"]) && ($pargs["arguments"]["colors"] == "0" || $pargs["arguments"]["colors"] == "false" || $pargs["arguments"]["colors"] == "no"))
        {
            $colorsEnabled = false;
        }

        $restart_supported = false;

        if ((isset($pargs["arguments"]["restartsupported"]) && in_array($pargs["arguments"]["restartsupported"], ["true", "1", "yes"])) || isset($pargs["uninitialized_keys"]["restartsupported"]))
        {
            $restart_supported = true;
        }

        // служба кэширования пользователей
        $this->userCache = new UserCache($this);

        // логгер-мастер
        $this->sl = new SystemLogger($colorsEnabled);

        // логгер бота
        $this->logger = new Logger("", $this->sl);

        $this->updater = new Updater($this, $this->logger, $this->sl);

        $this->bot = new Bot($this, $this->logger, new Logger("CONSOLE", $this->sl), $this->sl, $restart_supported);
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
        SystemConfigResource::Init($this->config);

        /**
         * Стартуем HTTP-сервер. После его старта продолжаем загружать всё остальное
         *
         * Сделано это для того, чтобы, в случае ошибки запуска HTTP-сервера, то бот сразу завершил процесс,
         * а не после того, как уже всё загрузилось
         */
        cmm::l("main.startinghttpserver"); 
        $this->server = new Server(SystemConfig::Get("server_addr"), SystemConfig::Get("server_port")); 
        $this->server->On(ServerEvents::Start, function(Server $server) : void
        {
            $this->Server_Start($server);
        });

        $this->server->On(ServerEvents::Shutdown, function(Server $server) : void
        {
            $this->Server_Stop($server);
        });

        $this->server->On(ServerEvents::Request, function(Request $request, Response $response, Server $server) : void
        {
            $this->serverQueue[] = new ServerQueueTask($server, $request, $response);
            Console::InterruptRead();
        });

        $this->server->On(ServerEvents::Throwable, function(Request $request, Response $response, Throwable $throwable, Server $server) : void
        {
            if (!$response->IsClosed())
            {
                $response->Status(500);
                $response->End("Internal Server Error");
            }
            Bot::GetInstance()->GetLogger()->Error("Failed to proceed HTTP-request. Uncaught " . get_class($throwable) . ". '" . $throwable->getMessage() . "' in " . $throwable->getFile() . " on line " . $throwable->getLine());
        });

        /**
         * Запускаем HTTP-сервер асинхронно, чтобы можно было использовать и асинхронные задачи, и обработчик команд
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
            Bot::GetInstance()->GetLogger()->Critical($e->getMessage());
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
            $this->exitCode = 255;
            $this->bot->Shutdown();
        }

        /**
         * Запускаем обработчик ввода команд.
         */
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
            try
            {
                $input = Console::ReadLine();
            }
            catch (ReadInterruptedException $e)
            {
                // foreach здесь не делать. Записи в массиве могут появляться асинхронно
                while (count($this->serverQueue) > 0)
                {
                    $queueTask = $this->serverQueue[0];
                    $this->Server_Request($queueTask->request, $queueTask->response, $queueTask->server);
                    $this->serverQueue[0] = null;
                    unset($this->serverQueue[0]);
                    $this->serverQueue = array_values($this->serverQueue);
                }
                continue;
            }

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
        if ($this->exitCode != 3)
        {
            $this->sl->CloseLogger();
        }
        exit($this->exitCode);
    }

    // продолжаем запуск бота
    private function Server_Start(Server $server) : void
    {
        cmm::l("http.addr", [SystemConfig::Get("server_addr"), SystemConfig::Get("server_port")]);
        cmm::l("http.uri", [SystemConfig::Get("server_addr"), SystemConfig::Get("server_port"), Bot::REQUEST_URI]);
        $this->resourcesWatcher = new ResourcesWatcher();
        $this->api = new VKApiClient();
        $this->newMessage = new NewMessage($this); 
        $this->commandHandler = new CommandHandler($this);
        $this->groupWall = new GroupWall($this);
        $this->common = new Common($this);
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

    public function UpdateTitle(AsyncTask $task, NoAsyncTaskParameters $params) : void
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
        $cpu = $this->bot->GetCpuUsageAsString();
        $title = "UVB | RAM: " . Main::GetFormattedMemory($this->ramController->GetUsage()) . " / " . Main::GetFormattedMemory($this->ramController->GetAllocatedMemory()) . " (" . $this->ramController->GetUsagePercent() . "%) | Users cached: " . count($this->userCache->GetUsers());
        if (!IS_WINDOWS) $title .= " | CPU " . $cpu . "%";
        return $title;
    }

    private function UpdateTitleStarted() : string
    {
        $puptime = $this->GetParsedUptime();
        $uptime_text = cmm::g("main.uptime", [$puptime["d"], $puptime["h"], $puptime["m"], $puptime["s"]]);

        $title = "";
        $cpu = $this->bot->GetCpuUsageAsString();
        $title = "UVB | Up: " . $uptime_text . " | RAM: " . Main::GetFormattedMemory($this->ramController->GetUsage()) . " / " . Main::GetFormattedMemory($this->ramController->GetAllocatedMemory()) . " (" . $this->ramController->GetUsagePercent() . "%) | Users cached: " . count($this->userCache->GetUsers());
        if (!IS_WINDOWS) $title .= " | CPU " . $cpu . "%";
        return $title;
    }

    public static function GetFormattedMemory(int $mu) : string
    {
        $memory_usage = "";
        if ($mu == -1)
        {
            return "INF";
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

    private function Server_Request(Request $request, Response $response, Server $server) : void
    {
        $response->DataSendTimeout = 2;
        if ($this->bot->IsShuttingDown())
        {
            $response->ClientNonBlockMode = true;
            $response->Header("Content-Type", "text/plain");
            $response->End("Bot is shutting down");
            return;
        }
        $data = json_decode($request->GetRawContent(), true);
        $secret_keys = SystemConfig::Get("groups_to_secret_keys");
        $is_valid_secret_key = !(!isset($data["secret"]) || !isset($data["group_id"]) || !isset($secret_keys["club" . $data["group_id"]]) || $secret_keys["club" . $data["group_id"]] != $data["secret"]);
        $group = null;
        if ($is_valid_secret_key && isset($data["group_id"]))
        {
            $group = Group::Get($data["group_id"]);
        }

        $request_event = new ServerRequestEvent($group, $request, $response, $is_valid_secret_key);
        $this->common->OnServerRequest($request_event);
        if ($request_event->IsDefaultRequestHandlerPrevented())
        {
            return;
        }
        $response->ClientNonBlockMode = true;
        $response->Header("Content-Type", "text/plain");
        if ($request->RequestUri != "/" . Bot::REQUEST_URI)
        {
            $response->End("Wrong URI");
            return;
        }

        if ($data == null)
        {
            $response->End("Bad request");
            return;
        }

        if (!$is_valid_secret_key)
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
            if (!$response->IsClosed())
            {
                $response->End("ok");
            }

            switch ($data["type"])
            {
                case "message_new":
                    $obj = $data["object"]["message"];
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
                        if ($obj["action"]["type"] == "chat_invite_user")
                        {
                            $conversation = Conversation::Get($peer_id, $group);
                            if ($obj["action"]["member_id"] < 0)
                            {
                                $event = new BotJoinEvent($group, $from, Group::Get($obj["action"]["member_id"]), $conversation);
                                $this->newMessage->OnBotJoin($event);
                            }
                            else if ($obj["action"]["member_id"] > 0 && $obj["action"]["member_id"] != $fromId)
                            {
                                $event = new UserAddEvent($group, $from, User::Get($obj["action"]["member_id"]), $conversation);
                                $this->newMessage->OnUserAdd($event);
                            }
                            else if ($obj["action"]["member_id"] == $fromId)
                            {
                                $event = new UserJoinEvent($group, $from, $conversation);
                                $this->newMessage->OnUserJoin($event);
                            }
                        }
                        else if ($obj["action"]["type"] == "chat_kick_user")
                        {
                            $conversation = Conversation::Get($peer_id, $group);
                            if ($obj["action"]["member_id"] > 0)
                            {
                                if ($obj["action"]["member_id"] != $fromId)
                                {
                                    $event = new UserKickEvent($group, $from, User::Get($obj["action"]["member_id"]), $conversation);
                                    $this->newMessage->OnUserKick($event);
                                }
                                else
                                {
                                    $event = new UserLeftEvent($group, $from, $conversation);
                                    $this->newMessage->OnUserLeft($event);
                                }
                            }
                            else if ($obj["action"]["member_id"] < 0)
                            {
                                $event = new BotLeftEvent($group, $from, Group::Get($obj["action"]["member_id"]), $conversation);
                                $this->newMessage->OnBotLeft($event);
                            }
                        }
                        break;
                    }
                    $conversation = $peer_id > 2000000000 ? Conversation::Get($peer_id, $group) : null;

                    try
                    {
                        $inboxMsg = new Message($peer_id > 2000000000 ? $conversation_message_id : $msgid, $date, $from, $group, $text, $peer_id, $attachments, $conversation, $geolocation);
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
                            $cmd = new Command($cmdname, $arrcmd, $from, $inboxMsg, null);
                            $event = new CommandPreProcessEvent($group, $cmd, true, null);
                            $this->newMessage->OnCommandPreProcess($event);
                            if (!$event->IsCancelled())
                            {
                                $this->commandHandler->OnCommand($cmd, $group);
                            }
                        }
                        else
                        {
                            $event = new NewPrivateMessageEvent($group, $inboxMsg, $request->GetRawContent());
                            $this->newMessage->OnNewPrivateMessage($event);
                        }
                    }
                    else
                    {
                        $conversation = Conversation::Get($peer_id, $group);
                        if (substr($text, 0, 1) == "/")
                        {
                            $rawcmd = substr($text, 1, strlen($text) - 1);
                            $arrcmd = explode(' ', $rawcmd);
                            $cmdname = $arrcmd[0];
                            array_shift($arrcmd);
                            $cmd = new Command($cmdname, $arrcmd, $from, $inboxMsg, $conversation);
                            $event = new CommandPreProcessEvent($group, $cmd, false, $conversation);
                            $this->newMessage->OnCommandPreProcess($event);
                            if (!$event->IsCancelled())
                            {
                                $this->commandHandler->OnConversationCommand($cmd, $conversation, $group);
                            }
                        }
                        else
                        {
                            $event = new NewConversationMessageEvent($group, $inboxMsg, $conversation, $request->GetRawContent());
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
                    $event = new UserJoinGroupEvent($group, $user, $__join, $__request, $__approved);
                    $this->inGroupUserAction->OnUserJoinGroup($event);
                    break;

                case "group_leave":
                    $obj = $data["object"];
                    $user = User::Get($obj["user_id"]);
                    $group = Group::Get($data["group_id"]);
                    $leftBySelf = $obj["self"] == 1;
                    $event = new UserLeftGroupEvent($group, $user, $leftBySelf);
                    $this->inGroupUserAction->OnUserLeftGroup($event);
                    break;

                case "wall_post_new":
                    $obj = $data["object"];

                    $id = $obj["id"];
                    $date = $obj["date"];
                    $text = $obj["text"] ?? "";
                    $ads = !!($obj["marked_as_ads"] ?? false);
                    if ($obj["from_id"] < 0)
                    {
                        $from = Group::Get($obj["from_id"]);
                    }
                    else
                    {
                        $from = User::Get($obj["from_id"]);
                    }
                    if ($obj["owner_id"] < 0)
                    {
                        $owner = Group::Get($obj["owner_id"]);
                    }
                    else
                    {
                        $owner = User::Get($obj["owner_id"]);
                    }
                    $created = User::Get($obj["created_by"]);
                    $attachments = $obj["attachments"] ?? [];

                    $post = Post::Factory($id, $owner, $date, $text, $ads, $from, $created, $attachments);
                    $event = new NewPostEvent($group, $post);
                    $this->groupWall->OnNewPost($event);
                    break;

                case "wall_reply_new":
                    $obj = $data["object"];

                    $id = $obj["id"];
                    $date = $obj["date"];
                    $text = $obj["text"] ?? "";
                    if ($obj["owner_id"] < 0)
                    {
                        $owner = Group::Get($obj["owner_id"]);
                    }
                    else
                    {
                        $owner = User::Get($obj["owner_id"]);
                    }
                    $post = Post::Factory($obj["post_id"], $owner);

                    if ($obj["from_id"] < 0)
                    {
                        $from = Group::Get($obj["from_id"]);
                    }
                    else
                    {
                        $from = User::Get($obj["from_id"]);
                    }

                    $attachments = $obj["attachments"] ?? [];

                    $replyTo = null;
                    if (isset($obj["reply_to_user"]))
                    {
                        $replyTo = $obj["reply_to_user"] < 0 ? Group::Get($obj["reply_to_user"]) : User::Get($obj["reply_to_user"]);
                    }

                    $replyToComment = null;
                    if (isset($obj["reply_to_comment"]))
                    {
                        $replyToComment = Comment::Factory($obj["reply_to_comment"], $owner);
                    }

                    $comment = Comment::Factory($id, $owner, $post, $date, $from, $text, $attachments, $replyTo, $replyToComment);
                    $event = new NewCommentEvent($group, $comment);
                    $this->groupWall->OnNewComment($event);
                    break;

                default:
                    $event = new UnregisteredVkEvent($group, $request->GetRawContent(), json_decode($request->GetRawContent(), true), $data["type"]);
                    $this->common->OnUnregistered($event);
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
        if (count($this->config["groups_to_access_tokens"]) == 0)
        {
            cmm::e("config.nogroups");
            $somethingWrong = true;
        }
        if (json_encode($this->config["groups_to_access_tokens"]) == json_encode($this->GetDefaultConfig()["groups_to_access_tokens"]))
        {
            cmm::e("config.groupsnotconfigured");
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
            "groups_to_access_tokens" => [
                "club1234" => "this_is_a_default_group",
                "club5678" => "access_token_of_second_group"
            ],
            "main_admin_access_token" => "",
            "server_addr" => "0.0.0.0",
            "server_port" => 80,
            "groups_to_secret_keys" => [
                "club1234" => "secret_key_1",
                "club5678" => "secret_key_2"
            ],
            "memory_limit" => "512M",
            "restart_on_ctrl_c" => true,
            "enable_wall_cache" => true,
            "enable_help_command_for_conversations" => true,
            "show_invalid_command_in_conversations" => true,
            "disable_plugin_on_exception" => true
        );
    }
}