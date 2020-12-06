<?php

namespace uvb\Models\Buttons;

use uvb\Models\Buttons\Colors\Color;

/**
 * Кнопка клавиатуры бота
 * @package uvb\Models\Buttons
 */

class Button
{
    /**
     * @ignore
     */
    protected string $type, $label, $payload;

    /**
     * @ignore
     */
    protected string $color;

    /**
     * @ignore
     */
    public function __construct(string $label, string $payload, string $color)
    {
        $this->type = "text";
        $this->label = $label;
        $this->payload = $payload;
        $this->color = $color;
    }

    /**
     * Получить текст кнопки
     *
     * @return string Текст кнопки
     */
    public function GetLabel() : string
    {
        return $this->label;
    }

    /**
     * Получить полезную нагрузку кнопки
     *
     * @return string Полезная нагрузка кнопки
     */
    public function GetPayload() : string
    {
        return $this->payload;
    }

    /**
     * Получить тип кнопки
     *
     * @return string Тип кнопки
     */
    public function GetType() : string
    {
        return $this->type;
    }

    public function __toString() : string
    {
        return $this->ConvertToJson();
    }

    /**
     * Конвертировать данные кнопки в JSON
     *
     * @return string Данные кнопки в виде JSON
     */
    public function ConvertToJson() : string
    {
        $arr = $this->GetButtonData();
        return json_encode($arr);
    }

    /**
     * Получить данные кнопки в виде массива
     *
     * @return array Данные кнопки
     */
    public function GetButtonData() : array
    {
        $arr = array
        (
            "action" => array
            (
                "type" => $this->type,
                "label" => $this->label
            ),
            "color" => $this->color
        );
        if ($this->payload != "")
        {
            $arr["action"]["payload"] = $this->payload;
        }
        return $arr;
    }
}