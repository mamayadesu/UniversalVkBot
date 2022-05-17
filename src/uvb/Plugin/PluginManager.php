<?php

namespace uvb\Plugin;

use Application\Application;
use \Exception;
use \Throwable;
use uvb\APIVersions;
use uvb\Bot;
use uvb\cmm;
use \Phar;
use uvb\Logger;
use uvb\Main;
use \RecursiveIteratorIterator;
use uvb\Models\CommandInfo;
use uvb\Services\Scheduler\Scheduler;
use uvb\System\CrashHandler;
use uvb\SystemLogger;

/**
 * Менеджер плагинов. Обеспечивает доступ к работе плагинов и их управлению.
 * Данный класс не допускается к инициализации плагином и может быть запущен только ядром.
 * Плагины идентифицируются по их именам, указанные в `plugin.json` в параметре `name`
 * @package uvb\Plugin
 *
 */

class PluginManager
{
    /**
     * @ignore
     */
    private array/*<Plugin>*/ $loadedPlugins = [];

    /**
     * @ignore
     */
    private array/*<Plugin>*/ $unloadedPlugins = [];

    /**
     * @ignore
     */
    private array/*<string, string>*/ $waitingForPlugins = array();

    /**
     * @ignore
     */
    private Main $main;

    /**
     * @ignore
     */
    private SystemLogger $sl;

    /**
     * @ignore
     */
    private static ?PluginManager $instance = null;

    /**
     * @ignore
     */
    public function __construct(Main $main, SystemLogger $sl)
    {
        if (self::$instance != null)
        {
            throw new \Exception("PluginManager is already initialized");
        }
        self::$instance = $this;
        $this->sl = $sl;
        $this->main = $main;
        @mkdir($this->GetPathToPlugins());
    }

    public function GetQueue() : array/*<string, string>*/
    {
        return $this->waitingForPlugins;
    }

    public function AddPluginToQueue(string $key, string $value) : void
    {
        if (!isset($this->waitingForPlugins[$key]))
        {
            $this->waitingForPlugins[$key] = $value;
        }
    }

    public function RemovePluginFromQueue(string $key) : void
    {
        if (isset($this->waitingForPlugins[$key]))
        {
            unset($this->waitingForPlugins[$key]);
        }
    }

