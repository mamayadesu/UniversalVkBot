<?php

namespace uvb;

use IO\Console;
use uvb\Events\CommandPreProcessEvent;
use uvb\Models\Command;
use uvb\Models\User;
use uvb\Plugin\CommandManager;
use uvb\Plugin\PluginManager;
use uvb\Protection\AddressBlocker;
use uvb\Repositories\UserRepository;
use uvb\Services\UserCache;
use uvb\System\ExitCode;

/**
 * Класс, предоставляющий API ядра для работы с ботом
 * @package uvb
 */

class Bot
{
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
    private AddressBlocker $addressBlocker;

    /**
     * @ignore
     */
    private bool $isShuttingDown = false;

    /**
     * @ignore
     */
    public function __construct(Main $main, Logger $logger, Logger $clogger, SystemLogger $sl)
    {
        if (self::$instance != null)
        {
            throw new \Exception("Bot is already initialized");
        }
        $this->main = $main;
        $this->logger = $logger;
        $this->clogger = $clogger;
        $this->sl = $sl;
        $this->addressBlocker = new AddressBlocker($this->main);
        $this->addressBlocker->Load();
        self::$instance = $this;
    }

    /**
     * Доступ к системным функциям бота
     *
     * @return Bot
     */
    public static function GetInstance() : Bot
    {
        return self::$instance;
    }

    /**
     * Доступ к сервису контроля IP-адресов
     *
     * @return AddressBlocker
     */
    public function GetAddressBlocker() : AddressBlocker
    {
        return $this->addressBlocker;
    }

    /**
     * Получить пользователя по его идентификатору
     *
     * @param int $vkId Идентификатор пользователя
     * @return User Объект пользователя
     */
    public function GetUser(int $vkId) : User
    {
        return UserRepository::Get($vkId);
    }

    /**
     * Получить пользователей по их идентификаторам
     *
     * @param array<int> $vkIds Список идентификаторов пользователей. В массиве могут быть только целые числа
     * @return array<User> Объекты пользователей
     */
    public function GetUsers(array $vkIds) : array
    {
        return UserRepository::GetUsers($vkIds);
    }

    /**
     * Доступ к менеджеру плагинов
     *
     * @return PluginManager Менеджер плагинов
     */
    public function GetPluginManager() : PluginManager
    {
        return $this->main->pluginManager;
    }

    /**
     * Доступ к менеджеру команд
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
     * @return UserCache Сервис кэширования пользователей
     */
    public function GetUserCache() : UserCache
    {
        return $this->main->userCache;
    }

    /**
     * Получить логгер ядра бота
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
     * Выполняет команду от лица какого-либо пользователя или консоли
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
        $command = new Command($commandName, $args, $user, 0);
        $_event = new CommandPreProcessEvent($command, true, 0);
        $this->main->newMessage->OnCommandPreProcess($_event);
        if (!$_event->IsCancelled())
        {
            $this->main->commandHandler->OnCommand($command);
        }
    }

    /**
     * Возвращает TRUE, если бот в данный момент выключается
     *
     * @return bool Выключается ли бот в данный момент
     */
    public function IsShuttingDown() : bool
    {
        return $this->isShuttingDown;
    }

    /**
     * Завершить работу бота
     */
    public function Shutdown() : void
    {
        $this->main->exitCode->Set(0);
        $this->ProcStopper();
    }

    /**
     * Перезагрузить бота
     */
    public function Reboot() : void
    {
        $this->main->exitCode->Set(2);
        $this->ProcStopper();
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

        $this->main->exitCode->Set(3);
        $this->ProcStopper();
    }

    private function ProcStopper() : void
    {
        if ($this->isShuttingDown)
        {
            return;
        }
        $this->isShuttingDown = true;
        $exitCode = $this->main->exitCode->Get();
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
        cmm::l("bot.pluginsshuttingdown");
        $name = "";
        $this->main->UpdateTitle();
        while (count($this->GetPluginManager()->GetPlugins()) > 0)
        {
            foreach ($this->GetPluginManager()->GetPlugins() as $key => $value)
            {
                $name = $key;
                break;
            }
            $this->main->UpdateTitle();
            $this->GetPluginManager()->UnloadPlugin($name); $this->main->UpdateTitle();
        }
        cmm::l("bot.savingconvids");
        $cids = ConversationIdsResource::$conversationIds;
        $cids->Save();

        cmm::l("bot.savingusers");
        $userCache = UserCache::GetInstance(); $this->main->UpdateTitle();
        $userCache->Save(true); $this->main->UpdateTitle();

        $this->main->KillThreads();
        // это делать в последнюю очередь
        cmm::l("bot.stoppingswoole");
        $this->main->server->shutdown(); $this->main->UpdateTitle();
    }
}