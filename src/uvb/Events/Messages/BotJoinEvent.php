<?php
declare(ticks = 1);

namespace uvb\Events\Messages;

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
    private int $conversationId;

    /**
     * @ignore
     */
    private Group $joinedGroup;

    /**
     * @ignore
     */
    public function __construct(Group $group, User $invited, Group $joinedGroup, int $conversationId)
    {
        $this->invited = $invited;
        $this->conversationId = $conversationId;
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
     * @return int Идентификатор беседы
     */
    public function GetConversationId() : int
    {
        return $this->conversationId;
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