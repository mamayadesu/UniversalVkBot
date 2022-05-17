<?php

namespace uvb\Models\Attachments\Document\Previews;

use uvb\Models\Attachments\Document\Previews\Photo\Size;
use \Exception;

/**
 * Модель содержит в себе размеры превью-изображений. В качестве изображения может быть GIF или любое другое изображение
 */

final class Photo extends Preview
{
    /**
     * @ignore
     */
    private array/*<Size>*/ $sizes = array();

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
     * @return array<Size> Массив размеров изображения
     */
    public function GetSizes() : array/*<Size>*/
    {
        return $this->sizes;
    }
}