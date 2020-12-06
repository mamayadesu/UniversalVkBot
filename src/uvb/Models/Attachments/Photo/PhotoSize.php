<?php

namespace uvb\Models\Attachments\Photo;

use Exception;

class PhotoSize
{
    /**
     * @ignore
     */
    private int $width, $height;

    /**
     * @ignore
     */
    private string $url, $type;

    /**
     * @ignore
     */
    public function __construct(array $sourceData)
    {
        if (!isset($sourceData["height"]) || !is_int($sourceData["height"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Photo\\PhotoSize: Invalid source data. Height is missing or not integer");
        }

        if (!isset($sourceData["url"]) || !is_string($sourceData["url"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Photo\\PhotoSize: Invalid source data. URL is missing or not string");
        }

        if (!isset($sourceData["type"]) || !is_string($sourceData["type"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Photo\\PhotoSize: Invalid source data. Type is missing or not string");
        }

        if (!isset($sourceData["width"]) || !is_int($sourceData["width"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Photo\\PhotoSize: Invalid source data. Width is missing or not integer");
        }

        $this->url = $sourceData["url"];
        $this->type = $sourceData["type"];
        $this->width = $sourceData["width"];
        $this->height = $sourceData["height"];
    }

    /**
     * Получить ссылку на файл изображения
     *
     * @return string URL файла изображения
     */
    public function GetUrl() : string
    {
        return $this->url;
    }

    /**
     * Получить тип фотографии
     *
     * @return string Тип фотографии
     */
    public function GetType() : string
    {
        return $this->type;
    }

    /**
     * Получить длину изображения
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
     * @return int Высота изображения
     */
    public function GetHeight() : int
    {
        return $this->height;
    }
}