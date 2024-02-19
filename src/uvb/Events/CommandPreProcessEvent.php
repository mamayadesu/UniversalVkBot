<?php
declare(ticks = 1);

namespace uvb\Events;

use uvb\Models\Command;
use uvb\Models\Conversation;
use uvb\Models\Group;

/**
 * Событие. Предварительный процесс ввода команды
 * @package uvb\Events
 */

class CommandPreProcessEvent extends Event
{
    /**
     * @ignore
     */
    private Command $command;

    /**
     * @ignore
     */
    private bool $isPrivate;

    /**
     * @ignore
     */
    private ?Conversation $conversation;

    /**
     * @ignore
     */
    public function __construct(Group $group, Command $command, bool $isPrivate, ?Conversation $conversation)
    {
        $this->command = $command;
        $this->isPrivate = $isPrivate;
        $this->conversation = $conversation;
        $this->isCancellable = true;
        parent::__construct($group);
    }

    /**
     * Получить команду
     *
     * @return Command Объект введённой команды
     */
    public function GetCommand() : Command
    {
        return $this->command;
    }

    /**
     * Отправлена ли команда в личные сообщения или в консоль
     *
     * @return bool TRUE - если команда была отправлена в личные сообщения боту либо в консоль. FALSE - в любую беседу
     */
    public function IsPrivate() : bool
    {
        return $this->isPrivate;
    }

    /**
     * Получить идентификатор беседы
     *
     * @return Conversation|null Идентификатор беседы
     */
    public function GetConversation() : ?Conversation
    {
        return $this->conversation;
    }
}