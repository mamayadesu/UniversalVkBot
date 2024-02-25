<?php
declare(ticks = 1);

namespace uvb\Models;

use Data\Enum;

/**
 * Типы сообщества
 */

class GroupAccessType extends Enum
{
    /**
     * Открытое. Появилось в API: 1.0
     */
    const OPEN = 0;

    /**
     * Закрытое. Появилось в API: 1.0
     */
    const CLOSED = 1;

    /**
     * Частное. Появилось в API: 1.0
     */
    const PRIVATE = 2;
}