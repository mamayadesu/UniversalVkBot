<?php
declare(ticks = 1);

namespace uvb\Models\Attachments\Document\Previews\Photo;

use uvb\Models\Attachments\Photo\PhotoSize;

/**
 * Модель размера изображения, отправленного как документ. Это может быть GIF или любое другое изображение
 */

class Size extends PhotoSize
{
    /**
     * @ignore
     */
    public function __construct(array $sourceData)
    {
        if (isset($sourceData["src"]))
        {
            $sourceData["url"] = $sourceData["src"];
        }
        parent::__construct($sourceData);
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
}