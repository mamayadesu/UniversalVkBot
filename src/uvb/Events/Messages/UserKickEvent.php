<?php
declare(ticks = 1);

namespace uvb\Events\Messages;

use uvb\Events\Event;
use uvb\Models\Conversation;
use uvb\Models\Group;
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
    private Conversation $conversation;

    /**
     * @ignore
     */
    public function __construct(Group $group, User $kickedBy, User $kicked, Conversation $conversation)
    {
        $this->kickedBy = $kickedBy;
        $this->kicked = $kicked;
        $this->conversation = $conversation;
        parent::__construct($group);
    }

    /**
     * Получить пользователя, который исключил пользователя из беседы
     *
     * Появилось в API: 1.0
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
     * Появилось в API: 1.0
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
     * Появилось в API: 1.0
     *
     * @return Conversation Идентификатор беседы
     */
    public function GetConversation() : Conversation
    {
        return $this->conversation;
    }
}