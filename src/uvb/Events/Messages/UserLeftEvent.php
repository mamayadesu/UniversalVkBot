<?php
declare(ticks = 1);

namespace uvb\Events\Messages;

use uvb\Events\Event;
use uvb\Models\User;

/**
 * Событие. Пользователь покинул беседу
 * @package uvb\Events\Messages
 */

class UserLeftEvent extends Event
{
    /**
     * @ignore
     */
    private User $left;

    /**
     * @ignore
     */
    private int $conversationId;

    /**
     * @ignore
     */
    public function __construct(User $left, int $conversationId)
    {
        $this->left = $left;
        $this->conversationId = $conversationId;
    }

    /**
     * Получить пользователя, который покинул беседу
     *
     * @return User Объект пользователя, покинувший беседу
     */
    public function GetLeft() : User
    {
        return $this->left;
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