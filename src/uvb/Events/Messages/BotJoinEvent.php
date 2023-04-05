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
    private Group $group;

    /**
     * @ignore
     */
    public function __construct(User $invited, Group $group, int $conversationId)
    {
        $this->invited = $invited;
        $this->conversationId = $conversationId;
        $this->group = $group;
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
     * Получить объект группы
     *
     * @return Group
     */
    public function GetGroup() : Group
    {
        return $this->group;
    }
}