    /**
     * Загружает плагин. В случае ошибки выводит информацию в консоль
     *
     * @param string $pathToPlugin Путь к PHAR-файлу
     */
    public function LoadPlugin(string $pathToPlugin) : void
    {
        $pluginDirectory = dirname($pathToPlugin) . DIRECTORY_SEPARATOR;
        $p = null;
        try
        {
            $p = new Phar($pathToPlugin, 0);
            $pathInPhar = "";
            $ptp = "";
            $fileContent = "";
            $ext = "";
            $spath = [];
            $fileName = "";
            $sfileName = [];
            $priority1 = [];
            $already_included = [];
            /* Данные плагина */
            $name = "";
            $version = "";
            $api_version = "";
            $main = "";
            $privateCommands = [];
            $conversationCommands = [];
            $dependences = [];
            $priorities = [];
            $pluginData = array();
            $filePluginJsonWasFound = false;
            $plugin = null;
            \hat();
            foreach (new RecursiveIteratorIterator($p) as $file)
            {
                \hat();
                $ptp = "phar://" . $pluginDirectory . basename($pathToPlugin) . DIRECTORY_SEPARATOR;
                $ptp = str_replace("\\", "/", $ptp);
                $pathInPhar = $file->getPathName();
                $pathInPhar = str_replace("\\", "/", $pathInPhar);
                $pathInPhar = str_replace($ptp, "", $pathInPhar);
                \hat();
                if ($pathInPhar == "plugin.json")
                {
                    $filePluginJsonWasFound = true;
                    $fileContent = file_get_contents($file->getPathName());
                    $pluginData = json_decode($fileContent, true);

                    if (!isset($pluginData["name"]) || !isset($pluginData["version"]) || !isset($pluginData["api_version"]) || !isset($pluginData["main"]))
                    {
                        cmm::e("pluginmanager.incorrectpluginjson", [$pathToPlugin]);
                        $this->RemovePluginFromQueue($name);
                        return;
                    }
                    \hat();
                    $name = $pluginData["name"];
                    $version = $pluginData["version"];
                    $api_version = $pluginData["api_version"];
                    if (!in_array($api_version, APIVersions::Get()))
                    {
                        cmm::e("pluginmanager.wrongapi", [$name, $api_version, implode(", ", APIVersions::Get())]);
                        return;
                    }
                    $main = $pluginData["main"];
                    $privateCommands = (isset($pluginData["private_commands"]) ? $pluginData["private_commands"] : array());
                    $conversationCommands = (isset($pluginData["conversation_commands"]) ? $pluginData["conversation_commands"] : array());
                    $priorities = (isset($pluginData["priorities"]) ? $pluginData["priorities"] : array());
                    $dependences = (isset($pluginData["dependences"]) ? $pluginData["dependences"] : array());
                    $dependencesList = [];

                    foreach ($dependences as $dependence)
                    {
                        if ($dependence != $name && !$this->IsPluginLoaded($dependence))
                        {
                            $dependencesList[] = $dependence;
                        }
                    }

                    if (count($dependencesList) > 0)
                    {
                        cmm::w("pluginmanager.dependences.waitingfor", [$name, implode(", ", $dependencesList)]);
                        $this->AddPluginToQueue($name, $pathToPlugin);
                        \hat();
                        return;
                    }
                    $this->RemovePluginFromQueue($name);
                    \hat();
                    foreach ($priorities as $priority)
                    {
                        \hat();
                        if (class_exists($priority))
                        {
                            cmm::w("pluginmanager.priorityalreadydeclared", [$priority]);
                            continue;
                        }
                        $priority1 = str_split($priority);
                        while (count($priority1) > 0 && ($priority1[0] == "/" || $priority1 == "\\"))
                        {
                            array_shift($priority1);
                        }
                        $priority = implode("", $priority1);
                        $priority = str_replace("\\", "/", $priority);
                        $priority .= ".php";
                        if (!in_array($ptp . "src/" . $priority, $already_included))
                        {
                            \hat();
                            try
                            {
                                require_once $ptp . "src/" . $priority;
                            }
                            catch (Throwable $e)
                            {
                                Bot::GetInstance()->GetLogger()->Critical($e->getMessage());
                                $this->RemovePluginFromQueue($name);
                                CrashHandler::Handle($e);
                                return;
                            }
                            $already_included[] = $ptp . "src/" . $priority;
                            \hat();
                        }
                        \hat();
                    }
                    \hat();
                }
                else if (!$filePluginJsonWasFound)
                {
                    cmm::e("pluginmanager.notplugin", [$pathToPlugin]);
                    $this->RemovePluginFromQueue($name);
                    return;
                }
                \hat();
                $spath = explode('/', $pathInPhar);
                if (strtolower($spath[0]) != "src")
                {
                    continue;
                }
                $fileName = $spath[count($spath) - 1];
                $sfileName = explode('.', $fileName);
                $ext = strtolower($sfileName[count($sfileName) - 1]);
                if ($ext != "php")
                {
                    continue;
                }
                $class_name1 = $spath;
                array_shift($class_name1);
                $class_name = implode("\\", $class_name1);
                $class_name = substr($class_name, 0, strlen($class_name) - 4);
                \hat();
                if (class_exists($class_name))
                {
                    cmm::w("pluginmanager.alreadydeclared", [$class_name]);
                    continue;
                }
                if (in_array($file->getPathName(), $already_included))
                {
                    continue;
                }
                try
                {
                    require_once $file->getPathName();
                }
                catch (Throwable $ex)
                {
                    cmm::c("pluginmanager.declarationfailed", [$file->getPathName(), $ex->getMessage()]);
                    $this->RemovePluginFromQueue($name);
                    CrashHandler::Handle($ex);
                    return;
                }
                \hat();
            }
            \hat();
            if (!$filePluginJsonWasFound)
            {
                cmm::e("pluginmanager.notplugin", [$pathToPlugin]);
                $this->RemovePluginFromQueue($name);
                return;
            }
            $this->LoadPluginFromSource($name, $version, $api_version, $main, $privateCommands, $conversationCommands, $dependences);
            \hat();
            foreach ($this->waitingForPlugins as $pluginName => $pathToPlugin)
            {
                \hat();
                if (!file_exists($pathToPlugin))
                {
                    cmm::e("pluginmanager.dependences.filenotfound", [$pluginName, $pathToPlugin]);
                    $this->RemovePluginFromQueue($pluginName);
                    continue;
                }
                $this->LoadPlugin($pathToPlugin);
            }
        }
        catch (Exception $e)
        {
            $p = null;
            cmm::e("pluginmanager.failedtoload", [$pathToPlugin, $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()]);
            CrashHandler::Handle($e);
            //$this->RemovePluginFromQueue($name);
        }
    }

