<?php
declare(ticks = 1);

namespace uvb\System;

final class SystemConfigResource
{
    private static array $config = array();

    public static function Init(array $cfg) : void
    {
        /*if (count(self::$config) == 0)
        {
            self::$config = $cfg;
        }*/
        self::$config = $cfg;
    }

    public static function GetConfig() : array
    {
        return self::$config;
    }
}