<?php

namespace uvb\Events\Messages;

use uvb\Models\User;
use uvb\Events\EventBase;

/**
 * Событие. Бот был добавлен в беседу
 * @package uvb\Events\Messages
 */

class BotJoinEvent extends EventBase
{
    /**
     * @ignore
     */
    private User $invited;

    /**
     * @ignore
     */
    private int $conversationId;

    /**
     * @ignore
     */
    public function __construct(User $invited, int $conversationId)
    {
        $this->invited = $invited;
        $this->conversationId = $conversationId;
    }

    /**
     * Получить пользователя, который добавил бота в беседу
     *
     * @return User Объект пользователя
     */
    public function GetInvited() : User
    {
        return $this->invited;
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