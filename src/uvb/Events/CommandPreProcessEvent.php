<?php
declare(ticks = 1);

namespace uvb\Events;

use uvb\Models\Command;

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
    private int $conversationId;

    /**
     * @ignore
     */
    public function __construct($command, bool $isPrivate, int $conversationId)
    {
        $this->command = $command;
        $this->isPrivate = $isPrivate;
        $this->conversationId = $conversationId;
        $this->isCancellable = true;
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
     * @return int Идентификатор беседы
     */
    public function GetConversationId() : int
    {
        return $this->conversationId;
    }
}