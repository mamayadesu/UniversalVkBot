<?php
declare(ticks = 1);

namespace uvb\Events\Messages;

use uvb\Events\Event;
use uvb\Models\Conversation;
use uvb\Models\Group;
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
    private Conversation $conversation;

    /**
     * @ignore
     */
    private string $rawData;

    /**
     * @ignore
     */
    public function __construct(Group $group, Message $inboxMessage, Conversation $conversation, string $rawData)
    {
        $this->inboxMessage = $inboxMessage;
        $this->conversation = $conversation;
        $this->rawData = $rawData;
        $this->isCancellable = true;
        parent::__construct($group);
    }

    /**
     * Получить входящее сообщение
     *
     * Появилось в API: 1.0
     *
     * @return Message Объект входящего сообщения
     */
    public function GetInboxMessage() : Message
    {
        return $this->inboxMessage;
    }

    /**
     * Получить объект беседы
     *
     * Появилось в API: 1.0
     *
     * @return Conversation
     */
    public function GetConversation() : Conversation
    {
        return $this->conversation;
    }

    /**
     * Получить исходный код данных в виде JSON
     *
     * Появилось в API: 1.0
     *
     * @return string Исходный код
     */
    public function GetRawData() : string
    {
        return $this->rawData;
    }

    /**
     * Отменить событие. Сообщение будет удалено
     *
     * Появилось в API: 1.0
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