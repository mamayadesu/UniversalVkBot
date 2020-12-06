<?php

namespace uvb\Models\Attachments;

class AttachmentTypes
{
    const PHOTO = "photo";
    const VIDEO = "video";
    const AUDIO = "audio";
    const DOCUMENT = "doc";
    const WALL = "wall";
    const MARKET = "market";
    const POLL = "poll";
    const STICKER = "sticker";

    public static function GetValues() : array
    {
        $obj = new AttachmentTypes();
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