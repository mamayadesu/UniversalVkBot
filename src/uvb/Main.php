<?php

namespace uvb;

use Exception;
use \IO\Console;
use \Application\Application;
use IO\FileDirectory;
use Phar;
use RecursiveIteratorIterator;
use Threading\ChildThreadedObject;
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
use uvb\Handlers\Ticker;
use uvb\Handlers\Unregistered;
use uvb\Models\Attachments\AttachmentTypes;
use uvb\Models\Command;
use uvb\Models\InboxMessage;
use uvb\Models\User;
use uvb\Models\UserSex;
use uvb\Plugin\CommandManager;
use uvb\Plugin\PluginBase;
use uvb\Plugin\PluginManager;
use uvb\Protection\AddressBlocker;
use uvb\Rcon\RconHandler;
use uvb\Rcon\RconResource;
use uvb\Repositories\MessageRepository;
use uvb\Repositories\UserRepository;
use uvb\Services\UserCache;
use uvb\System\ExitCode;
use uvb\System\Update\Updater;
use uvb\Threads\Readline;
use uvb\Utils\AttachmentParser;
use VK\Actions\Messages;
use VK\Client\VKApiClient;
use VK\Actions\Friends;
use uvb\Handlers\NewMessage;
use uvb\Handlers\CommandHandler;
use uvb\Threads\Ticker as TickerThread;
use \Swoole\Http\Server;
use \Swoole\Http\Request;
use \Swoole\Http\Response;
use Threading\Threaded;

/**
 * @ignore
 */

final class Main
{
    private static bool $mainInitialized = false;

    private float $mt_start;
    private int $timestart = 0;
    public ChildThreadedObject $exitCode;

    private array/*<Threaded>*/ $threads = [];
    public array $config;
    private int $tickerLastTick = 0;
    private int $tickerInactiveSkipped = 0;
    private int $cmdPid = -1;
    private string $cmdNextKey = "";
    public ConsoleMessagesManager $consoleMessagesManager;
    public ConversationIds $conversationIds;
    private VKApiClient $api;
    private Friends $friends;
    private Messages $messages;
    public NewMessage $newMessage;
    public CommandHandler $commandHandler;
    public CommandManager $commandManager;
    public Unregistered $unregistered;
    public PluginManager $pluginManager;
    public UserCache $userCache;
    public Bot $bot;
    private static User $consoleUser;
    public SystemCommandsHandler $sch;
    public Ticker $ticker;
    public RconHandler $rconHandler;
    public InGroupUserAction $inGroupUserAction;
    public ?Server $server;
    private SystemLogger $sl;
    private Logger $logger;
    public Updater $updater;
    private $stdin;

