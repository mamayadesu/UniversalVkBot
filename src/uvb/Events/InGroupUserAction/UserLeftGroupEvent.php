<?php
declare(ticks = 1);

namespace uvb\Events\InGroupUserAction;

use uvb\Events\Event;
use uvb\Models\Group;
use uvb\Models\User;

/**
 * Событие. Пользователь покинул сообщество
 * @package uvb\Events\InGroupUserAction
 */

class UserLeftGroupEvent extends Event
{
    /**
     * @ignore
     */
    private User $user;

    /**
     * @ignore
     */
    private Group $group;

    /**
     * @ignore
     */
    private bool $leftBySelf;

    /**
     * @ignore
     */
    public function __construct(User $user, Group $group, bool $leftBySelf)
    {
        $this->user = $user;
        $this->leftBySelf = $leftBySelf;
        $this->group = $group;
    }

    /**
     * Получить пользователя
     *
     * @return User Возвращает пользователя, покинувший группу
     */
    public function GetUser() : User
    {
        return $this->user;
    }

    /**
     * Вышел ли пользователь из сообщества самостоятельно
     *
     * @return bool TRUE, если пользователь покинул сообщество самостоятельно. FALSE - во всех остальных случаях
     */
    public function LeftBySelf() : bool
    {
        return $this->leftBySelf;
    }

    /**
     * Возвращает объект группы, из которой вышли или исключили
     *
     * @return Group
     */
    public function GetGroup() : Group
    {
        return $this->group;
    }
}