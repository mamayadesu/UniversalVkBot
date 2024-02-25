<?php
declare(ticks = 1);

namespace uvb\Models\Attachments\Audio;

use \Exception;

/**
 * Класс, описывающий информацию об исполнителе в ВК
 * @package uvb\Models\Attachments\Audio
 */

class AudioArtist
{
    /**
     * @ignore
     */
    private string $name, $domain, $id;

    /**
     * @ignore
     */
    public function __construct(array $sourceData)
    {
        if (!isset($sourceData["name"]) || !is_string($sourceData["name"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Audio\\Audio: Invalid source data. 'name' is missing or not integer");
        }

        if (!isset($sourceData["domain"]) || !is_string($sourceData["domain"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Audio\\Audio: Invalid source data. 'domain' is missing or not string");
        }

        if (!isset($sourceData["id"]) || !is_string($sourceData["id"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Audio\\Audio: Invalid source data. 'id' is missing or not integer");
        }

        $this->name = $sourceData["name"];
        $this->domain = $sourceData["domain"];
        $this->id = $sourceData["id"];
    }

    /**
     * Получить имя исполнителя
     *
     * Появилось в API: 1.0
     *
     * @return string Имя исполнителя
     */
    public function GetName() : string
    {
        return $this->name;
    }

    /**
     * Получить домен исполнителя (честно говоря, я так и не выяснил что это)
     *
     * Появилось в API: 1.0
     *
     * @return string Домен
     */
    public function GetDomain() : string
    {
        return $this->domain;
    }

    /**
     * Получить идентификатор исполнителя
     *
     * Появилось в API: 1.0
     *
     * @return string Идентификатор исполнителя
     */
    public function GetId() : string
    {
        return $this->id;
    }
}