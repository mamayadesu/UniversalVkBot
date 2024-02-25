<?php
declare(ticks = 1);

namespace uvb\Models\Buttons;

use uvb\Models\Buttons\Colors\Color;

/**
 * Кнопка, открывающая ссылку
 * @package uvb\Models\Buttons
 *
 *
 */

class OpenLink extends Button
{
    /**
     * @ignore
     */
    private string $url;

    /**
     * @ignore
     */
    public function __construct(string $label, string $payload, string $url)
    {
        parent::__construct($label, $payload, Color::POSITIVE);
        $this->url = $url;
        $this->type = "open_link";
    }

    /**
     * Получить URL ссылки
     *
     * Появилось в API: 1.0
     *
     * @return string URL
     */
    public function GetLink() : string
    {
        return $this->url;
    }

    public function GetButtonData(): array
    {
        $arr = array
        (
            "action" => array
            (
                "type" => $this->type,
                "link" => $this->url,
                "label" => $this->label
            )
        );
        if ($this->payload != "")
        {
            $arr["action"]["payload"] = $this->payload;
        }
        return $arr;
    }
}