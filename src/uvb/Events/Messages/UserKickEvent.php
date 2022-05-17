<?php

namespace uvb\Events\Messages;

use uvb\Events\Event;
use uvb\Models\User;

/**
 * Событие. Пользователь был исключён из беседы
 * @package uvb\Events\Messages
 */

class UserKickEvent extends Event
{
    /**
     * @ignore
     */
    private User $kickedBy, $kicked;

    /**
     * @ignore
     */
    private int $conversationId;

    /**
     * @ignore
     */
    public function __construct(User $kickedBy, User $kicked, int $conversationId)
    {
        $this->kickedBy = $kickedBy;
        $this->kicked = $kicked;
        $this->conversationId = $conversationId;
    }

    /**
     * Получить пользователя, который исключил пользователя из беседы
     *
     * @return User Объект пользователя, который исключил другого человека из беседы
     */
    public function GetKickedBy() : User
    {
        return $this->kickedBy;
    }

    /**
     * Получить пользователя, который был исключён
     *
     * @return User Объект пользователя, который был исключён из беседы
     */
    public function GetKicked() : User
    {
        return $this->kicked;
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