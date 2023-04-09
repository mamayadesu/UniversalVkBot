<?php
declare(ticks = 1);

namespace uvb\Events\InGroupUserAction;

use uvb\Events\Event;
use uvb\Models\User;
use uvb\Models\Group;

/**
 * Событие. Пользователь присоединился к группе/подал заявку на вступление/заявка одобрена
 * @package uvb\Events\InGroupUserAction
 */

class UserJoinGroupEvent extends Event
{
    /**
     * @ignore
     */
    private User $user;

    /**
     * @ignore
     */
    private bool $join, $request, $approved;

    /**
     * @ignore
     */
    public function __construct(Group $group, User $user, bool $join, bool $request, bool $approved)
    {
        $this->user = $user;
        $this->join = $join;
        $this->request = $request;
        $this->approved = $approved;
        $this->isCancellable = true;
        parent::__construct($group);
    }

    /**
     * Получить пользователя, который вступил или отправил заявку на вступление
     * 
     * @return User Пользователб
     */
    public function GetUser() : User
    {
        return $this->user;
    }

    /**
     * Вступил ли пользователь в группу самостоятельно
     * 
     * @return bool Присоединился ли пользователь самостоятельно
     */
    public function IsJoined() : bool
    {
        return $this->join;
    }

    /**
     * Отправил ли пользователь заявку на вступление в закрытое сообщество
     *
     * @return bool TRUE - если пользователь только что отправил заявку на вступление в сообщество. FALSE - в остальных случаях
     */
    public function IsRequest() : bool
    {
        return $this->request;
    }

    /**
     * Была ли заявка на вступление в закрытое сообщество одобрена
     * 
     * @return bool TRUE - если заявка пользователя была только одобрена. FALSE - в остальных случаях
     */
    public function IsApproved() : bool
    {
        return $this->approved;
    }

    /**
     * Отменить событие
     * Пользователь будет исключён из сообщества либо заявка на вступление будет отклонена
     */
    public function SetCancelled() : void
    {
        if ($this->IsCancelled())
        {
            return;
        }

        $this->cancelled = Group::Get(1)->KickMember($this->user);
    }

    /**
     * Возвращает объект группы, в которую вступили/подали заявку/одобрили заявку на вступление
     *
     * @return Group
     */
    public function GetGroup() : Group
    {
        return parent::GetGroup();
    }
}