<?php

namespace uvb\Events\InGroupUserAction;

use uvb\Events\EventBase;
use uvb\Models\User;

/**
 * Событие. Пользователь покинул сообщество
 * @package uvb\Events\InGroupUserAction
 */

class UserLeftGroupEvent extends EventBase
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
    public function __construct(User $user, bool $leftBySelf)
    {
        $this->user = $user;
        $this->leftBySelf = $leftBySelf;
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
}