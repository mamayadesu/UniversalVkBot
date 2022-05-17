<?php

namespace uvb\Events\Messages;

use uvb\Events\Event;
use uvb\Models\Message;

/**
 * Событие. Новое входящее сообщение в беседу
 * @package uvb\Events\Messages
 */

class NewConversationMessageEvent extends Event
{
    /**
     * @ignore
     */
    private Message $inboxMessage;

    /**
     * @ignore
     */
    private int $conversationId;

    /**
     * @ignore
     */
    private string $rawData;

    /**
     * @ignore
     */
    public function __construct(Message $inboxMessage, int $conversationId, string $rawData)
    {
        $this->inboxMessage = $inboxMessage;
        $this->conversationId = $conversationId;
        $this->rawData = $rawData;
        $this->isCancellable = true;
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
     * Получить идентификатор беседы
     *
     * @return int Идентификатор беседы
     */
    public function GetConversationId() : int
    {
        return $this->conversationId;
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

    /**
     * Отменить событие. Сообщение будет удалено
     */
    public function SetCancelled() : void
    {
        if ($this->cancelled)
        {
            return;
        }
        $this->cancelled = $this->inboxMessage->Delete();
    }
}