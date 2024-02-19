<?php
declare(ticks = 1);

namespace uvb\Events\Messages;

use uvb\Events\Event;
use uvb\Models\Conversation;
use uvb\Models\Group;
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
    private Conversation $conversation;

    /**
     * @ignore
     */
    public function __construct(Group $group, User $joined, Conversation $conversation)
    {
        $this->joined = $joined;
        $this->conversation = $conversation;
        parent::__construct($group);
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
     * @return Conversation Идентификатор беседы
     */
    public function GetConversation() : Conversation
    {
        return $this->conversation;
    }
}