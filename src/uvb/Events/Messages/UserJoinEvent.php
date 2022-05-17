<?php

namespace uvb\Events\Messages;

use uvb\Events\Event;
use uvb\Models\User;

/**
 * Событие. Пользователь присоединился к беседе. Срабатывает, когда пользователь присоединился к беседе по ссылке
 * @package uvb\Events\Messages
 */

class UserJoinEvent extends Event
{
    /**
     * @ignore
     */
    private User $joined;

    /**
     * @ignore
     */
    private int $conversationId;

    /**
     * @ignore
     */
    public function __construct(User $joined, int $conversationId)
    {
        $this->joined = $joined;
        $this->conversationId = $conversationId;
    }

    /**
     * Получить пользователя, который присоединился
     *
     * @return User Объект пользователя, который самостоятельно присоединился к беседе
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