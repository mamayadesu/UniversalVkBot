<?php
declare(ticks = 1);

namespace uvb\Models\Attachments;

use uvb\Models\Entity;
use uvb\Models\Group;
use uvb\Models\User;

/**
 * Класс, описывающий любое вложение
 * @package uvb\Models\Attachments
 */

class Attachment implements IAttachment
{
    /**
     * @ignore
     */
    protected int $date = 0, $id = 0, $ownerId = 0;

    /**
     * @ignore
     */
    protected string $mediaType = "unknown", $accessKey = "";

    /**
     * @ignore
     */
    public function __construct(array $sourceData, string $mediaType)
    {
        if (isset($sourceData["date"]) && is_int($sourceData["date"]))
        {
            $this->date = $sourceData["date"];
        }

        if (isset($sourceData["id"]) && is_int($sourceData["id"]))
        {
            $this->id = $sourceData["id"];
        }

        if (isset($sourceData["owner_id"]) && is_int($sourceData["owner_id"]))
        {
            $this->ownerId = $sourceData["owner_id"];
        }

        if (isset($sourceData["access_key"]) && is_string($sourceData["access_key"]))
        {
            $this->accessKey = $sourceData["access_key"];
        }

        $mediaType = strtolower($mediaType);

        if (AttachmentTypes::HasItem($mediaType))
        {
            $this->mediaType = $mediaType;
        }
    }

    /**
     * Получить дату публикации вложения
     *
     * Появилось в API: 1.0
     *
     * @return int Возвращает дату публикации изображения в формате Unixtime
     */
    final function GetDate() : int
    {
        return $this->date;
    }

    /**
     * Получить идентификатор вложения
     *
     * Появилось в API: 1.0
     *
     * @return int Индентификатор вложения
     */
    final function GetId() : int
    {
        return $this->id;
    }

    /**
     * Получить идентификатор пользователя или сообщества, опубликовавшего вложение
     *
     * Появилось в API: 1.0
     *
     * @return int Число меньше нуля - идентификатор сообщества (без знака минус). Число больше нуля - идентификатор пользователя
     */
    final function GetOwnerId() : int
    {
        return $this->ownerId;
    }

    /**
     * Получить пользователя или сообщество, опубликовавшего вложение
     *
     * Появилось в API: 1.0
     *
     * @return Entity|null Сущность (пользователь или сообщество)
     */
    final function GetOwner() : ?Entity
    {
        if ($this->ownerId > 0)
        {
            return User::Get($this->ownerId);
        }
        else
        {
            return Group::Get($this->ownerId);
        }
    }

    /**
     * Получить ключ доступа. Ключ доступа необходим, например, если вложение было отправлено в личные сообщения, но нужна публичная ссылка на него
     *
     * Появилось в API: 1.0
     *
     * @return string Ключ доступа к изображению
     */
    final function GetAccessKey() : string
    {
        return $this->accessKey;
    }

    /**
     * Получить тип вложения
     *
     * Появилось в API: 1.0
     *
     * @return string Тип вложения (например, photo, video, doc и т.д.)
     */
    final function GetMediaType() : string
    {
        return $this->mediaType;
    }

    /**
     * Получить форматированную "ссылку" на изображение, как это устроено на сайте ВК
     *
     * Появилось в API: 1.0
     *
     * @return string Форматированная ссылка на вложение
     */
    public function GetFormatted() : string
    {
        return $this->mediaType . "_" . $this->ownerId . "_" . $this->ownerId . ($this->accessKey != "" ? "_" . $this->accessKey : "");
    }
}