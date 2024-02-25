<?php
declare(ticks = 1);

namespace uvb\Models\Attachments\Document\Previews;

use \Exception;

/**
 * Модель описывает превью граффити
 */

final class Graffiti extends Preview
{
    /**
     * @ignore
     */
    private string $src;

    /**
     * @ignore
     */
    private int $width, $height;

    /**
     * @ignore
     */
    public function __construct(array $sourceData)
    {
        if (!isset($sourceData["src"]) || !is_string($sourceData["src"]))
        {
            throw new Exception("uvb\\Models\\Attachments\\Document\\Previews\\Graffiti: Invalid source data. 'src' is missing or not string");
        }
        if (!isset($sourceData["width"]) || !is_integer($sourceData["width"]))
        {
            throw new Exception("uvb\\Models\\Attachments\\Document\\Previews\\Graffiti: Invalid source data. 'width' is missing or not string");
        }
        if (!isset($sourceData["height"]) || !is_integer($sourceData["height"]))
        {
            throw new Exception("uvb\\Models\\Attachments\\Document\\Previews\\Graffiti: Invalid source data. 'height' is missing or not string");
        }

        $this->src = $sourceData["src"];
        $this->width = $sourceData["width"];
        $this->height = $sourceData["height"];
    }

    /**
     * Появилось в API: 1.0
     *
     * @return string Ссылка на скачивание
     */
    public function GetLink() : string
    {
        return $this->src;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return int Ширина изображения
     */
    public function GetWidth() : int
    {
        return $this->width;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return int Высота изображения
     */
    public function GetHeight() : int
    {
        return $this->height;
    }
}