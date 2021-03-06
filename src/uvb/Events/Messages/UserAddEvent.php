<?php

namespace uvb\Events\Messages;

use uvb\Events\EventBase;
use uvb\Models\User;

/**
 * Событие. Пользователь добавлен в беседу
 * @package uvb\Events\Messages
 */

class UserAddEvent extends EventBase
{
    /**
     * @ignore
     */
    private User $invited, $joined;

    /**
     * @ignore
     */
    private int $conversationId;

    /**
     * @ignore
     */
    public function __construct(User $invited, User $joined, int $conversationId)
    {
        $this->invited = $invited;
        $this->joined = $joined;
        $this->conversationId = $conversationId;
    }

    /**
     * Получить пользователя, который добавил нового пользователя
     *
     * @return User Объект пользователя, который добавил человека в беседу
     */
    public function GetInvited() : User
    {
        return $this->invited;
    }

    /**
     * Получить пользователя, который был добавлен
     *
     * @return User Объект пользователя, который был добавлен в беседу
     */
    public function GetJoined() : User
    {
        return $this->joined;
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