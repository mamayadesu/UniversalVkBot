<?php

namespace uvb;

class ConfigResource
{
    private static array $config = array();

    public static function Init(array $cfg) : void
    {
        if (count(self::$config) == 0)
        {
            self::$config = $cfg;
        }
    }

    public static function GetConfig() : array
    {
        return self::$config;
    }
}