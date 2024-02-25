<?php
declare(ticks = 1);

namespace uvb\Utils;

use uvb\Bot;
use uvb\Models\Attachments\Attachment;
use uvb\Models\Attachments\AttachmentTypes;
use uvb\Models\Attachments\Audio\Audio;
use uvb\Models\Attachments\AudioMessage\AudioMessage;
use uvb\Models\Attachments\Document\Document;
use uvb\Models\Attachments\Photo\Photo;
use uvb\Models\Attachments\Sticker\Sticker;
use \Exception;
use uvb\Models\Attachments\Video\Video;
use uvb\System\CrashHandler;

class AttachmentParser
{
    /*// Кэширование вложений отменено
    // Потому что вложений с разными айдишниками много и кэшировать их - лишняя трата памяти
    private static array $CachedAttachments = array();*/

    /**
     * Парсит вложение, присланные в JSON от VK.
     *
     * Появилось в API: 1.0
     *
     * @param array $sourceData Исходные данные
     * @return Attachment|null Объект вложения
     */
    public static function Parse(array $sourceData) : ?Attachment
    {
        $attachment = null;

        //
        /*$str = $sourceData["type"] . $sourceData["owner_id"] . "_" . $sourceData["id"];
        if (isset(self::$CachedAttachments[$str]))
        {
            return self::$CachedAttachments[$str];
        }*/

        switch ($sourceData["type"])
        {
            case AttachmentTypes::PHOTO:
                try
                {
                    $attachment = new Photo($sourceData["photo"]);
                }
                catch (Exception $e)
                {
                    self::PrintErr($e);
                }
                break;

            case AttachmentTypes::STICKER:
                try
                {
                    $attachment = new Sticker($sourceData["sticker"]);
                }
                catch (Exception $e)
                {
                    self::PrintErr($e);
                }
                break;

            case AttachmentTypes::VIDEO:
                try
                {
                    $attachment = new Video($sourceData["video"]);
                }
                catch (Exception $e)
                {
                    self::PrintErr($e);
                }
                break;

            case AttachmentTypes::AUDIO:
                try
                {
                    $attachment = new Audio($sourceData["audio"]);
                }
                catch (Exception $e)
                {
                    self::PrintErr($e);
                }
                break;

            case AttachmentTypes::DOCUMENT:
                try
                {
                    $attachment = new Document($sourceData["doc"]);
                }
                catch (Exception $e)
                {
                    self::PrintErr($e);
                }
                break;

            case AttachmentTypes::AUDIO_MESSAGE:
                try
                {
                    $attachment = new AudioMessage($sourceData["audio_message"]);
                }
                catch (Exception $e)
                {
                    self::PrintErr($e);
                }
                break;

            default:
                try
                {
                    $attachment = new Attachment($sourceData[$sourceData["type"]], $sourceData["type"]);
                }
                catch (Exception $e)
                {
                    self::PrintErr($e);
                }
                break;
        }
        //self::$CachedAttachments[$str] = $attachment;
        return $attachment;
    }

    /**
     * @ignore
     */
    private static function PrintErr(Exception $e) : void
    {
        Bot::GetInstance()->GetLogger()->Critical($e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
        CrashHandler::Handle($e);
    }
}