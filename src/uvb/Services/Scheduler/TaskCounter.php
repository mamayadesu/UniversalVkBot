<?php

namespace uvb\Services\Scheduler;

/**
 * @ignore
 */

final class TaskCounter
{
    private static int $i = 0;

    public static function GetNext() : int
    {
        return ++self::$i;
    }
}