<?php
declare(ticks = 1);

namespace uvb\Models\Attachments\Photo;

use \Exception;
use uvb\Bot;
use uvb\Models\Attachments\Attachment;
use uvb\Models\Attachments\AttachmentTypes;

/**
 * Объект фотографии в ВК
 * @package uvb\Models\Attachments\Photo
 */

class Photo extends Attachment
{
    /**
     * @ignore
     */
    private array/*<PhotoSize>*/ $sizes;

    /**
     * @ignore
     */
    private int $albumId;

    /**
     * @ignore
     */
    private bool $_hasTags;

    /**
     * @ignore
     */
    private string $text;

    /**
     * @ignore
     */
    public function __construct(array $sourceData)
    {
        parent::__construct($sourceData, AttachmentTypes::PHOTO);
        $keys = ["text", "sizes", "id", "owner_id", "album_id"];
        $noKeys = [];
        foreach ($keys as $key)
        {
            if (!isset($sourceData[$key]))
            {
                $noKeys[] = $key;
            }
        }
        if (count($noKeys) > 0)
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Photo\\Photo: Invalid source data. The next keys are missing: " . implode(", ", $noKeys));
        }

        $this->text = $sourceData["text"];
        if (!is_array($sourceData["sizes"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Photo\\Photo: Invalid source data. Sizes must be an array, " . gettype($sourceData["sizes"] . " given"));
        }
        if (!is_int($sourceData["album_id"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Photo\\Photo: Invalid source data. Album ID must be an integer, " . gettype($sourceData["album_id"] . " given"));
        }
        if (isset($sourceData["has_tags"]) && !is_bool($sourceData["has_tags"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Photo\\Photo: Invalid source data. Has tags must be a bool, " . gettype($sourceData["has_tags"] . " given"));
        }
        if (!is_string($sourceData["text"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Photo\\Photo: Invalid source data. Text must be a string, " . gettype($sourceData["text"] . " given"));
        }
        $size = null;
        foreach ($sourceData["sizes"] as $sourceSize)
        {
            try
            {
                $size = new PhotoSize($sourceSize);
            }
            catch (Exception $e)
            {
                throw $e;
            }
            $this->sizes[] = $size;
        }
        $this->albumId = $sourceData["album_id"];
        $this->_hasTags = $sourceData["has_tags"] ?? false;
        $this->text = $sourceData["text"];
    }

    /**
     * Получить список размеров фотографии
     *
     * @return array<PhotoSize> Список объектов PhotoSize, в которых в каждом из них информация о размере фотографии
     */
    public function GetSizes() : array/*<PhotoSize>*/
    {
        return $this->sizes;
    }

    /**
     * Получить идентификатор альбома, в котором находится фотография
     *
     * @return int Идентификатор альбома
     */
    public function GetAlbumId() : int
    {
        return $this->albumId;
    }

    /**
     * Имеет ли фотография тэги
     *
     * @return bool TRUE - у фотографии есть тэги
     */
    public function HasTags() : bool
    {
        return $this->_hasTags;
    }

    /**
     * Получить текст к фотографии
     *
     * @return string Текст фотографии
     */
    public function GetText() : string
    {
        return $this->text;
    }

    public function __toString() : string
    {
        return $this->GetText();
    }
}