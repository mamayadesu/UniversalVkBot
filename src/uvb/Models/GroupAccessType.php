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
     * Открытое
     */
    const OPEN = 0;

    /**
     * Закрытое
     */
    const CLOSED = 1;

    /**
     * Частное
     */
    const PRIVATE = 2;
}