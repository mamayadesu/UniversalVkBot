<?php
declare(ticks = 1);

namespace uvb\Models\Attachments\AudioMessage;

use uvb\Models\Attachments\Attachment;
use \Exception;
use uvb\Models\Attachments\AttachmentTypes;

class AudioMessage extends Attachment
{
    /**
     * @ignore
     */
    private int $duration;

    /**
     * @ignore
     */
    private string $link_mp3, $link_ogg;

    /**
     * @var array<int>
     */
    private array $waveform;


    public function __construct(array $sourceData)
    {
        parent::__construct($sourceData, AttachmentTypes::AUDIO_MESSAGE);

        if (!isset($sourceData["duration"]) || !is_integer($sourceData["duration"]))
        {
            throw new Exception("uvb\\Models\\Attachments\\AudioMessage\\AudioMessage: Invalid source data. 'duration' is missing or not integer");
        }
        if (!isset($sourceData["link_mp3"]) || !is_string($sourceData["link_mp3"]))
        {
            throw new Exception("uvb\\Models\\Attachments\\AudioMessage\\AudioMessage: Invalid source data. 'link_mp3' is missing or not string");
        }
        if (!isset($sourceData["link_ogg"]) || !is_string($sourceData["link_ogg"]))
        {
            throw new Exception("uvb\\Models\\Attachments\\AudioMessage\\AudioMessage: Invalid source data. 'link_ogg' is missing or not string");
        }
        if (!isset($sourceData["waveform"]) || !is_array($sourceData["waveform"]))
        {
            throw new Exception("uvb\\Models\\Attachments\\AudioMessage\\AudioMessage: Invalid source data. 'waveform' is missing or not array");
        }

        $this->duration = $sourceData["duration"];
        $this->link_mp3 = $sourceData["link_mp3"];
        $this->link_ogg = $sourceData["link_ogg"];
        $this->waveform = $sourceData["waveform"];
    }

    /**
     * Появилось в API: 1.0
     *
     * @return int Длительность аудио-файла
     */
    public function GetDuration() : int
    {
        return $this->duration;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return array<int> Массив целых чисел для визуального отображения звука
     */
    public function GetWaveForm() : array
    {
        return $this->waveform;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return string Ссылка на MP3-файл аудио-сообщения
     */
    public function GetMp3Link() : string
    {
        return $this->link_mp3;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return string Ссылка на OGG-файл аудио-сообщения
     */
    public function GetOggLink() : string
    {
        return $this->link_ogg;
    }
}