<?php
declare(ticks = 1);

namespace uvb\Events\Messages;

use uvb\Events\Event;
use uvb\Models\Conversation;
use uvb\Models\Group;
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
    private Conversation $conversation;

    /**
     * @ignore
     */
    public function __construct(Group $group, User $left, Conversation $conversation)
    {
        $this->left = $left;
        $this->conversation = $conversation;
        parent::__construct($group);
    }

    /**
     * Получить пользователя, который покинул беседу
     *
     * Появилось в API: 1.0
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
     * Появилось в API: 1.0
     *
     * @return Conversation Идентификатор беседы
     */
    public function GetConversation() : Conversation
    {
        return $this->conversation;
    }
}