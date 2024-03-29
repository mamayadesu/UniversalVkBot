<?php
declare(ticks = 1);

namespace uvb;

use Application\Application;
use uvb\Events\CommandPreProcessEvent;
use uvb\Models\Command;
use uvb\Models\Group;
use uvb\Models\User;
use uvb\Plugin\CommandManager;
use uvb\Plugin\PluginManager;
use uvb\Services\RamController;
use uvb\Services\UserCache;
use uvb\System\SystemConfig;
use uvb\Utils\CpuUsage;
use VK\Client\VKApiClient;

/**
 * Класс, предоставляющий API ядра для работы с ботом
 * @package uvb
 */

final class Bot
{
    const REQUEST_URI = "";

    /**
     * @ignore
     */
    private Main $main;

    /**
     * @ignore
     */
    private static ?Bot $instance = null;

    /**
     * @ignore
     */
    private Logger $logger, $clogger;

    /**
     * @ignore
     */
    private SystemLogger $sl;

    /**
     * @ignore
     */
    private bool $isShuttingDown = false, $restartSupported;

    /**
     * @ignore
     */
    private string $confirmResponse = "";

    /**
     * @ignore
     */
    private float $cpu = 0;

    /**
     * @ignore
     */
    private float $lastCpuChecked = 0;

