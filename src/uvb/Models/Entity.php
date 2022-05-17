<?php

namespace uvb\Models;

interface Entity
{
    public function GetVkId() : int;

    public function GetName() : string;

    public function IsHuman() : bool;

    public function GetMention() : string;

    public function GetFullMention() : string;

    public static function Get(int $vkId) : ?Entity;
}