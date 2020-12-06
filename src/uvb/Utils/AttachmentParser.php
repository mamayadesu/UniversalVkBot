<?php

namespace uvb\Utils;

use uvb\Bot;
use uvb\Models\Attachments\Attachment;
use uvb\Models\Attachments\AttachmentTypes;
use uvb\Models\Attachments\Audio\Audio;
use uvb\Models\Attachments\Photo\Photo;
use uvb\Models\Attachments\Sticker\Sticker;
use \Exception;
use uvb\Models\Attachments\Video\Video;

class AttachmentParser
{
    public static function Parse(array $sourceData) : ?Attachment
    {
        $attachment = null;
        switch ($sourceData["type"])
        {
            case AttachmentTypes::PHOTO:
                try
                {
                    $attachment = new Photo($sourceData["photo"]);
                }
                catch (Exception $e)
                {
                    self::PrintErr($e->getMessage());
                    return $attachment;
                }
                break;

            case AttachmentTypes::STICKER:
                try
                {
                    $attachment = new Sticker($sourceData["sticker"]);
                }
                catch (Exception $e)
                {
                    self::PrintErr($e->getMessage());
                    return $attachment;
                }
                break;

            case AttachmentTypes::VIDEO:
                try
                {
                    $attachment = new Video($sourceData["video"]);
                }
                catch (Exception $e)
                {
                    self::PrintErr($e->getMessage());
                    return $attachment;
                }
                break;

            case AttachmentTypes::AUDIO:
                try
                {
                    $attachment = new Audio($sourceData["audio"]);
                }
                catch (Exception $e)
                {
                    self::PrintErr($e->getMessage());
                    return $attachment;
                }
                break;

            default:
                try
                {
                    $attachment = new Attachment($sourceData[$sourceData["type"]], $sourceData["type"]);
                }
                catch (Exception $e)
                {
                    self::PrintErr($e->getMessage());
                    return $attachment;
                }
                break;
        }
        //var_dump($attachment);
        return $attachment;
    }

    private static function PrintErr(string $msg) : void
    {
        Bot::GetInstance()->GetLogger()->Critical($msg);
    }
}