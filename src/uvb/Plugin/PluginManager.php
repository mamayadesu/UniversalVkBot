<?php
declare(ticks = 1);

namespace uvb\Plugin;

use Application\Application;
use \Exception;
use Scheduler\SchedulerMaster;
use \Throwable;
use uvb\APIVersions;
use uvb\Bot;
use uvb\cmm;
use \Phar;
use uvb\Logger;
use uvb\Main;
use \RecursiveIteratorIterator;
use uvb\Models\CommandInfo;
use uvb\Models\Group;
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
     * @var array<Plugin>
     * @ignore
     */
    private array $loadedPlugins = [];

    /**
     * @var array<string>
     * @ignore
     */
    private array $unloadedPlugins = [];

    /**
     * @var array<string, string>
     * @ignore
     */
    private array $waitingForPlugins = array();

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
     * @var array<string, string[]>
     */
    private array $pluginsSettings = array();

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
        $this->LoadPluginsSettings();
        @mkdir($this->GetPathToPlugins());
    }

    /**
     * Появилось в API: 1.0
     *
     * @return array<string, string>
     */
    public function GetQueue() : array
    {
        return $this->waitingForPlugins;
    }

    /**
     * Появилось в API: 1.0
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function AddPluginToQueue(string $key, string $value) : void
    {
        if (!isset($this->waitingForPlugins[$key]))
        {
            $this->waitingForPlugins[$key] = $value;
        }
    }

    /**
     * Появилось в API: 1.0
     *
     * @param string $key
     * @return void
     */
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
     * Появилось в API: 1.0
     *
     * @param string $pathToPlugin Путь к PHAR-файлу
     * @param bool $plugins_settings_autosave Сохранять ли плагин в plugins_settings.json по умолчанию
     */
    public function LoadPlugin(string $pathToPlugin, bool $plugins_settings_autosave = true) : void
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
            
            foreach (new RecursiveIteratorIterator($p) as $file)
            {
                
                $ptp = "phar://" . $pluginDirectory . basename($pathToPlugin) . DIRECTORY_SEPARATOR;
                $ptp = str_replace("\\", "/", $ptp);
                $pathInPhar = $file->getPathName();
                $pathInPhar = str_replace("\\", "/", $pathInPhar);
                $pathInPhar = str_replace($ptp, "", $pathInPhar);
                
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
                        
                        return;
                    }
                    $this->RemovePluginFromQueue($name);
                    
                    foreach ($priorities as $priority)
                    {
                        
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
                            
                        }
                        
                    }
                    
                }
                else if (!$filePluginJsonWasFound)
                {
                    cmm::e("pluginmanager.notplugin", [$pathToPlugin]);
                    $this->RemovePluginFromQueue($name);
                    return;
                }
                
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
                
            }
            
            if (!$filePluginJsonWasFound)
            {
                cmm::e("pluginmanager.notplugin", [$pathToPlugin]);
                $this->RemovePluginFromQueue($name);
                return;
            }
            $this->LoadPluginFromSource($name, $version, $api_version, $main, $privateCommands, $conversationCommands, $dependences, $plugins_settings_autosave);
            
            foreach ($this->waitingForPlugins as $pluginName => $pathToPlugin)
            {
                
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
     * Появилось в API: 1.0
     *
     * @param string $name Имя плагина
     * @param string $version Версия плагина
     * @param string $api_version API UniversalVkBot, который плагин использует
     * @param string $main Полное имя главного классаю Главный класс обязательно должен наследовать класс \uvb\Plugin\Plugin
     * @param array<string, array<string, string>> $privateCommands Список команд для личных сообщений
     * @param array<string, array<string, string>> $conversationCommands Список команд для бесед
     * @param array<string> $dependences Список плагинов, от которых загружаемый плагин зависит
     * @param bool $plugins_settings_autosave Нужно ли сохранять плагин в plugins_settings.json
     */
    public function LoadPluginFromSource(string $name, string $version, string $api_version, string $main, array $privateCommands, array $conversationCommands, array $dependences, bool $plugins_settings_autosave = true) : void
    {
        
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
        try
        {
            $plugin = new $main($name, $version, $api_version, $dependences, $this->main->bot, new Logger($name, $this->sl));
        }
        catch (Exception $ex)
        {
            cmm::e("pluginmanager.erroroccured", [$name, $ex->getMessage()]);
            CrashHandler::Handle($ex);
            return;
        }
        if (!$plugin instanceof Plugin)
        {
            cmm::e("pluginmanager.wrongmain", [$name]);
            return;
        }
        
        $plugin->__declareUninitializer($plugin, $main);
        foreach ($privateCommands as $cmd => $cmdInfo)
        {
            $this->main->commandManager->RegisterPrivateCommand(new CommandInfo($cmd, "[" . $name . "] " . $cmdInfo["description"], $cmdInfo["allowed_for_users"], $plugin)); 
        }
        
        foreach ($conversationCommands as $cmd => $cmdInfo)
        {
            $this->main->commandManager->RegisterConversationCommand(new CommandInfo($cmd, "[" . $name . "] " . $cmdInfo["description"], $cmdInfo["allowed_for_users"], $plugin)); 
        }
        
        $this->loadedPlugins[$name] = $plugin;
        $this->RemovePluginFromQueue($name);
        
        try
        {
            $plugin->OnEnable();
        }
        catch (Throwable $e)
        {
            cmm::c("pluginmanager.erroroccured", [$name, $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()]); 
            CrashHandler::Handle($e, $plugin);
            $plugin->DisablePlugin(); 
            return;
        }

        $settings_changed = false;
        if (!isset($this->pluginsSettings[$name]))
        {
            $this->pluginsSettings[$name] = [];
            $settings_changed = true;
        }

        if (count($this->pluginsSettings[$name]) == 0)
        {
            cmm::l("pluginmanager.loadedforallgroups", [$name]);
        }
        else
        {
            /** @var Group[] $groups */$groups = [];
            foreach ($this->pluginsSettings[$name] as $club)
            {
                $club_id = intval(str_replace("club", "", $club));
                $groups[] = Group::Get($club_id);
            }

            $groups_names = [];
            foreach ($groups as $group)
            {
                $groups_names[] = $group->GetName();
            }

            cmm::l("pluginmanager.loadedfornextgroups", [$name, implode(", ", $groups_names)]);
        }

        if ($settings_changed && $plugins_settings_autosave)
        {
            $this->SavePluginsSettings();
        }
    }

    /**
     * Загружает плагины из папки `/plugins`
     *
     * Появилось в API: 1.0
     */
    public function LoadPlugins() : void
    {
        foreach (glob($this->GetPathToPlugins() . "*.phar") as $pharPath)
        {
            cmm::l("pluginmanager.found", [$pharPath]);
            $this->LoadPlugin($pharPath, false);
        }

        $this->SavePluginsSettings();
    }

    /**
     * Получить путь к плагинам
     *
     * Появилось в API: 1.0
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
     * Появилось в API: 1.0
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
     * Появилось в API: 1.0
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
     * Появилось в API: 1.0
     *
     * @param string $name Имя загруженного плагина
     * @param bool $printErrors TRUE - будут выведены ошибки (если они есть) в консоль
     */
    public function UnloadPlugin(string $name, bool $printErrors = true) : void
    {
        if (!isset($this->loadedPlugins[$name]) || !$this->loadedPlugins[$name] instanceof Plugin)
        {
            if ($printErrors)
            {
                cmm::e("pluginmanager.getplugin.notloaded", [$name]);
            }
            return;
        }
        cmm::l("pluginmanager.stoppingplugin", [$name]);
        $plugin = $this->loadedPlugins[$name];

        foreach (SchedulerMaster::GetActiveTasks() as $Task)
        {
            if (get_class($Task->GetThis()) == get_class($plugin))
                $Task->Cancel();
        }

        try
        {
            $plugin->OnDisable();
        }
        catch (Throwable $e)
        {
            cmm::c("pluginmanager.disableerroroccured", [$name, $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()]);
            CrashHandler::Handle($e, $this->loadedPlugins[$name]);
        }

        if (!in_array($name, $this->unloadedPlugins))
        {
            $this->unloadedPlugins[] = $name;
        }

        $commands = $this->main->commandManager->GetRegisteredPrivateCommands();
        foreach ($commands as $cmd)
        {if (!$cmd instanceof CommandInfo) continue;
            if ($cmd->GetOwner() === $plugin)
            {
                $this->main->commandManager->UnregisterPrivateCommand($cmd->GetCommandName(), $cmd->GetOwner()); 
            }
        }

        $commands = $this->main->commandManager->GetRegisteredConversationCommands(); 
        foreach ($commands as $cmd)
        {if (!$cmd instanceof CommandInfo) continue;
            
            if ($cmd->GetOwner() === $plugin)
            {
                $this->main->commandManager->UnregisterConversationCommand($cmd->GetCommandName(), $cmd->GetOwner()); 
            }
        }
        $this->loadedPlugins[$name]->UninitializeFields($this->loadedPlugins[$name]); 
        $dependences = [];
        foreach ($this->GetPlugins() as $plugName => $p)
        {
            $dependences = $p->GetDependences();
            if (in_array($name, $dependences))
            {
                cmm::w("pluginmanager.dependences.forceunloading", [$plugName, implode(", ", $dependences)]); 
                $this->UnloadPlugin($plugName); 
            }
        }
        $plugin->SystemPluginDisabling();
        $this->loadedPlugins[$name] = null;
        unset($this->loadedPlugins[$name]);
        $plugin = null;
        unset($obj);
    }

    /**
     * Получить массив плагинов.
     *
     * Появилось в API: 1.0
     *
     * @return array<string, Plugin> Список объектов главных классов плагинов. Ключ - имя плагина. Значение - главный класс плагина
     */
    public function GetPlugins() : array
    {
        return $this->loadedPlugins;
    }

    /**
     * @ignore
     */
    private function ResetPluginsSettings(string $path_to_file) : void
    {
        cmm::e("pluginssettings.invalid_file");
        $f = fopen($path_to_file, "w+");
        fwrite($f, json_encode(array(), JSON_PRETTY_PRINT));
        fclose($f);
        $this->pluginsSettings = array();
    }

    /**
     * @ignore
     */
    private function LoadPluginsSettings() : void
    {
        $path_to_file = Application::GetExecutableDirectory() . "plugins_settings.json";

        if (!file_exists($path_to_file))
        {
            $this->ResetPluginsSettings($path_to_file);
        }
        else
        {
            $settings = @json_decode(file_get_contents($path_to_file), true);
            $somethingWrong = false;
            if ($settings === null)
            {
                $this->ResetPluginsSettings($path_to_file);
                $somethingWrong = true;
            }
            else
            {
                foreach ($settings as $pluginname => $clubs_list)
                {
                    if (!is_string($pluginname) || !is_array($clubs_list))
                    {
                        $this->ResetPluginsSettings($path_to_file);
                        $somethingWrong = true;
                    }
                    else
                    {
                        foreach ($clubs_list as $club)
                        {
                            if (strlen($club) <= 4)
                            {
                                $this->ResetPluginsSettings($path_to_file);
                                $somethingWrong = true;
                            }
                            else
                            {
                                if (substr($club, 0, 4) != "club")
                                {
                                    $this->ResetPluginsSettings($path_to_file);
                                    $somethingWrong = true;
                                }
                                else
                                {
                                    $club_id = intval(substr($club, 4));
                                    if ($club_id <= 0)
                                    {
                                        $this->ResetPluginsSettings($path_to_file);
                                        $somethingWrong = true;
                                    }
                                }
                            }
                        }
                    }
                }

                if (!$somethingWrong)
                {
                    $this->pluginsSettings = $settings;
                }
            }
        }
    }

    /**
     * Получить настройки plugins_settings.json
     *
     * Появилось в API: 1.0
     *
     * @return array<string, string[]>
     */
    public function GetPluginsSettings() : array
    {
        return $this->pluginsSettings;
    }

    /**
     * Сохранить настройки plugins_settings.json
     *
     * Появилось в API: 1.0
     *
     * @return void
     */
    public function SavePluginsSettings() : void
    {
        $f = fopen(Application::GetExecutableDirectory() . "plugins_settings.json", "w+");
        fwrite($f, json_encode($this->pluginsSettings, JSON_PRETTY_PRINT));
        fclose($f);
    }
}