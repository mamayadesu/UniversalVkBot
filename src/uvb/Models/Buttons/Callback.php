<?php
declare(ticks = 1);

namespace uvb\Models\Buttons;

use uvb\Models\Buttons\Colors\Color;

/**
 * Callback-кнопка клавиатуры бота
 * @package uvb\Models\Buttons
 *
 *
 */

class Callback extends Button
{
    /**
     * @ignore
     */

    public function __construct(string $label, string $payload, string $color)
    {
        parent::__construct($label, $payload, $color);
    }
}