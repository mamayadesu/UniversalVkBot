<?php
declare(ticks = 1);

namespace uvb\Plugin;

use Application\Application;
use \Exception;
use uvb\Bot;
use uvb\Events\CommandPreProcessEvent;
use uvb\Events\InGroupUserAction\UserJoinGroupEvent;
use uvb\Events\InGroupUserAction\UserLeftGroupEvent;
use uvb\Events\Messages\BotJoinEvent;
use uvb\Events\Messages\BotLeftEvent;
use uvb\Events\Messages\UserAddEvent;
use uvb\Events\Messages\UserJoinEvent;
use uvb\Events\Messages\UserKickEvent;
use uvb\Events\Messages\UserLeftEvent;
use uvb\Events\Messages\NewPrivateMessageEvent;
use uvb\Events\Messages\NewConversationMessageEvent;
use uvb\Events\ServerRequestEvent;
use uvb\Events\UnregisteredVkEvent;
use uvb\Events\Wall\NewCommentEvent;
use uvb\Events\Wall\NewPostEvent;
use uvb\Logger;
use uvb\Models\Command;
use uvb\Models\Conversation;
use uvb\Models\Group;

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
    final function __construct(string $name, string $version, string $api_version, array $dependences, Bot $bot, Logger $logger)
    {
        $this->name = $name;
        $this->version = $version;
        $this->api_version = $api_version;
        $this->dependences = $dependences;
        $this->bot = $bot;
        $this->logger = $logger;
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
     * Появилось в API: 1.0
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
     * Появилось в API: 1.0
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
     * Появилось в API: 1.0
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
     * Появилось в API: 1.0
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
     * Появилось в API: 1.0
     *
     * @return array<string> Список плагинов, от которых данный плагин зависит
     */
    final public function GetDependences() : array
    {
        return $this->dependences;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return bool Запущен ли плагин
     */
    final public function IsRunning() : bool
    {
        return $this->is_running;
    }

    /**
     * Получить путь к папке, которую плагин использует для хранения своих данных (файлы конфигурации плагина и прочее)
     *
     * Появилось в API: 1.0
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
     * Появилось в API: 1.0
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
     * Появилось в API: 1.0
     *
     * @return Logger Логгер плагина
     */
    final protected function GetLogger() : Logger
    {
        return $this->logger;
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
        $this->is_running = false;
    }

    /**
     * Завершить работу плагина
     *
     * Появилось в API: 1.0
     *
     * @return void
     */
    final function DisablePlugin() : void
    {
        $this->bot->GetPluginManager()->UnloadPlugin($this->name);
    }

    /**
     * Метод вызывается менеджером плагинов при запуске плагина. Аналог метода `__construct`. Может быть переопределён плагином
     *
     * Появилось в API: 1.0
     *
     * @return void
     */
    public function OnEnable() : void
    {

    }

    /**
     * Метод вызывается при завершении работы плагина. Аналог метода `__destruct`. Может быть переопределён плагином
     *
     * Появилось в API: 1.0
     *
     * @return void
     */
    public function OnDisable() : void
    {

    }

    /**
     * Событие. Новое входящее личное сообщение.
     * Неотменяемое
     *
     * Появилось в API: 1.0
     *
     * @param NewPrivateMessageEvent $event Объект, описывающий событие
     * @return void
     */
    public function OnNewPrivateMessage(NewPrivateMessageEvent $event) : void
    {

    }

    /**
     * Событие. Новое входящее сообщение в беседу.
     * Отменяемое
     *
     * Появилось в API: 1.0
     *
     * @param NewConversationMessageEvent $event Объект, описывающий событие
     * @return void
     */
    public function OnNewConversationMessage(NewConversationMessageEvent $event) : void
    {

    }

    /**
     * Событие. Пользователь был добавлен в беседу.
     * Неотменяемое
     *
     * Появилось в API: 1.0
     *
     * @param UserAddEvent $event Объект, описывающий событие
     * @return void
     */
    public function OnUserAdd(UserAddEvent $event) : void
    {

    }

    /**
     * Событие. Пользователь присоединился к беседе по ссылке.
     * Неотменяемое
     *
     * Появилось в API: 1.0
     *
     * @param UserJoinEvent $event Объект, описывающий событие
     * @return void
     */
    public function OnUserJoin(UserJoinEvent $event) : void
    {

    }

    /**
     * Событие. Пользователь покинул беседу.
     * Неотменяемое
     *
     * Появилось в API: 1.0
     *
     * @param UserLeftEvent $event Объект, описывающий событие
     * @return void
     */
    public function OnUserLeft(UserLeftEvent $event) : void
    {

    }

    /**
     * Событие. Пользователь был исключён из беседы.
     * Неотменяемое
     *
     * Появилось в API: 1.0
     *
     * @param UserKickEvent $event Объект, описывающий событие
     * @return void
     */
    public function OnUserKick(UserKickEvent $event) : void
    {

    }

    /**
     * Событие. Бот был добавлен в беседу.
     * Неотменяемое
     *
     * Появилось в API: 1.0
     *
     * @param BotJoinEvent $event Объект, описывающий событие
     * @return void
     */
    public function OnBotJoin(BotJoinEvent $event) : void
    {

    }

    /**
     * Событие. Бот был исключён из беседы.
     * Неотменяемое
     *
     * Появилось в API: 1.0
     *
     * @param BotLeftEvent $event Объект, описывающий событие
     * @return void
     */
    public function OnBotLeft(BotLeftEvent $event) : void
    {

    }

    /**
     * Событие. Предварительна обработка команды перед её выполнением.
     * Отменяемое
     *
     * Появилось в API: 1.0
     *
     * @param CommandPreProcessEvent $event Объект, описывающий событие
     * @return void
     */
    public function OnCommandPreProcess(CommandPreProcessEvent $event) : void
    {

    }

    /**
     * Данный метод вызывает обработчик команд при вводе команды в личные сообщения боту или в консоль.
     * Обработчик команд вызывает данный метод плагина только в том случае, если введённая команда прописана в `plugin.json` в массиве `private_commands`.
     * Поэтому если в плагине всего одна команда, в теле метода необязательно уточнять какая именно команда была введена
     *
     * Появилось в API: 1.0
     *
     * @param Command $cmd Объект, описывающий введённую команду
     * @param Group $group Группа, к которой принадлежит событие
     * @return void
     */
    public function OnCommand(Command $cmd, Group $group) : void
    {

    }

    /**
     * Данный метод вызывает обработчик команд при вводе команды в беседу, в которой состоит бот
     * Обработчик команд вызывает данный метод плагина только в том случае, если введённая команда прописана в `plugin.json` в массиве `conversation_commands`.
     * Поэтому если в плагине всего одна команда, в теле метода необязательно уточнять какая именно команда была введена
     *
     * Появилось в API: 1.0
     *
     * @param Command $cmd Объект, описывающий исполняемую команду с её аргументами
     * @param Conversation $conversation Объект беседы
     * @param Group $group Группа, к которой принадлежит событие
     * @return void
     */
    public function OnConversationCommand(Command $cmd, Conversation $conversation, Group $group) : void
    {

    }

    /**
     * Событие. Пользователь присоединился к сообществу или отправил заявку на вступление в сообщество или заявка была одобрена/отклонена.
     * Отменяемое
     *
     * Появилось в API: 1.0
     *
     * @param UserJoinGroupEvent $event Объект, описывающий событие
     * @return void
     */
    public function OnUserJoinGroup(UserJoinGroupEvent $event) : void
    {

    }

    /**
     * Событие. Пользователь покинул сообщество или был исключён.
     * Неотменяемое
     *
     * Появилось в API: 1.0
     *
     * @param UserLeftGroupEvent $event Объект, описывающий событие
     * @return void
     */
    public function OnUserLeftGroup(UserLeftGroupEvent $event) : void
    {

    }

    /**
     * Событие. Новая запись на стене сообщества
     * Отменяемое
     *
     * Появилось в API: 1.0
     *
     * @param NewPostEvent $event
     * @return void
     */
    public function OnNewPost(NewPostEvent $event) : void
    {

    }

    /**
     * Событие. Новый комментарий на стене сообщества
     * Отменяемое
     *
     * Появилось в API: 1.0
     *
     * @param NewCommentEvent $event
     * @return void
     */
    public function OnNewComment(NewCommentEvent $event) : void
    {

    }

    /**
     * Событие. Незарегистрированное.
     * Неотменяемое
     *
     * Появилось в API: 1.0
     *
     * @param UnregisteredVkEvent $event Объект, описывающий событие
     */
    public function OnUnregistered(UnregisteredVkEvent $event) : void
    {

    }

    /**
     * Событие. Запрос на сервер
     * Неотменяемое
     *
     * @param ServerRequestEvent $event
     * @return void
     */
    public function OnServerRequest(ServerRequestEvent $event) : void
    {

    }

    /**
     * Включён ли плагин для указанной группы
     *
     * Появилось в API: 1.0
     *
     * @param Group $group
     * @return bool
     */
    public function IsEnabledForGroup(Group $group) : bool
    {
        $settings = $this->GetBot()->GetPluginManager()->GetPluginsSettings();

        if (!isset($settings[$this->GetPluginName()]) || count($settings[$this->GetPluginName()]) == 0)
        {
            return true;
        }

        return in_array("club" . (-$group->GetVkId()), $settings[$this->GetPluginName()]);
    }
}