<?php

namespace uvb\Plugin;

use Application\Application;
use \Exception;
use uvb\Bot;
use uvb\Events\CommandPreProcessEvent;
use uvb\Events\InGroupUserAction\UserJoinGroupEvent;
use uvb\Events\InGroupUserAction\UserLeftGroupEvent;
use uvb\Events\Messages\BotJoinEvent;
use uvb\Events\Messages\UserAddEvent;
use uvb\Events\Messages\UserJoinEvent;
use uvb\Events\Messages\UserKickEvent;
use uvb\Events\Messages\UserLeftEvent;
use uvb\Events\Messages\NewPrivateMessageEvent;
use uvb\Events\Messages\NewConversationMessageEvent;
use uvb\Events\UnregisteredVkEvent;
use uvb\Logger;
use uvb\Models\Command;
use uvb\Services\Scheduler\Scheduler;
use uvb\Services\Scheduler\AsyncTask;

/**
 * Данный класс описывает запущенный плагин, содержит в себе набор необходимых методов для API и является абстрактным.
 * Главные классы каждого плагина должны наследовать этот класс.
 * @package uvb\Plugin
 *
 *
 */

abstract class Plugin
{
    /**
     * @ignore
     */
    private string $name, $version, $api_version;

    /**
     * @ignore
     */
    private bool $is_running = true;

    /**
     * @ignore
     */
    private Bot $bot;

    /**
     * @ignore
     */
    private ?Logger $logger = null;

    /**
     * @ignore
     */
    private ?Scheduler $scheduler;

    /**
     * @ignore
     */
    private static ?Plugin $instance;

    /**
     * @ignore
     */
    private array $dependences = [];

    /**
     * @ignore
     */
    protected $__uf = null;

    /**
     * @ignore
     */
    final function __construct(string $name, string $version, string $api_version, array $dependences, Bot $bot, Logger $logger, Scheduler $scheduler)
    {
        $this->name = $name;
        $this->version = $version;
        $this->api_version = $api_version;
        $this->dependences = $dependences;
        $this->bot = $bot;
        $this->logger = $logger;
        $this->scheduler = $scheduler;
        if ($name != "system")
        {
            self::$instance = $this;
        }
        else
        {
            self::$instance = null;
        }
    }

    /**
     * Получить экземпляр класса
     *
     * @return Plugin|null Экземпляр главного класса плагина
     */
    final public static function GetInstance() : ?Plugin
    {
        return self::$instance;
    }

    /**
     * Получить название плагина
     *
     * @return string Название плагина
     */
    final public function GetPluginName() : string
    {
        return $this->name;
    }

    /**
     * Получить версию плагина
     *
     * @return string Версия плагина
     */
    final public function GetVersion() : string
    {
        return $this->version;
    }

    /**
     * Получить версию API, которую данный плагин использует
     *
     * @return string Версия API, которую данный плагин использует
     */
    final public function GetAPIVersion() : string
    {
        return $this->api_version;
    }

    /**
     * Получить список плагинов, от которых данный плагин зависит
     *
     * @return array<string> Список плагинов, от которых данный плагин зависит
     */
    final public function GetDependences() : array
    {
        return $this->dependences;
    }

    /**
     * @return bool Запущен ли плагин
     */
    final public function IsRunning() : bool
    {
        return $this->is_running;
    }

    /**
     * Получить путь к папке, которую плагин использует для хранения своих данных (файлы конфигурации плагина и прочее
     *
     * @return string Путь к папке
     */
    final protected function GetDataFolder() : string
    {
        return Application::GetExecutableDirectory() . DIRECTORY_SEPARATOR . "plugins" . DIRECTORY_SEPARATOR . $this->name . DIRECTORY_SEPARATOR;
    }

    /**
     * Получить доступ к системным функциям бота
     *
     * @return Bot
     */
    final protected function GetBot() : Bot
    {
        return $this->bot;
    }

    /**
     * Получить логгер плагина
     *
     * @return Logger Логгер плагина
     */
    final protected function GetLogger() : Logger
    {
        return $this->logger;
    }

    /**
     * Получить планировщик задач для этого плагина
     *
     * @return Scheduler
     */
    final protected function GetScheduler() : Scheduler
    {
        return $this->scheduler;
    }

    /**
     * @ignore
     */
    final function __declareUninitializer($obj, $className) : void
    {
        $func = function($obj)
        {
            $vars = get_object_vars($obj);
            foreach ($vars as $var => $value)
            {
                unset($obj->{$var});
            }
        };
        if ($this->__uf == null)
        {
            $this->__uf = \Closure::bind($func, $obj, $className);
        }
    }

    /**
     * @ignore
     */
    final public function __call($method, $args)
    {
        if (!$this->is_running)
        {
            throw new Exception("Plugin is not running");
        }
        switch ($method)
        {
            case "UninitializeFields":
                call_user_func_array($this->__uf, $args);
                break;
        }
    }

