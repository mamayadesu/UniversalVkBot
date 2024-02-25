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
    private bool $leftBySelf;

    /**
     * @ignore
     */
    public function __construct(Group $group, User $user, bool $leftBySelf)
    {
        $this->user = $user;
        $this->leftBySelf = $leftBySelf;
        parent::__construct($group);
    }

    /**
     * Получить пользователя
     *
     * Появилось в API: 1.0
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
     * Появилось в API: 1.0
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
     * Появилось в API: 1.0
     *
     * @return Group
     */
    public function GetGroup() : Group
    {
        return parent::GetGroup();
    }
}