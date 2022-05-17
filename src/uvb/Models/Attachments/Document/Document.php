<?php

namespace uvb\Models\Attachments\Document;

use uvb\Models\Attachments\Attachment;
use uvb\Models\Attachments\AttachmentTypes;
use uvb\Models\Attachments\Document\Previews\AudioMessage;
use uvb\Models\Attachments\Document\Previews\Graffiti;
use uvb\Models\Attachments\Document\Previews\Photo;
use uvb\Models\Attachments\Document\Previews\Preview;
use \Exception;

class Document extends Attachment
{
    /**
     * @ignore
     */
    private string $title, $url, $ext;

    /**
     * @ignore
     */
    private int $size;

    /**
     * @ignore
     */
    private ?Video $video = null;

    /**
     * @ignore
     */
    private ?Preview $preview = null;

    /**
     * @ignore
     */
    public function __construct(array $sourceData)
    {
        parent::__construct($sourceData, AttachmentTypes::DOCUMENT);

        if (!isset($sourceData["title"]) || !is_string($sourceData["title"]))
        {
            throw new Exception("uvb\\Models\\Attachments\\Document\\Document: Invalid source data. 'title' is missing or not string");
        }
        if (!isset($sourceData["url"]) || !is_string($sourceData["url"]))
        {
            throw new Exception("uvb\\Models\\Attachments\\Document\\Document: Invalid source data. 'url' is missing or not string");
        }
        if (!isset($sourceData["ext"]) || !is_string($sourceData["ext"]))
        {
            throw new Exception("uvb\\Models\\Attachments\\Document\\Document: Invalid source data. 'ext' is missing or not string");
        }
        if (!isset($sourceData["size"]) || !is_int($sourceData["size"]))
        {
            throw new Exception("uvb\\Models\\Attachments\\Document\\Document: Invalid source data. 'size' is missing or not string");
        }

        $this->title = $sourceData["title"];
        $this->url = $sourceData["url"];
        $this->ext = $sourceData["ext"];
        $this->size = $sourceData["size"];

        if (isset($sourceData["preview"]) && is_array($sourceData["preview"]))
        {
            $firstArrayElement = null;
            $type = "";
            foreach ($sourceData["preview"] as $t => $value)
            {
                $firstArrayElement = $value;
                $type = $t;
                break;
            }

            switch ($type)
            {
                case "audio_message":
                    $this->preview = new AudioMessage($firstArrayElement);
                    break;

                case "graffiti":
                    $this->preview = new Graffiti($firstArrayElement);
                    break;

                case "photo":
                    $this->preview = new Photo($firstArrayElement);
                    break;
            }
        }

        if (isset($sourceData["video"]) && is_array($sourceData["video"]))
        {
            $this->video = new Video($sourceData["video"]);
        }
    }

    /**
     * @return string Название файла вместе с его расширением
     */
    public function GetTitle() : string
    {
        return $this->title;
    }

    /**
     * @return string Прямая ссылка на скачивание файла
     */
    public function GetUrl() : string
    {
        return $this->url;
    }

    /**
     * @return string Расширение файла
     */
    public function GetFileExtension() : string
    {
        return $this->ext;
    }

    /**
     * @return int Размер файла в байтах
     */
    public function GetFileSize() : int
    {
        return $this->size;
    }

    /**
     * @return Video|null Если это анимированное GIF-изображение, можно также получить MP4-формат
     */
    public function GetVideo() : ?Video
    {
        return $this->video;
    }

    /**
     * @return Preview|null Превью документа, если таковое имеется
     */
    public function GetPreview() : ?Preview
    {
        return $this->preview;
    }
}