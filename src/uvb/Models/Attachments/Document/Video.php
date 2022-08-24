<?php
declare(ticks = 1);

namespace uvb\Models\Attachments\Document;

use \Exception;

/**
 * Модель описывает видео GIF-изображения
 */

final class Video
{
    /**
     * @ignore
     */
    private string $src;

    /**
     * @ignore
     */
    private int $width, $height, $fileSize;

    public function __construct(array $sourceData)
    {
        if (!isset($sourceData["src"]) || !is_string($sourceData["src"]))
        {
            throw new Exception("uvb\\Models\\Attachments\\Document\\Video: Invalid source data. 'src' is missing or not string");
        }
        if (!isset($sourceData["width"]) || !is_integer($sourceData["width"]))
        {
            throw new Exception("uvb\\Models\\Attachments\\Document\\Video: Invalid source data. 'width' is missing or not string");
        }
        if (!isset($sourceData["height"]) || !is_integer($sourceData["height"]))
        {
            throw new Exception("uvb\\Models\\Attachments\\Document\\Video: Invalid source data. 'height' is missing or not string");
        }
        if (!isset($sourceData["file_size"]) || !is_integer($sourceData["file_size"]))
        {
            throw new Exception("uvb\\Models\\Attachments\\Document\\Video: Invalid source data. 'file_size' is missing or not string");
        }

        $this->src = $sourceData["src"];
        $this->width = $sourceData["width"];
        $this->height = $sourceData["height"];
        $this->fileSize = $sourceData["file_size"];
    }

    /**
     * @return string Ссылка на скачивание
     */
    public function GetLink() : string
    {
        return $this->src;
    }

    /**
     * @return int Ширина видео
     */
    public function GetWidth() : int
    {
        return $this->width;
    }

    /**
     * @return int Высота видео
     */
    public function GetHeight() : int
    {
        return $this->height;
    }

    /**
     * @return int Размер файла
     */
    public function GetFileSize() : int
    {
        return $this->fileSize;
    }
}