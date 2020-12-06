<?php

namespace uvb\Models;

use uvb\Models\Buttons\Button;

/**
 * Данный класс описывает клавиатуру бота
 * @package uvb\Models
 *
 *
 */

class BotKeyboard
{
    /**
     * @ignore
     */
    private bool $oneTime = true, $inline = true;

    /**
     * @ignore
     */
    private array $buttons = [];

    public function __construct()
    {

    }

    /**
     * Включить или отключить опцию скрытия клавиатуры после нажатия на кнопку
     *
     * @param bool $oneTime Скрывать ли клавиатуру после нажатия на кнопку
     * @return BotKeyboard Текущая клавиатура
     */
    public function SetOneTime(bool $oneTime) : BotKeyboard
    {
        $this->oneTime = $oneTime;
        if ($this->oneTime)
        {
            $this->inline = false;
        }
        return $this;
    }

    /**
     * Установить опцию, должна ли клавиатура отображаться внутри сообщения
     *
     * @param bool $inline TRUE - клавиатура отображается внутри сообщения. Опция one_time при этом не поддерживается. FALSE - стандартное отображение клавиатуры
     * @return BotKeyboard Текущая клавиатура
     */
    public function SetInline(bool $inline) : BotKeyboard
    {
        $this->inline = $inline;
        if ($this->inline)
        {
            $this->oneTime = false;
        }
        return $this;
    }

    /**
     * Добавляет кнопку к клавиатуре бота
     *
     * @param Button $button Объект, описывающий кнопку клавиатуры
     * @return BotKeyboard Текущая клавиатура
     */
    public function AddButton(Button $button) : BotKeyboard
    {
        $this->buttons[] = $button;
        return $this;
    }

    /**
     * @ignore
     */
    public function __toString() : string
    {
        return $this->ConvertToJson();
    }

    /**
     * @return bool Содержит ли клавиатура бота хотя бы одну кнопку.
     */
    public function IsKeyboardFilled() : bool
    {
        return count($this->buttons) > 0;
    }

    /**
     * Получить исходные данные клавиатуры бота в виде массива
     *
     * @return array<string, mixed> Исходные данные клавиатуры бота в виде массива
     */
    public function GetKeyboardData() : array
    {
        if (count($this->buttons) == 0)
        {
            return array();
        }
        $buttons = [];
        foreach ($this->buttons as $button)
        {if(!$button instanceof Button)continue;
            $buttons[] = [$button->GetButtonData()];
        }
        $arr = array
        (
            "one_time" => $this->oneTime,
            "buttons" => $buttons,
            "inline" => $this->inline
        );
        return $arr;
    }

    /**
     * Получить исходные данные клавиатуры бота в виде JSON
     *
     * @return string Исходные данные клавиатуры бота в виде JSON
     */
    public function ConvertToJson() : string
    {
        $arr = $this->GetKeyboardData();

        return json_encode($arr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}