    /**
     * Загрузить плагин из любого другого источника
     *
     * @param string $name Имя плагина
     * @param string $version Версия плагина
     * @param string $api_version API UniversalVkBot, который плагин использует
     * @param string $main Полное имя главного классаю Главный класс обязательно должен наследовать класс \uvb\Plugin\Plugin
     * @param array<string, array<string, string>> $privateCommands Список команд для личных сообщений
     * @param array<string, array<string, string>> $conversationCommands Список команд для бесед
     * @param array<string> $dependences Список плагинов, от которых загружаемый плагин зависит
     */
    public function LoadPluginFromSource(string $name, string $version, string $api_version, string $main, array $privateCommands, array $conversationCommands, array $dependences) : void
    {
        \hat();
        cmm::l("pluginmanager.loading", [$name, $version]);
        if (!in_array($api_version, APIVersions::Get()))
        {
            cmm::e("pluginmanager.wrongapi", [$name, $api_version, implode(", ", APIVersions::Get())]);
            return;
        }
        if (isset($this->loadedPlugins[$name]))
        {
            cmm::e("pluginmanager.alreadyloadedwiththesamename", [$name]);
            return;
        }
        if (strtolower($name) == "system" || strtolower($name) == "console")
        {
            cmm::e("pluginmanager.invalidname", [$name]);
            return;
        }
        \hat();
        $scheduler = new Scheduler($this->main->schedulerMaster);
        try
        {
            $plugin = new $main($name, $version, $api_version, $dependences, $this->main->bot, new Logger($name, $this->sl), $scheduler);
        }
        catch (Exception $ex)
        {
            cmm::e("pluginmanager.erroroccured", [$name, $ex->getMessage()]);
            CrashHandler::Handle($ex);
            return;
        }
        $scheduler->__setPlugin($plugin);
        \hat();
        if (!$plugin instanceof Plugin)
        {
            cmm::e("pluginmanager.wrongmain", [$name]);
            return;
        }
        \hat();
        $plugin->__declareUninitializer($plugin, $main);
        foreach ($privateCommands as $cmd => $cmdInfo)
        {
            $this->main->commandManager->RegisterPrivateCommand(new CommandInfo($cmd, "[" . $name . "] " . $cmdInfo["description"], $cmdInfo["allowed_for_users"], $plugin)); \hat();
        }
        \hat();
        foreach ($conversationCommands as $cmd => $cmdInfo)
        {
            $this->main->commandManager->RegisterConversationCommand(new CommandInfo($cmd, "[" . $name . "] " . $cmdInfo["description"], $cmdInfo["allowed_for_users"], $plugin)); \hat();
        }
        \hat();
        $this->loadedPlugins[$name] = $plugin;
        $this->RemovePluginFromQueue($name);
        \hat();
        try
        {
            $plugin->OnEnable();
        }
        catch (Throwable $e)
        {
            cmm::c("pluginmanager.erroroccured", [$name, $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()]); \hat();
            CrashHandler::Handle($e, $plugin);
            $plugin->DisablePlugin(); \hat();
            return;
        }
    }

    /**
     * Загружает плагины из папки `/plugins`
     */
    public function LoadPlugins() : void
    {
        foreach (glob($this->GetPathToPlugins() . "*.phar") as $pharPath)
        {
            cmm::l("pluginmanager.found", [$pharPath]);
            $this->LoadPlugin($pharPath);
        }
    }

