<?php
declare(ticks = 1);

namespace uvb\Events\Messages;

use uvb\Models\Conversation;
use uvb\Models\Group;
use uvb\Models\User;
use uvb\Events\Event;

/**
 * Событие. Бот был исключён из беседы
 * @package uvb\Events\Messages
 */

class BotLeftEvent extends Event
{
    /**
     * @ignore
     */
    private User $invited;

    /**
     * @ignore
     */
    private Conversation $conversation;

    /**
     * @ignore
     */
    private Group $joinedGroup;

    /**
     * @ignore
     */
    public function __construct(Group $group, User $invited, Group $joinedGroup, Conversation $conversation)
    {
        $this->invited = $invited;
        $this->conversation = $conversation;
        $this->joinedGroup = $joinedGroup;
        parent::__construct($group);
    }

    /**
     * Получить пользователя, который исключил бота из беседы
     *
     * Появилось в API: 1.0
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
     * Появилось в API: 1.0
     *
     * @return Conversation Идентификатор беседы
     */
    public function GetConversation() : Conversation
    {
        return $this->conversation;
    }

    /**
     * Получить объект группы
     *
     * Появилось в API: 1.0
     *
     * @return Group
     */
    public function GetJoinedGroup() : Group
    {
        return $this->joinedGroup;
    }
}