    /**
     * @ignore
     */
    final function SystemPluginDisabling() : void
    {
        if (!$this->GetBot()->GetPluginManager()->IsPluginLoaded($this->GetPluginName()))
        {
            return;
        }
        self::$instance = null;
        unset($this->logger);
        foreach ($this->scheduler->GetTasks() as $TaskId => $Task)
        {if(!$Task instanceof AsyncTask)continue;
            $Task->Cancel();
        }
        $this->scheduler->__dispose();
        unset($this->scheduler);
        $this->is_running = false;
    }

    /**
     * Завершить работу текущего плагина
     */
    final function DisablePlugin() : void
    {
        $this->bot->GetPluginManager()->UnloadPlugin($this->name);
    }

    /**
     * Метод вызывается менеджером плагинов при запуске плагина. Аналог метода `__construct`. Может быть переопределён плагином
     */
    public function OnEnable() : void
    {

    }

    /**
     * Метод вызывается при завершении работы плагина. Аналог метода `__destruct`. Может быть переопределён плагином
     */
    public function OnDisable() : void
    {

    }

    /**
     * Событие. Новое входящее личное сообщение.
     * Неотменяемое
     *
     * @param NewPrivateMessageEvent $event Объект, описывающий событие
     */
    public function OnNewPrivateMessage(NewPrivateMessageEvent $event) : void
    {

    }

    /**
     * Событие. Новое входящее сообщение в беседу.
     *
     * @param NewConversationMessageEvent $event Объект, описывающий событие
     */
    public function OnNewConversationMessage(NewConversationMessageEvent $event) : void
    {

    }

    /**
     * Событие. Пользователь был добавлен в беседу.
     * Неотменяемое
     *
     * @param UserAddEvent $event Объект, описывающий событие
     */
    public function OnUserAdd(UserAddEvent $event) : void
    {

    }

    /**
     * Событие. Пользователь присоединился к беседе по ссылке.
     * Неотменяемое
     *
     * @param UserJoinEvent $event Объект, описывающий событие
     */
    public function OnUserJoin(UserJoinEvent $event)
    {

    }

    /**
     * Событие. Пользователь покинул беседу.
     * Неотменяемое
     *
     * @param UserLeftEvent $event Объект, описывающий событие
     */
    public function OnUserLeft(UserLeftEvent $event) : void
    {

    }

    /**
     * Событие. Пользователь был исключён из беседы.
     * Неотменяемое
     *
     * @param UserKickEvent $event Объект, описывающий событие
     */
    public function OnUserKick(UserKickEvent $event) : void
    {

    }

    /**
     * Событие. Бот был добавлен в беседу.
     * Неотменяемое
     *
     * @param BotJoinEvent $event Объект, описывающий событие
     */
    public function OnBotJoin(BotJoinEvent $event) : void
    {

    }

    /**
     * Событие. Предварительна обработка команды перед её выполнением.
     * Отменяемое
     *
     * @param CommandPreProcessEvent $event Объект, описывающий событие
     */
    public function OnCommandPreProcess(CommandPreProcessEvent $event)
    {

    }

    /**
     * Данный метод вызывает обработчик команд при вводе команды в личные сообщения боту или в консоль.
     * Обработчик команд вызывает данный метод плагина только в том случае, если введённая команда прописана в `plugin.json` в массиве `private_commands`.
     * Поэтому если в плагине всего одна команда, в теле метода необязательно уточнять какая именно команда была введена
     *
     * @param Command $cmd Объект, описывающий введённую команду
     */
    public function OnCommand(Command $cmd) : void
    {

    }

    /**
     * Данный метод вызывает обработчик команд при вводе команды в беседу, в которой состоит бот
     * Обработчик команд вызывает данный метод плагина только в том случае, если введённая команда прописана в `plugin.json` в массиве `conversation_commands`.
     * Поэтому если в плагине всего одна команда, в теле метода необязательно уточнять какая именно команда была введена
     *
     * @param Command $cmd Объект, описывающий исполняемую команду с её аргументами
     * @param int $conversationId Идентификатор беседы
     */
    public function OnConversationCommand(Command $cmd, int $conversationId) : void
    {

    }

    /**
     * Событие. Пользователь присоединился к сообществу или отправил заявку на вступление в сообщество или заявка была одобрена/отклонена.
     * Отменяемое
     *
     * @param UserJoinGroupEvent $event Объект, описывающий событие
     */
    public function OnUserJoinGroup(UserJoinGroupEvent $event)
    {

    }

    /**
     * Событие. Пользователь покинул сообщество или был исключён.
     * Неотменяемое
     *
     * @param UserLeftGroupEvent $event Объект, описывающий событие
     */
    public function OnUserLeftGroup(UserLeftGroupEvent $event)
    {

    }

    /**
     * Событие. Незарегистрированное.
     *
     * @param UnregisteredVkEvent $event Объект, описывающий событие
     */
    public function OnUnregistered(UnregisteredVkEvent $event)
    {

    }
}