<?php

namespace uvb\Models;

class UserSex
{
    const FEMALE = 1;
    const MALE = 2;

    public static function GetValues() : array
    {
        $obj = new UserSex();
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