<?php
declare(ticks = 1);

namespace uvb\Events\Messages;

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
     * Получить пользователя, который исключил бота из беседы
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
     * Получить объект группы
     *
     * @return Group
     */
    public function GetJoinedGroup() : Group
    {
        return $this->joinedGroup;
    }
}