    /**
     * Получить путь к плагинам
     *
     * @return string Полный путь к папке с плагинами
     */
    public function GetPathToPlugins() : string
    {
        return Application::GetExecutableDirectory() . "plugins" . DIRECTORY_SEPARATOR;
    }

    /**
     * Загружен ли плагин
     *
     * @param string $name Имя плагина
     * @return bool TRUE - плагин с указанным названием загружен. FALSE - плагина с таким именем нет
     */
    public function IsPluginLoaded(string $name) : bool
    {
        return isset($this->loadedPlugins[$name]);
    }

    /**
     * Получить объект главного класса плагина
     *
     * @param string $name Имя загруженного плагина
     * @return Plugin|null Объект главного класса плагина. Метод вернёт NULL, если плагин с таким названием не был загружен
     */
    public function GetPlugin(string $name) : ?Plugin
    {
        if (!isset($this->loadedPlugins[$name]))
        {
            cmm::e("pluginmanager.getplugin.notloaded", [$name]);
            return null;
        }
        return $this->loadedPlugins[$name];
    }

    /**
     * Завершить работу плагина
     *
     * @param string $name Имя загруженного плагина
     * @param bool $printErrors TRUE - будут выведены ошибки (если они есть) в консоль
     */
    public function UnloadPlugin(string $name, bool $printErrors = true) : void
    {
        \hat();
        if (!isset($this->loadedPlugins[$name]) || !$this->loadedPlugins[$name] instanceof Plugin)
        {
            if ($printErrors)
            {
                cmm::e("pluginmanager.getplugin.notloaded", [$name]);
            }
            return;
        }
        cmm::l("pluginmanager.stoppingplugin", [$name]); \hat();
        try
        {
            $this->loadedPlugins[$name]->OnDisable();
        }
        catch (Throwable $e)
        {
            cmm::c("pluginmanager.disableerroroccured", [$name, $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()]);
            CrashHandler::Handle($e, $this->loadedPlugins[$name]);
        }
        \hat();

        if (!in_array($name, $this->unloadedPlugins))
        {
            $this->unloadedPlugins[] = $name;
        }

        $commands = $this->main->commandManager->GetRegisteredPrivateCommands();
        foreach ($commands as $cmd)
        {if (!$cmd instanceof CommandInfo) continue;
            \hat();
            if ($cmd->GetOwner() == $this->loadedPlugins[$name])
            {
                $this->main->commandManager->UnregisterPrivateCommand($cmd->GetCommandName(), $cmd->GetOwner()); \hat();
            }
        }

        $commands = $this->main->commandManager->GetRegisteredConversationCommands(); \hat();
        foreach ($commands as $cmd)
        {if (!$cmd instanceof CommandInfo) continue;
            \hat();
            if ($cmd->GetOwner() == $this->loadedPlugins[$name])
            {
                $this->main->commandManager->UnregisterConversationCommand($cmd->GetCommandName(), $cmd->GetOwner()); \hat();
            }
        }
        $this->loadedPlugins[$name]->UninitializeFields($this->loadedPlugins[$name]); \hat();
        $dependences = [];
        foreach ($this->GetPlugins() as $plugName => $plugin)
        {
            $dependences = $plugin->GetDependences();
            if (in_array($name, $dependences))
            {
                cmm::w("pluginmanager.dependences.forceunloading", [$plugName, implode(", ", $dependences)]); \hat();
                $this->UnloadPlugin($plugName); \hat();
            }
        }
        \hat();
        $obj = $this->loadedPlugins[$name];
        $obj->SystemPluginDisabling(); \hat();
        $this->loadedPlugins[$name] = null;
        unset($this->loadedPlugins[$name]); \hat();
        $obj = null;
        unset($obj);
    }

    /**
     * Получить массив плагинов.
     *
     * @return array<string, Plugin> Список объектов главных классов плагинов. Ключ - имя плагина. Значение - главный класс плагина
     */
    public function GetPlugins() : array
    {
        return $this->loadedPlugins;
    }
}