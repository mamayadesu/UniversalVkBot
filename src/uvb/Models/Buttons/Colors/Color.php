<?php

namespace uvb\Models\Buttons\Colors;

class Color
{
    const PRIMARY = "primary";
    const SECONDARY = "secondary";
    const NEGATIVE = "negative";
    const POSITIVE = "positive";

    public static function GetValues() : array
    {
        $obj = new Color();
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