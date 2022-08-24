<?php
declare(ticks = 1);

namespace uvb\Models\Buttons;

use uvb\Models\Buttons\Colors\Color;

/**
 * Кнопка геолокации
 * @package uvb\Models\Buttons
 *
 *
 */
class Location extends Button
{
    /**
     * @ignore
     */
    public function __construct(string $payload)
    {
        parent::__construct("", $payload, Color::POSITIVE);
        $this->type = "location";
    }

    public function GetButtonData(): array
    {
        $arr = array
        (
            "action" => array
            (
                "type" => $this->type,
                "payload" => $this->payload
            )
        );
        return $arr;
    }
}