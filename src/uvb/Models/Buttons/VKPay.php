<?php

namespace uvb\Models\Buttons;

use uvb\Models\Buttons\Colors\Color;

/**
 * Кнопка VKPay
 * @package uvb\Models\Buttons
 *
 *
 */

class VKPay extends Button
{
    /**
     * @ignore
     */
    private string $hash;

    /**
     * @ignore
     */
    public function __construct(string $payload, string $hash)
    {
        parent::__construct("", $payload, Color::POSITIVE);
        $this->hash = $hash;
        $this->type = "vkpay";
    }

    public function GetHash() : string
    {
        return $this->hash;
    }

    public function GetButtonData(): array
    {
        $arr = array
        (
            "action" => array
            (
                "type" => $this->type,
                "payload" => $this->payload,
                "hash" => $this->hash
            )
        );
        return $arr;
    }
}