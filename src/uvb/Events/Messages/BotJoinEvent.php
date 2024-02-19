<?php
declare(ticks = 1);

namespace uvb\Events\Messages;

use uvb\Models\Conversation;
use uvb\Models\Group;
use uvb\Models\User;
use uvb\Events\Event;

/**
 * Событие. Бот был добавлен в беседу
 * @package uvb\Events\Messages
 */

class BotJoinEvent extends Event
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
     * @param Group $group
     * @param User $invited
     * @param Group $joinedGroup
     * @param Conversation $conversation
     */
    public function __construct(Group $group, User $invited, Group $joinedGroup, Conversation $conversation)
    {
        $this->invited = $invited;
        $this->conversation = $conversation;
        $this->joinedGroup = $joinedGroup;
        parent::__construct($group);
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
     * @return Conversation Идентификатор беседы
     */
    public function GetConversation() : Conversation
    {
        return $this->conversation;
    }

    /**
     * Получить объект группы, которая была добавлена в беседу
     *
     * @return Group
     */
    public function GetJoinedGroup() : Group
    {
        return $this->joinedGroup;
    }
}