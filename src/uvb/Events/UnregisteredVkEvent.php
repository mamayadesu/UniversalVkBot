<?php
declare(ticks = 1);

namespace uvb\Events;

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
    public function __construct(string $rawContent, array $data, string $type)
    {
        $this->rawContent = $rawContent;
        $this->data = $data;
        $this->type = $type;
    }

    /**
     * Получить исходные данные в виде JSON
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
     * @return array Исходные данные
     */
    public function GetData() : array
    {
        return $this->data;
    }

    /**
     * Получить тип события
     *
     * @return string Тип события. Например, новое сообщение, пользователь вступил в сообщество, пользователь поставил лайк под постом и т.д.
     */
    public function GetType() : string
    {
        return $this->type;
    }
}