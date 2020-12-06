<?php

namespace uvb;

class BackgroundColors
{
    const AUTO = "auto";
    const BLACK = "40";
    const RED = "41";
    const GREEN = "42";
    const YELLOW = "43";
    const BLUE = "44";
    const MAGENTA = "45";
    const CYAN = "46";
    const LIGHT_GRAY = "47";

    public static function GetValues() : array
    {
        $obj = new BackgroundColors();
        $reflectionClass = new \ReflectionClass($obj);
        $arr = [];
        foreach ($reflectionClass->getConstants() as $key => $value)
        {
            $arr[] = $value;
        }
        $obj = null;
        unset($obj);
        return $arr;
    }
}