<?php
declare(ticks = 1);

namespace uvb\Events\Messages;

use uvb\Events\Event;
use uvb\Models\Group;
use uvb\Models\User;
use uvb\Models\Message;

/**
 * Событие. Новое входящее сообщение в личные сообщения бота
 * @package uvb\Events\Messages
 */

class NewPrivateMessageEvent extends Event
{
    /**
     * @ignore
     */
    private Message $inboxMessage;

    /**
     * @ignore
     */
    private string $rawData;

    /**
     * @ignore
     */
    public function __construct(Group $group, Message $im, string $rawData)
    {
        $this->inboxMessage = $im;
        $this->rawData = $rawData;
        parent::__construct($group);
    }

    /**
     * Получить входящее сообщение
     *
     * @return Message Объект входящего сообщения
     */
    public function GetInboxMessage() : Message
    {
        return $this->inboxMessage;
    }

    /**
     * Получить исходный код данных в виде JSON
     *
     * @return string Исходный код
     */
    public function GetRawData() : string
    {
        return $this->rawData;
    }
}