<?php
declare(ticks = 1);

namespace uvb\Models\Attachments\Video;

use \Exception;

class VideoImage
{
    /**
     * @ignore
     */
    private string $url;

    /**
     * @ignore
     */
    private int $width, $height;

    /**
     * @ignore
     */
    public function __construct(array $sourceData)
    {
        if (!isset($sourceData["height"]) || !is_int($sourceData["height"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Video\\Video: Invalid source data. 'height' is missing or not integer");
        }

        if (!isset($sourceData["url"]) || !is_string($sourceData["url"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Video\\Video: Invalid source data. 'url' is missing or not string");
        }

        if (!isset($sourceData["width"]) || !is_int($sourceData["width"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Video\\Video: Invalid source data. 'width' is missing or not integer");
        }

        $this->url = $sourceData["url"];
        $this->width = $sourceData["width"];
        $this->height = $sourceData["height"];
    }

    /**
     * Получить ссылку на файл изображения
     *
     * Появилось в API: 1.0
     *
     * @return string URL файла изображения
     */
    public function GetUrl() : string
    {
        return $this->url;
    }
    /**
     * Получить длину изображения
     *
     * Появилось в API: 1.0
     *
     * @return int Длина изображения
     */
    public function GetWidth() : int
    {
        return $this->width;
    }

    /**
     * Получить высоту изображения
     *
     * Появилось в API: 1.0
     *
     * @return int Высота изображения
     */
    public function GetHeight() : int
    {
        return $this->height;
    }
}