    /**
     * @ignore
     */
    public function __construct(Main $main, Logger $logger, Logger $clogger, SystemLogger $sl, bool $restartSupported)
    {
        if (self::$instance != null)
        {
            throw new \Exception("Bot is already initialized");
        }
        $this->main = $main;
        $this->logger = $logger;
        $this->clogger = $clogger;
        $this->sl = $sl;
        $this->restartSupported = $restartSupported;
        Admins::Initialize();
        self::$instance = $this;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return float Процент использования CPU. Работает только Linux-системах. Для Windows этот метод всегда возвращает ноль.
     */
    public function GetCpuUsage() : float
    {
        return CpuUsage::GetValue();
    }

    /**
     * Делает то же самое, что и `GetCpuUsage`, но возвращает значение в виде отформатированной строки
     *
     * Появилось в API: 1.0
     *
     * @return string
     */
    public function GetCpuUsageAsString() : string
    {
        $value = CpuUsage::GetValue() . '';
        if (count(explode('.', $value)) == 1)
        {
            $value .= '.0';
        }
        return $value;
    }

    /**
     * Служба контроля потребления оперативной памяти
     *
     * Появилось в API: 1.0
     *
     * @return RamController
     */
    public function GetRamController() : RamController
    {
        return $this->main->ramController;
    }

    /**
     * Доступ к системным функциям бота
     *
     * Появилось в API: 1.0
     *
     * @return Bot
     */
    public static function GetInstance() : ?Bot
    {
        return self::$instance;
    }

    /**
     * Устанавливает код подтверждения Callback API
     *
     * Появилось в API: 1.0
     *
     * @param string $text
     * @return void
     */
    public function SetConfirmResponse(string $text) : void
    {
        $this->confirmResponse = $text;
    }

    /**
     * Возвращает установленный код подтверждения Callback API
     *
     * Появилось в API: 1.0
     *
     * @return string
     */
    public function GetConfirmResponse() : string
    {
        return $this->confirmResponse;
    }

    /**
     * Получить пользователя по его идентификатору
     *
     * Появилось в API: 1.0
     *
     * @param int $vkId Идентификатор пользователя
     * @return User|null Объект пользователя
     */
    public function GetUser(int $vkId) : ?User
    {
        return User::Get($vkId);
    }

    /**
     * Получить пользователей по их идентификаторам
     *
     * Появилось в API: 1.0
     *
     * @param array<int> $vkIds Список идентификаторов пользователей. В массиве могут быть только целые числа
     * @return array<User> Объекты пользователей
     */
    public function GetUsers(array $vkIds) : array
    {
        return User::GetUsers($vkIds);
    }

    /**
     * Доступ к менеджеру плагинов
     *
     * Появилось в API: 1.0
     *
     * @return PluginManager Менеджер плагинов
     */
    public function GetPluginManager() : ?PluginManager
    {
        return $this->main->pluginManager;
    }

    /**
     * Доступ к менеджеру команд
     *
     * Появилось в API: 1.0
     *
     * @return CommandManager Менеджер команд
     */
    public function GetCommandManager() : CommandManager
    {
        return $this->main->commandManager;
    }

    /**
     * Доступ к сервису кэширования пользователей
     *
     * Появилось в API: 1.0
     *
     * @return UserCache Сервис кэширования пользователей
     */
    public function GetUserCache() : UserCache
    {
        return $this->main->userCache;
    }

    /**
     * Получить логгер ядра бота
     *
     * Появилось в API: 1.0
     *
     * @return Logger Логгер бота
     */
    public function GetLogger() : Logger
    {
        return $this->logger;
    }

    /**
     * @ignore
     */
    public function __gcl() : Logger
    {
        return $this->clogger;
    }

    /**
     * @return VKApiClient
     */
    public static function GetVkApi() : VKApiClient
    {
        return self::$instance->main->api;
    }

    /**
     * Выполняет команду от лица какого-либо пользователя или консоли
     *
     * Появилось в API: 1.0
     *
     * @param User $user Пользователей, от лица которого будет выполнена команда
     * @param string $command Команда и её аргументы
     */
    public function DispatchPrivateCommand(User $user, string $command) : void
    {
        $scommand = explode(' ', $command);
        $commandName = $scommand[0];
        array_shift($scommand);
        $args = [];
        foreach ($scommand as $arg)
        {
            $args[] = $arg;
        }
        $group = $this->GetDefaultGroup();
        $command = new Command($commandName, $args, $user, null, null);
        $_event = new CommandPreProcessEvent($group, $command, true, null);
        $this->main->newMessage->OnCommandPreProcess($_event);
        if (!$_event->IsCancelled())
        {
            $this->main->commandHandler->OnCommand($command, $group);
        }
    }

    /**
     * Получить группу по умолчанию. Группа по умолчанию - это первая в списке группа в config.json в параметре groups_to_access_tokens
     *
     * Появилось в API: 1.0
     *
     * @return Group
     */
    public function GetDefaultGroup() : Group
    {
        $groups = SystemConfig::Get("groups_to_access_tokens");

        $group_id = 0;
        foreach ($groups as $group => $access_token)
        {
            $group_id = intval(str_replace("club", "", $group));
            break;
        }

        return Group::Get($group_id);
    }

    /**
     * Возвращает TRUE, если бот в данный момент выключается
     *
     * Появилось в API: 1.0
     *
     * @return bool Выключается ли бот в данный момент
     */
    public function IsShuttingDown() : bool
    {
        return $this->isShuttingDown;
    }

    /**
     * Завершить работу бота
     *
     * Появилось в API: 1.0
     *
     * @return void
     */
    public function Shutdown() : void
    {
        if ($this->main->exitCode != 255)
        {
            $this->main->exitCode = 0;
        }
        $this->ProcStopper();
    }

    /**
     * Перезагрузить бота
     *
     * Появилось в API: 1.0
     *
     * @return void
     */
    public function Reboot() : void
    {
        if (!$this->restartSupported)
        {
            cmm::w("main.restart_is_not_supported");
            return;
        }
        $this->main->exitCode = 2;
        $this->ProcStopper();
    }

    /**
     * Появилось в API: 1.0
     *
     * @return bool Поддерживается ли перезапуск бота
     */
    public function IsRestartSupported() : bool
    {
        return $this->restartSupported;
    }

    /**
     * @ignore
     */
    public function InstallUpdate() : void
    {
        if (!$this->main->updater->IsReadyToInstall())
        {
            return;
        }

        $this->main->exitCode = 3;
        $this->ProcStopper();
    }

    /**
     * @ignore
     */
    private function ProcStopper() : void
    {
        if ($this->isShuttingDown)
        {
            return;
        }
        $this->isShuttingDown = true;
        if ($this->main->resourcesWatcher !== null)
        {
            $this->main->resourcesWatcher->ShutdownTasks();
        }
        $exitCode = $this->main->exitCode;
        if ($exitCode == 0)
        {
            cmm::l("bot.shuttingdown");
        }
        else if ($exitCode == 2)
        {
            cmm::l("bot.restarting");
        }
        else if ($exitCode == 3)
        {
            cmm::l("bot.updating");
        }
        $pluginManager = $this->GetPluginManager();

        if ($pluginManager != null)
        {
            cmm::l("bot.pluginsshuttingdown");
            $name = "";
            
            while (count($pluginManager->GetPlugins()) > 0)
            {
                foreach ($pluginManager->GetPlugins() as $key => $value)
                {
                    $name = $key;
                    break;
                }
                
                $pluginManager->UnloadPlugin($name); 
            }
        }
        $cids = ConversationIdsResource::$conversationIds;
        if ($cids != null)
        {
            cmm::l("bot.savingconvids");
            $cids->Save();
        }

        $userCache = UserCache::GetInstance(); 
        if ($userCache != null)
        {
            cmm::l("bot.savingusers");
            $userCache->Save(true); 
        }

        $this->main->KillThreads();
        // это делать в последнюю очередь

        if ($this->main->server != null)
        {
            cmm::l("bot.stoppinghttp");
            $this->main->server->Shutdown(); 
            if ($exitCode == 3)
            {
                $exitCode = 2;
                $this->main->InstallUpdate();
            }
            if ($exitCode == 2)
            {
                sleep(3);
            }
        }
        else
        {
            exit(0);
        }
    }
}