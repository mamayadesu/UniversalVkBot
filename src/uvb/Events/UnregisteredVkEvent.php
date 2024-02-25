<?php
declare(ticks = 1);

namespace uvb\Events;

use uvb\Models\Group;

/**
 * Событие. Незарегистрированное
 * @package uvb\Events
 */

class UnregisteredVkEvent extends Event
{
    /**
     * @ignore
     */
    private string $rawContent, $type;

    /**
     * @ignore
     */
    private array $data;

    /**
     * @ignore
     */
    public function __construct(Group $group, string $rawContent, array $data, string $type)
    {
        $this->rawContent = $rawContent;
        $this->data = $data;
        $this->type = $type;
        parent::__construct($group);
    }

    /**
     * Получить исходные данные в виде JSON
     *
     * Появилось в API: 1.0
     *
     * @return string Исходные данные в виде JSON
     */
    public function GetRawContent() : string
    {
        return $this->rawContent;
    }

    /**
     * Получить исходные данные в виде массива
     *
     * Появилось в API: 1.0
     *
     * @return array Исходные данные
     */
    public function GetData() : array
    {
        return $this->data;
    }

    /**
     * Получить тип события
     *
     * Появилось в API: 1.0
     *
     * @return string Тип события. Например, новое сообщение, пользователь вступил в сообщество, пользователь поставил лайк под постом и т.д.
     */
    public function GetType() : string
    {
        return $this->type;
    }
}