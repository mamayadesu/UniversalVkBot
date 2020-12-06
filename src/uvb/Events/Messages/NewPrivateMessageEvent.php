<?php

namespace uvb\Events\Messages;

use uvb\Events\EventBase;
use uvb\Models\User;
use uvb\Models\InboxMessage;

/**
 * Событие. Новое входящее сообщение в личные сообщения бота
 * @package uvb\Events\Messages
 */

class NewPrivateMessageEvent extends EventBase
{
    /**
     * @ignore
     */
    private InboxMessage $inboxMessage;

    /**
     * @ignore
     */
    private string $rawData;

    /**
     * @ignore
     */
    public function __construct(InboxMessage $im, string $rawData)
    {
        $this->inboxMessage = $im;
        $this->rawData = $rawData;
    }

    /**
     * Получить входящее сообщение
     *
     * @return InboxMessage Объект входящего сообщения
     */
    public function GetInboxMessage() : InboxMessage
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