    public function __construct(array $args, bool $swooleLoaded)
    {
        if (self::$mainInitialized)
        {
            throw new Exception("You cannot initialize the main class.");
        }
        self::$mainInitialized = true;
        $this->mt_start = microtime(true);
        $this->exitCode = ExitCode::Run([], $this)->GetChildThreadedObject();

        $this->stdin = fopen("php://stdin", "r");
        stream_set_blocking($this->stdin, false);
        $pargs = Application::ParseArguments($args, "--");
        $colorsEnabled = true;
        if (isset($pargs["arguments"]["colors"]) && ($pargs["arguments"]["colors"] == "0" || $pargs["arguments"]["colors"] == "false" || $pargs["arguments"]["colors"] == "no"))
        {
            $colorsEnabled = false;
        }
        $this->userCache = new UserCache($this);
        $this->UpdateTitle();
        $this->sl = new SystemLogger($colorsEnabled); $this->UpdateTitle();
        $this->logger = new Logger("", $this->sl); $this->UpdateTitle();

        $this->updater = new Updater($this, $this->logger, $this->sl);

        $this->bot = new Bot($this, $this->logger, new Logger("CONSOLE", $this->sl), $this->sl);
        cmm::$bot = $this->bot;
        $this->consoleMessagesManager = new ConsoleMessagesManager($this);

        cmm::l("main.starting");
        cmm::l("main.programversion", [Application::GetVersion()]);
        cmm::l("main.apiversion", [APIVersions::Last()]);

        if (!$swooleLoaded)
        {
            cmm::w("swoole.notloaded", []);
        }

        $this->api = new VKApiClient();
        $this->friends = $this->api->friends();
        $this->messages = $this->api->messages();
        $this->newMessage = new NewMessage($this); $this->UpdateTitle();
        $this->commandHandler = new CommandHandler($this); $this->UpdateTitle();
        $this->unregistered = new Unregistered($this); $this->UpdateTitle();
        $this->pluginManager = new PluginManager($this, $this->sl); $this->UpdateTitle();
        $this->commandManager = new CommandManager($this); $this->UpdateTitle();
        $this->sch = new SystemCommandsHandler("System", "", "", [], $this->bot, $this->logger);
        $this->inGroupUserAction = new InGroupUserAction($this); $this->UpdateTitle();
        $this->sch->SetCmdMgr($this->commandManager);
        $this->sch->SetPlgMgr($this->pluginManager);
        $this->sch->SetMain($this);
        $this->sch->RegisterSystemCommands();
        $this->rconHandler = new RconHandler();
        $this->conversationIds = new ConversationIds();
        ConversationIdsResource::$conversationIds = $this->conversationIds;
        RconResource::$RconHandler = $this->rconHandler;
        $this->ticker = new Ticker($this);
        cmm::l("bot.loadingusers");
        $this->userCache->Load(true);
        $this->LoadData();
        if (!in_array(0, $this->config["admins"]))
        {
            $this->config["admins"][] = 0;
        }
        /*if (strtoupper(substr(PHP_OS, 0, 3)) == "WIN" && $this->config["console_commands_input_enabled"])
        {
            cmm::w("main.cmdwindowsinput");
            $this->config["console_commands_input_enabled"] = false;
        }*/
        ConfigResource::Init($this->config);
        if ($this->config["rcon_enabled"])
        {
            if ($this->config["rcon_password"] == "")
            {
                cmm::w("rcon.passrequired");
                $this->config["rcon_enabled"] = false;
            }
            if ($this->config["rcon_password"] == $this->GetDefaultConfig()["rcon_password"])
            {
                cmm::w("rcon.defaultpass");
            }
            if ($this->config["rcon_enabled"])
            {
                cmm::l("rcon.started");
            }
        }
        if (!$this->config["console_commands_input_enabled"])
        {
            cmm::l("main.cmdinputdisabled");
        }

        self::$consoleUser = new User(0, ["nom" => "CONSOLE"], ["nom" => ""], UserSex::MALE);

        cmm::l("main.loadingplugins");
        $this->pluginManager->LoadPlugins();
        $plugins = $this->pluginManager->GetPlugins();
        $pluginsName = [];
        foreach ($plugins as $plugin)
        {if(!$plugin instanceof PluginBase)continue;
            $pluginsName[] = $plugin->GetPluginName();
        }
        cmm::l("main.pluginsloaded", [count($plugins), implode(", ", $pluginsName)]);

        if (!$this->updater->UpdateWasFinished())
        {
            $this->updater->UpdateCommand();
        }

        cmm::l("main.startingticker");
        $this->StartTicker();
        if (Config::Get("console_commands_input_enabled") && Readline::IsWindows())
        {
            $rladdr = Config::Get("server_addr");
            if ($rladdr == "0.0.0.0")
            {
                $rladdr = "127.0.0.1";
            }
            $rlport = Config::Get("server_port") . "";
            $rlruntime = null;
            try
            {
                $rlruntime = Readline::Run([$rladdr, $rlport], $this);
            }
            catch (\Exception $e)
            {
                $rlruntime = null;
            }
            if ($rlruntime != null)
            {
                $this->threads[] = $rlruntime;
            }
        }
        cmm::l("main.startingswoole"); $this->UpdateTitle();
        $this->server = new Server(Config::Get("server_addr"), Config::Get("server_port")); $this->UpdateTitle();
        $this->server->on("start", function(Server $server) { $this->Server_Start($server);}); $this->UpdateTitle();
        $this->server->on("shutdown", function(Server $server) { $this->Server_Stop($server);}); $this->UpdateTitle();
        $this->server->on("request", function(Request $request, Response $response) { $this->Server_Request($request, $response);}); $this->UpdateTitle();
        $this->timestart = time(); $this->UpdateTitle();
        $this->server->start();
        $exitCode = $this->exitCode->Get();
        @$this->exitCode->Exit();

        if ($exitCode == 3)
        {
            $exitCode = 2;
            $this->InstallUpdate();
        }
        if ($exitCode == 2)
        {
            sleep(3);
        }
        exit($exitCode);
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
                if ($class == "autoload" || $class == "thread" || ($cstrlen > 5 && substr($class, 0, 5) == "Core\\"))
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

    private function StartTicker() : void
    {
        $this->tickerLastTick = time();
        $this->tickerInactiveSkipped = 0;

        $addr = Config::Get("server_addr");
        if ($addr == "0.0.0.0")
        {
            $addr = "127.0.0.1";
        }
        $port = Config::Get("server_port") . "";
        $tickerruntime = null;
        try
        {
            $tickerruntime = TickerThread::Run([$addr, $port], $this);
        }
        catch (\Exception $e)
        {
            $tickerruntime = null;
        }
        if ($tickerruntime != null)
        {
            $this->threads[] = $tickerruntime;
        }
        $end = floor(microtime(true) * 1000) + 100;
        while (floor(microtime(true) * 1000) <= $end) { }
        //Application::SetTitle(Application::GetName());
    }

    private function Server_Stop(Server $server) : void
    {
        cmm::l("swoole.shuttingdown");
        if ($this->exitCode->Get() != 3)
        {
            $this->sl->CloseLogger();
        }
    }

    private function Server_Start(Server $server) : void
    {
        cmm::l("swoole.addr", [Config::Get("server_addr"), Config::Get("server_port")]);
        cmm::l("swoole.uri", [Config::Get("server_addr"), Config::Get("server_port"), Config::Get("requests_uri")]);
        $t = (microtime(true) - $this->mt_start);
        $t = $t * 10000;
        $t = round($t);
        $t = $t / 10000;

        cmm::l("main.started", [$t]);
        if ($this->updater->UpdateWasFinished())
        {
            $this->bot->GetLogger()->Log($this->bot->GetLogger()->GetColoredString(cmm::g("system.update.updated", [Application::GetVersion()]), ForegroundColors::DARK_GREEN, BackgroundColors::BLACK));
        }
    }

    public function GetUptime() : int
    {
        return time() - $this->timestart;
    }

    public function GetParsedUptime() : array
    {
        $c = time() - $this->timestart;
        $days = floor(($c) / 86400);
        $hours = floor(($c) / 60 / 60 - $days * 24);
        $minutes = floor(($c) / 60 - floor(($c) / 60 / 60) * 60);
        $seconds = ((($c) - $hours * 3600 - $days * 86400 - $minutes * 60));

        return array(
            "d" => $days,
            "h" => $hours,
            "m" => $minutes,
            "s" => $seconds
        );
    }

    public function UpdateTitle() : void
    {
        if ($this->timestart > 0)
        {
            $this->UpdateTitleStarted();
        }
        else
        {
            $this->UpdateTitleNotStarted();
        }
    }

    private function UpdateTitleNotStarted() : void
    {
        $memory_usage = false;
        $max = -1;
        $t = $type = "m";
        $using = 0;
        if (function_exists("memory_get_usage"))
        {
            $imax = ini_get("memory_limit");
            $using = memory_get_peak_usage(true);
            if ($imax != -1)
            {
                $imax1 = str_split($imax);
                $t = $imax1[strlen($imax) - 1];
                $max = substr($imax, 0, -1);
            }
            switch (strtolower($t))
            {
                case "b":
                    $type = "b";
                    break;

                case "k":
                    $type = "k";
                    $using = round($using / 1024, 2);
                    break;

                default:
                case "m":
                    $type = "m";
                    $using = round($using / 1024 / 1024, 2);
                    break;

                case "g":
                    $type = "g";
                    $using = round($using / 1024 / 1024 / 1024, 2);
                    break;
            }
            $memory_usage = true;
        }
        $mu = ($memory_usage ? $using . " " . strtoupper($type) . "B" . " / " . ($max != -1 ? $max : "∞") . " " . strtoupper($t) . "B" : "?");
        $title = "UniversalVkBot | Memory peak: " . $mu . " | Memory usage: " . Main::GetMemoryUsage() . " | Users cached: " . count($this->userCache->GetUsers());
        Application::SetTitle($title);
    }

    private function UpdateTitleStarted() : void
    {
        $puptime = $this->GetParsedUptime();
        $uptime_text = cmm::g("main.uptime", [$puptime["d"], $puptime["h"], $puptime["m"], $puptime["s"]]);
        $memory_usage = false;
        $max = -1;
        $t = $type = "m";
        $using = 0;
        if (function_exists("memory_get_usage"))
        {
            $imax = ini_get("memory_limit");
            $using = memory_get_peak_usage(true);
            if ($imax != -1)
            {
                $imax1 = str_split($imax);
                $t = $imax1[strlen($imax) - 1];
                $max = substr($imax, 0, -1);
            }
            switch (strtolower($t))
            {
                case "b":
                    $type = "b";
                    break;

                case "k":
                    $type = "k";
                    $using = round($using / 1024, 2);
                    break;

                default:
                case "m":
                    $type = "m";
                    $using = round($using / 1024 / 1024, 2);
                    break;

                case "g":
                    $type = "g";
                    $using = round($using / 1024 / 1024 / 1024, 2);
                    break;
            }
            $memory_usage = true;
        }
        $mu = ($memory_usage ? $using . " " . strtoupper($type) . "B" . " / " . ($max != -1 ? $max : "∞") . " " . strtoupper($t) . "B" : "?");
        $title = "";
        if ($this->timestart > 0)
        {
            $title = "UVB | Uptime: " . $uptime_text . " | Memory peak: " . $mu . " | Memory usage: " . Main::GetMemoryUsage() . " | Users cached: " . count($this->userCache->GetUsers());
        }
        else
        {
            $title = "UniversalVkBot | Memory peak: " . $mu . " | Memory usage: " . Main::GetMemoryUsage() . " | Users cached: " . count($this->userCache->GetUsers());
        }
        Application::SetTitle($title);
    }

    public static function GetMemoryUsage() : string
    {
        $memory_usage = "";
        if (!function_exists("memory_get_usage"))
        {
            return $memory_usage;
        }
        $mu = memory_get_usage();
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

    public function GetStatisticAsString() : string
    {
        $uptime = $this->GetUptime();
        $puptime = $this->GetParsedUptime();
        $uptime_text = cmm::g("main.uptime", [$puptime["d"], $puptime["h"], $puptime["m"], $puptime["s"]]);
        $memory_usage = false;
        $max = -1;
        $t = $type = "m";
        $using = 0;
        if (function_exists("memory_get_usage"))
        {
            $imax = ini_get("memory_limit");
            $using = memory_get_peak_usage(true);
            if ($imax != -1)
            {
                $imax1 = str_split($imax);
                $t = $imax1[strlen($imax) - 1];
                $max = substr($imax, 0, -1);
            }
            switch (strtolower($t))
            {
                case "b":
                    $type = "b";
                    break;

                case "k":
                    $type = "k";
                    $using = round($using / 1024, 2);
                    break;

                default:
                case "m":
                    $type = "m";
                    $using = round($using / 1024 / 1024, 2);
                    break;

                case "g":
                    $type = "g";
                    $using = round($using / 1024 / 1024 / 1024, 2);
                    break;
            }
            $memory_usage = true;
        }
        $pid = -1;
        if (function_exists("getmypid"))
        {
            $pid = getmypid();
        }
        $log = cmm::g("main.statistic", [Config::Get("server_addr"), Config::Get("server_port"), $uptime, $uptime_text, ($memory_usage ? $using . " " . strtoupper($type) . "B" . " / " . ($max != -1 ? $max : "∞") . " " . strtoupper($t) . "B" : "?"), self::GetMemoryUsage(), count($this->userCache->GetUsers()), ($pid != -1 ? $pid : "?")]);
        return $log;
    }

    public static function GetConsoleAsUser() : User
    {
        return self::$consoleUser;
    }

    public static function GetRconAsUser(int $port) : User
    {
        return new User(-3000000000 - $port, ["nom" => "RCON"], ["nom" => ""], UserSex::FEMALE);
    }

    private function Server_Request(Request $request, Response $response) : void
    {
        if ($this->bot->IsShuttingDown())
        {
            $response->header("Content-Type", "text/plain");
            $response->end("Bot is shutting down");
            return;
        }
        $data = json_decode($request->rawcontent(), true);
        $tickerAllowedAddresses = ["127.0.0.1", Config::Get("server_addr"), "192.168.1.1", "192.168.100.1", "192.168.0.1"];
        if (!in_array($request->server["remote_addr"], $tickerAllowedAddresses))
        {
            if ($this->bot->GetAddressBlocker()->IsBanned($request->server["remote_addr"]))
            {
                $response->status(308);
                $response->header("Location", "https:///google.ru");
                $response->end("");
                return;
            }
            $uriCheck1 = AddressBlocker::UriHasThreat($request->server["request_uri"]);
            $uriCheck2 = AddressBlocker::UriHasThreat($request->rawcontent());
            if ($uriCheck1 || ($uriCheck2 && ($data == null || !isset($data["secret"]) || !isset($data["group_id"]))))
            {
                $response->status(308);
                $response->header("Location", "https://google.ru");
                $response->end("");
                $this->bot->GetAddressBlocker()->Ban($request->server["remote_addr"]);
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
                cmm::w("request.threatdetected", [$request->server["remote_addr"]]);
                $this->bot->GetLogger()->Warn($request->server["request_uri"]);
                return;
            }
        }
        $input = "";
        if (Config::Get("console_commands_input_enabled"))
        {
            if (Readline::IsWindows() && $request->server["request_uri"] == "/cmd" && in_array($request->server["remote_addr"], $tickerAllowedAddresses))
            {
                if ($data["first"] && $this->cmdPid == -1)
                {
                    $this->cmdPid = $data["pid"];
                    $this->cmdNextKey = md5(time() . " " . rand(-100, 100) . md5(rand(-100, 100) . Config::Get("server_addr")));
                    $response->end($this->cmdNextKey);
                    return;
                }
                else if (!$data["first"] && $this->cmdPid != $data["pid"])
                {
                    $response->end("fail");
                    return;
                }
                else if (!$data["first"] && $data["key"] != $this->cmdNextKey)
                {
                    $response->end("fail");
                    return;
                }
                else if (!$data["first"] && $data["key"] == $this->cmdNextKey && $data["pid"] == $this->cmdPid)
                {
                    $this->cmdNextKey = md5(time() . " " . rand(-100, 100) . md5(rand(-100, 100) . Config::Get("server_addr")));
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
                    $response->end($this->cmdNextKey);
                    return;
                }
                else
                {
                    $response->end("fail");
                    return;
                }
            }
            else if (!Readline::IsWindows())
            {
                $input = rtrim(fgets($this->stdin));
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
        $response->header("Content-Type", "text/plain");

        if ((time() - $this->tickerLastTick) > 3)
        {
            $this->tickerInactiveSkipped++;
        }

        if ($this->tickerInactiveSkipped >= 2)
        {
            $this->StartTicker();
        }

        if ($request->server["request_uri"] == "/ticker" && in_array($request->server["remote_addr"], $tickerAllowedAddresses))
        {
            $this->tickerLastTick = time();
            $this->tickerInactiveSkipped = 0;
            $this->ticker->OnTick();
            $response->end("ticker ok");
            return;
        }

        if ($request->server["request_uri"] == "/rcon")
        {
            if (!Config::Get("rcon_enabled"))
            {
                $response->end("Rcon is disabled");
                return;
            }
            $whitelisted = true;
            $blocklisted = false;
            $seip = [];
            $ip = $request->server["remote_addr"];
            $separatedIp = explode('.', $ip);
            if (Config::Get("rcon_whitelist_enabled"))
            {
                $whitelisted = false;
                foreach (Config::Get("rcon_whitelist") as $eip)
                {
                    $seip = explode('.', $eip);
                    if (count($seip) != 4)
                    {
                        continue;
                    }
                    if (($seip[0] == "*" || $seip[0] == $separatedIp[0]) && ($seip[1] == "*" || $seip[1] == $separatedIp[1]) && ($seip[2] == "*" || $seip[2] == $separatedIp[2]) && ($seip[3] == "*" || $seip[3] == $separatedIp[3]))
                    {
                        $whitelisted = true;
                        break;
                    }
                }
            }
            foreach (Config::Get("rcon_blocklist") as $eip)
            {
                $seip = explode('.', $eip);
                if (count($seip) != 4)
                {
                    continue;
                }
                if (($seip[0] == "*" || $seip[0] == $separatedIp[0]) && ($seip[1] == "*" || $seip[1] == $separatedIp[1]) && ($seip[2] == "*" || $seip[2] == $separatedIp[2]) && ($seip[3] == "*" || $seip[3] == $separatedIp[3]))
                {
                    $blocklisted = true;
                    break;
                }
            }

            if (!$whitelisted)
            {
                $response->end("You're not white-listed");
                return;
            }

            if ($blocklisted)
            {
                $response->end("You're block-listed");
                return;
            }

            if ($data == null)
            {
                cmm::w("rcon.incorrectdata", [$ip, $request->server["remote_port"]]);
                $this->bot->GetLogger()->Warn($request->rawcontent());
                $response->end("Incorrect data");
                return;
            }

            if (!isset($data["password"]))
            {
                cmm::w("rcon.passisnotset", [$ip, $request->server["remote_port"]]);
                $response->end("Password is not set");
                return;
            }

            if (!isset($data["cmd"]))
            {
                cmm::w("rcon.passisnotset", [$ip, $request->server["remote_port"]]);
                $response->end("Command is not set");
                return;
            }

            if (Config::Get("rcon_password") != $data["password"])
            {
                cmm::e("rcon.wrongpassword", [$ip, $request->server["remote_port"]]);
                $response->end("Wrong password");
                return;
            }

            if ($data["cmd"] == "#rconconnectioncheck")
            {
                cmm::l("rcon.connected", [$ip, $request->server["remote_port"]]);
                $response->end("OK");
                return;
            }
            if ($data["cmd"] == "#disconnect")
            {
                cmm::l("rcon.disconnected", [$ip, $request->server["remote_port"]]);
                $response->end("OK");
                return;
            }
            $user = self::GetRconAsUser($request->server["remote_port"]);
            $this->config["admins"][] = $user->GetVkId();
            $input = str_replace(["\r", "\n"], ["", ""], $data["cmd"]);
            cmm::l("rcon.cmdinput", [$ip, $request->server["remote_port"], $input]);
            $input1 = explode(' ', $input);
            $commandName = $input1[0];
            array_shift($input1);
            $command = new Command($commandName, $input1, $user, 0);
            $_event = new CommandPreProcessEvent($command, true, 0);
            $this->newMessage->OnCommandPreProcess($_event);
            if (!$_event->IsCancelled())
            {
                $this->commandHandler->OnCommand($command);
            }
            $responseText = $this->rconHandler->GetResponse("r" . $request->server["remote_port"]);
            $response->end($responseText);
            return;
        }

        if ($request->server["request_uri"] != Config::Get("requests_uri"))
        {
            $this->bot->GetLogger()->Error("*******************");
            cmm::e("request.wronguri", [$request->server["request_uri"]]);
            $this->bot->GetLogger()->Error($request->rawcontent());
            $this->bot->GetLogger()->Error($request->server["remote_addr"]);
            $response->end("Wrong URI");
            $this->bot->GetLogger()->Error("*******************");
            return;
        }

        if ($data == null)
        {
            cmm::e("request.incorrectdata");
            $this->bot->GetLogger()->Error($request->rawcontent());
            $response->end("Bad request");
            return;
        }
        if ($data["type"] == "confirmation")
        {
            cmm::l("request.confirm");
            if (!isset($data["group_id"]) || $data["group_id"] != Config::Get("group_id"))
            {
                cmm::w("request.invalidgroup");
                $response->end("Invalid group");
                return;
            }
            if (!isset($data["secret"]) || $data["secret"] != Config::Get("secret_key"))
            {
                cmm::e("request.wrongsecretkey");
                $response->end("Invalid secret key");
                return;
            }
            $response->end(Config::Get("confirm_response"));
            $this->bot->GetLogger()->Log($this->bot->GetLogger()->GetColoredString(cmm::g("request.confirmed"), ForegroundColors::GREEN, BackgroundColors::BLACK));
            return;
        }
        else
        {
            $obj = array();
            $event = null;
            switch ($data["type"])
            {
                case "message_new":
                    $obj = $data["object"];
                    $date = $obj["date"];
                    $fromId = $obj["from_id"];
                    $peer_id = $obj["peer_id"];
                    $from = UserRepository::Get($fromId);
                    $text = $obj["text"];
                    $attachments1 = $obj["attachments"];
                    $attachments = [];
                    $attachment = null;
                    foreach ($attachments1 as $attachment1)
                    {
                        $attachment = AttachmentParser::Parse($attachment1);
                        if ($attachment != null)
                        {
                            $attachments[] = $attachment;
                        }
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
                            if (-$obj["action"]["member_id"] == Config::Get("group_id"))
                            {
                                $event = new BotJoinEvent($from, $peer_id);
                                $this->newMessage->OnBotJoin($event);
                            }
                            else if ($obj["action"]["member_id"] > 0 && $obj["action"]["member_id"] != $fromId)
                            {
                                $event = new UserAddEvent($from, UserRepository::Get($obj["action"]["member_id"]), $peer_id);
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
                            if ($obj["action"]["member_id"] > 0 && -$fromId != Config::Get("group_id"))
                            {
                                if ($obj["action"]["member_id"] != $fromId)
                                {
                                    $event = new UserKickEvent($from, UserRepository::Get($obj["action"]["member_id"]), $peer_id);
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
                        $inboxMsg = new InboxMessage($msgid, $date, $from, $text, $peer_id, $attachments);
                    }
                    catch (\Exception $e)
                    {
                        $this->bot->GetLogger()->Critical($e->getMessage());
                        $response->end("ok");
                        return;
                    }

                    if (MessageRepository::IsPrivateMessage($peer_id))
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
                            $event = new NewPrivateMessageEvent($inboxMsg, $request->rawcontent());
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
                            $event = new NewConversationMessageEvent($inboxMsg, $peer_id, $request->rawcontent());
                            $this->newMessage->OnNewConversationMessage($event);
                        }
                    }
                    break;

                case "group_join":
                    $obj = $data["object"];
                    $user = UserRepository::Get($obj["user_id"]);
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
                    $user = UserRepository::Get($obj["user_id"]);
                    $leftBySelf = $obj["self"] == 1;
                    $event = new UserLeftGroupEvent($user, $leftBySelf);
                    $this->inGroupUserAction->OnUserLeftGroup($event);
                    break;

                default:
                    $event = new UnregisteredVkEvent($request->rawcontent(), json_decode($request->rawcontent(), true), $data["type"]);
                    $this->unregistered->OnUnregistered($event);
                    break;
            }
        }
        //$this->bot->Log("Response: ok");
        $response->end("ok");
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
            "rcon_enabled" => false,
            "rcon_password" => "defaultrconpass1234",
            "rcon_whitelist_enabled" => false,
            "rcon_whitelist" => [
                "127.0.0.1"
            ],
            "rcon_blocklist" => [

            ],
            "requests_uri" => "/request",
            "confirm_response" => "",
            "console_commands_input_enabled" => true,
            "secret_key" => "",
            "ticker_command" => "php",
            "admins" => [

            ]
        );
    }

}