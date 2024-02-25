<?php
declare(ticks = 1);

namespace uvb\Models\Attachments\Video;

use Couchbase\Group;
use \Exception;
use uvb\Bot;
use uvb\System\SystemConfig;
use uvb\Models\Attachments\Attachment;
use uvb\Models\Attachments\AttachmentTypes;
use VK\Client\VKApiClient;

class Video extends Attachment
{
    /**
     * @ignore
     */
    private bool $canEdit = false, $canAdd = false, $canAttachLink = false, $isFavourite = false;

    /**
     * @ignore
     */
    private string $description, $title, $trackCode = "";

    /**
     * @ignore
     */
    private int $duration, $width = 0, $height = 0, $views;

    /**
     * @var array<VideoImage>
     * @ignore
     */
    private array/*<VideoImage>*/ $image, $firstFrame = [];

    /**
     * @ignore
     */
    public function __construct(array $sourceData)
    {
        parent::__construct($sourceData, AttachmentTypes::VIDEO);

        if (isset($sourceData["can_edit"]))
        {
            if (is_int($sourceData["can_edit"]))
            {
                $this->canEdit = ($sourceData["can_edit"] != 0);
            }
            else if (is_bool($sourceData["can_edit"]))
            {
                $this->canEdit = $sourceData["can_edit"];
            }
            else
            {
                throw new Exception("\\uvb\\Models\\Attachments\\Video\\Video: Invalid source data. 'can_edit' must be an integer or a bool, " . gettype($sourceData["can_edit"] . " given"));
            }
        }

        if (isset($sourceData["can_add"]))
        {
            if (is_int($sourceData["can_add"]))
            {
                $this->canAdd = ($sourceData["can_add"] != 0);
            }
            else if (is_bool($sourceData["can_add"]))
            {
                $this->canAdd = $sourceData["can_add"];
            }
            else
            {
                throw new Exception("\\uvb\\Models\\Attachments\\Video\\Video: Invalid source data. 'can_add' must be an integer or a bool, " . gettype($sourceData["can_add"] . " given"));
            }
        }

        if (isset($sourceData["can_attach_link"]))
        {
            if (is_int($sourceData["can_attach_link"]))
            {
                $this->canAttachLink = ($sourceData["can_attach_link"] != 0);
            }
            else if (is_bool($sourceData["can_attach_link"]))
            {
                $this->canAttachLink = $sourceData["can_attach_link"];
            }
            else
            {
                throw new Exception("\\uvb\\Models\\Attachments\\Video\\Video: Invalid source data. 'can_attach_link' must be an integer or a bool, " . gettype($sourceData["can_attach_link"] . " given"));
            }
        }

        if (isset($sourceData["is_favourite"]))
        {
            if (is_int($sourceData["is_favourite"]))
            {
                $this->isFavourite = ($sourceData["is_favourite"] != 0);
            }
            else if (is_bool($sourceData["can_attach_link"]))
            {
                $this->isFavourite = $sourceData["is_favourite"];
            }
            else
            {
                throw new Exception("\\uvb\\Models\\Attachments\\Video\\Video: Invalid source data. 'is_favourite' must be an integer or a bool, " . gettype($sourceData["is_favourite"] . " given"));
            }
        }

        if (!isset($sourceData["width"]))
        {
            $sourceData["width"] = 0;
        }
        if (!isset($sourceData["height"]))
        {
            $sourceData["height"] = 0;
        }
        if (!isset($sourceData["first_frame"]))
        {
            $sourceData["first_frame"] = [];
        }
        $keys = ["description", "title", "track_code", "duration", "width", "height", "views", "image", "first_frame"];
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
            throw new Exception("\\uvb\\Models\\Attachments\\Video\\Video: Invalid source data. The next keys are missing: " . implode(", ", $noKeys));
        }
        if (!is_string($sourceData["description"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Video\\Video: Invalid source data. 'is_favourite' must be a string, " . gettype($sourceData["description"] . " given"));
        }
        if (!is_string($sourceData["title"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Video\\Video: Invalid source data. 'title' must be a string, " . gettype($sourceData["title"] . " given"));
        }
        if (!is_string($sourceData["track_code"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Video\\Video: Invalid source data. 'title' must be a string, " . gettype($sourceData["title"] . " given"));
        }

        if (!is_int($sourceData["duration"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Video\\Video: Invalid source data. 'duration' must be an integer, " . gettype($sourceData["duration"] . " given"));
        }

        if (!is_int($sourceData["width"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Video\\Video: Invalid source data. 'width' must be an integer, " . gettype($sourceData["width"] . " given"));
        }

        if (!is_int($sourceData["height"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Video\\Video: Invalid source data. 'height' must be an integer, " . gettype($sourceData["height"] . " given"));
        }

        if (!is_int($sourceData["views"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Video\\Video: Invalid source data. 'views' must be an integer, " . gettype($sourceData["views"] . " given"));
        }

        if (!is_array($sourceData["image"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Video\\Video: Invalid source data. 'image' must be an array, " . gettype($sourceData["image"] . " given"));
        }

        if (!is_array($sourceData["first_frame"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Video\\Video: Invalid source data. 'first_frame' must be an array, " . gettype($sourceData["first_frame"] . " given"));
        }
        $this->description = $sourceData["description"];
        $this->title = $sourceData["title"];
        $this->trackCode = $sourceData["track_code"];
        $this->duration = $sourceData["duration"];
        $this->width = $sourceData["width"];
        $this->height = $sourceData["height"];
        $this->views = $sourceData["views"];
        $img = null;
        foreach ($sourceData["image"] as $sourceImage)
        {
            try
            {
                $img = new VideoImage($sourceImage);
            }
            catch (Exception $e)
            {
                throw $e;
            }
            $this->image[] = $img;
        }

        foreach ($sourceData["first_frame"] as $sourceImage)
        {
            try
            {
                $img = new VideoImage($sourceImage);
            }
            catch (Exception $e)
            {
                throw $e;
            }
            $this->firstFrame[] = $img;
        }
    }

    /**
     * Появилось в API: 1.0
     *
     * @return bool TRUE - видео можно изменить
     */
    public function CanEdit() : bool
    {
        return $this->canEdit;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return bool TRUE - видео можно добавить в другой альбом
     */
    public function CanAdd() : bool
    {
        return $this->canAdd;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return bool TRUE - ...???
     */
    public function CanAttachLink() : bool
    {
        return $this->canAttachLink;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return bool !!!???
     */
    public function IsFavourite() : bool
    {
        return $this->isFavourite;
    }

    /**
     * Получить описание видео
     *
     * Появилось в API: 1.0
     *
     * @return string Описание видео
     */
    public function GetDescription() : string
    {
        return $this->description;
    }

    /**
     * Получить название видео
     *
     * Появилось в API: 1.0
     *
     * @return string Название видео
     */
    public function GetTitle() : string
    {
        return $this->title;
    }

    /**
     * Получить трек-код видео
     *
     * Появилось в API: 1.0
     *
     * @return string Трек-код видео
     */
    public function GetTrackCode() : string
    {
        return $this->trackCode;
    }

    /**
     * Получить продолжительность видео
     *
     * Появилось в API: 1.0
     *
     * @return int продолжительность видео в секундах
     */
    public function GetDuration() : int
    {
        return $this->duration;
    }

    /**
     * Получить длину разрешения видео
     *
     * Появилось в API: 1.0
     *
     * @return int Длина разрешения видео
     */
    public function GetWidth() : int
    {
        return $this->width;
    }

    /**
     * Получить высоту разрешения видео
     *
     * Появилось в API: 1.0
     *
     * @return int Высоту разрешения видео
     */
    public function GetHeight() : int
    {
        return $this->height;
    }

    /**
     * Получить количество просмотров
     *
     * Появилось в API: 1.0
     *
     * @return int Количество просмотров видео
     */
    public function GetViews() : int
    {
        return $this->views;
    }

    /**
     * Получить список кадров в видео
     *
     * Появилось в API: 1.0
     *
     * @return array<VideoImage> Список объектов VideoImages, хранящие информацию о кадрах в видео
     */
    public function GetImage() : array
    {
        return $this->image;
    }

    /**
     * Получить список первых кадров в видео
     *
     * Появилось в API: 1.0
     *
     * @return array<VideoImage> Список объектов VideoImage, хранящие информацию о первых кадрах в видео
     */
    public function GetFirstFrame() : array
    {
        return $this->firstFrame;
    }

    /**
     * Получить ссылки на исходные файлы видео
     *
     * ВНИМАНИЕ! На данный момент этот метод НЕ РАБОТАЕТ. Это связано с VK API, в котором, на данный момент, боты не могут использовать метод video.get. Неизвестно, когда разработчики VK API добавят этот метод для бота и добавят ли вообще.
     *
     * Появилось в API: 1.0
     *
     * @return array Список URL на исходные файлы видео
     * @throws Exception
     */
    public function GetDownloadLinks(?Group $by_group = null) : array
    {
        if ($by_group === null)
        {
            $by_group = Bot::GetInstance()->GetDefaultGroup();
        }
        $video = Bot::GetVkApi()->video();
        $videoId = $this->ownerId . "_" . $this->id . ($this->accessKey != "" ? "_" . $this->accessKey : "");
        $params = array
        (
            "owner_id" => $this->ownerId,
            "videos" => $videoId,
            "count" => 1
        );
        try
        {
            $response = $video->get($by_group->GetAccessToken(), $params);
        }
        catch (Exception $e)
        {
            throw $e;
        }
        if (!isset($response["items"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Video\\Video::GetDownloadLinks(): Key 'items' not found");
        }
        if (!isset($response["items"][0]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Video\\Video::GetDownloadLinks(): Key 'items'[0] not found");
        }
        if (!isset($response["items"][0]["files"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Video\\Video::GetDownloadLinks(): Key 'items'[0]'files' not found");
        }
        return $response["items"][0]["files"];
    }
}