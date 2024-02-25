<?php
declare(ticks = 1);

namespace uvb\Events;

use uvb\Models\Group;

/**
 * Класс, описывающий любое событие
 * @package uvb\Events
 */

abstract class Event
{

    /**
     * @ignore
     */
    protected bool $isCancellable = false, $cancelled = false;

    protected ?Group $group = null;

    public function __construct(?Group $group)
    {
        $this->group = $group;
    }

    /**
     * Возможно ли отменить событие
     *
     * Появилось в API: 1.0
     *
     * @return bool TRUE - событие можно отменить, вызвав метод SetCancelled(). FALSE - событие отменить нельзя
     */
    public function IsCancellable() : bool
    {
        return $this->isCancellable;
    }

    /**
     * Отменить событие. Событие нельзя отменить, если в параметрах события задано, что оно неотменяемое
     *
     * Появилось в API: 1.0
     */
    public function SetCancelled() : void
    {
        if ($this->isCancellable)
        {
            $this->cancelled = true;
        }
    }

    /**
     * Отменено ли событие
     *
     * Появилось в API: 1.0
     *
     * @return bool TRUE - событие отменено. FALSE - событие не было отменено
     */
    public function IsCancelled() : bool
    {
        return $this->cancelled;
    }

    /**
     * Получить объект группы, к которой относится событие
     *
     * Появилось в API: 1.0
     *
     * @return Group|null
     */
    public function GetGroup() : ?Group
    {
        return $this->group;
    }
}