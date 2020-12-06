<?php

namespace uvb\Models;

class UserNameCases
{
    const NOM = "nom";
    const GEN = "gen";
    const DAT = "dat";
    const ACC = "acc";
    const INS = "ins";
    const ABL = "abl";

    public static function GetValues() : array
    {
        $obj = new UserNameCases();
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