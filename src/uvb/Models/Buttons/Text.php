<?php
declare(ticks = 1);

namespace uvb\Models\Buttons;

use uvb\Models\Buttons\Colors\Color;

/**
 * Стандартная кнопка с текстом
 * @package uvb\Models\Buttons
 *
 *
 */

class Text extends Button
{
    /**
     * @ignore
     */
    public function __construct(string $label, string $payload, string $color)
    {
        parent::__construct($label, $payload, $color);
    }
}