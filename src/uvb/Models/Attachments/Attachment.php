<?php

namespace uvb\Models\Attachments;

use uvb\Models\User;
use uvb\Repositories\UserRepository;

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

        if (in_array($mediaType, AttachmentTypes::GetValues()))
        {
            $this->mediaType = $mediaType;
        }
    }

    /**
     * Получить дату публикации вложения
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
     * @return int Индентификатор вложения
     */
    final function GetId() : int
    {
        return $this->id;
    }

    /**
     * Получить идентификатор пользователя или сообщества, опубликовавшего вложение
     *
     * @return int Число меньше нуля - идентификатор сообщества (без знака минус). Число больше нуля - идентификатор пользователя
     */
    final function GetOwnerId() : int
    {
        return $this->ownerId;
    }

    /**
     * Получить пользователя, опубликовавшего вложение
     *
     * @return User|null Объект пользователя, опубликовавшего вложение. Данный метод вернёт NULL, если вложение было опубликовано сообществом
     */
    final function GetOwner() : ?User
    {
        return UserRepository::Get($this->ownerId);
    }

    /**
     * Эквивалент метода GetOwner
     *
     * @return User|null Объект пользователя, опубликовавшего вложение. Данный метод вернёт NULL, если вложение было опубликовано сообществом
     */
    final function GetUser() : ?User
    {
        return $this->GetOwner();
    }

    /**
     * Получить ключ доступа. Ключ доступа необходим, например, если вложение было отправлено в личные сообщения, но нужна публичная ссылка на него
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
     * @return string Тип вложения (например, photo, video, doc и т.д.)
     */
    final function GetMediaType() : string
    {
        return $this->mediaType;
    }

    /**
     * Получить форматированную "ссылку" на изображение, как это устроено на сайте ВК
     * @return string Форматированная ссылка на вложение
     */
    public function GetFormatted() : string
    {
        return $this->mediaType . "_" . $this->ownerId . "_" . $this->ownerId . ($this->accessKey != "" ? "_" . $this->accessKey : "");
    }
}