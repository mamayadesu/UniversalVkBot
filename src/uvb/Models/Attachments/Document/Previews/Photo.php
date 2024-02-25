<?php
declare(ticks = 1);

namespace uvb\Models\Attachments\Document\Previews;

use uvb\Models\Attachments\Document\Previews\Photo\Size;
use \Exception;

/**
 * Модель содержит в себе размеры превью-изображений. В качестве изображения может быть GIF или любое другое изображение
 */

final class Photo extends Preview
{
    /**
     * @var array<Size>
     * @ignore
     */
    private array $sizes = array();

    /**
     * @ignore
     */
    public function __construct(array $sourceData)
    {
        if (!isset($sourceData["sizes"]) || !is_array($sourceData["sizes"]))
        {
            throw new Exception("uvb\\Models\\Attachments\\Document\\Previews\\Photo: Invalid source data. 'sizes' is missing or not array");
        }

        foreach ($sourceData["sizes"] as $sizeArray)
        {
            $this->sizes[] = new Size($sizeArray);
        }
    }

    /**
     * Появилось в API: 1.0
     *
     * @return array<Size> Массив размеров изображения
     */
    public function GetSizes() : array
    {
        return $this->sizes;
    }
}