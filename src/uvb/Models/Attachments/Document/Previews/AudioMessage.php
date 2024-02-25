<?php
declare(ticks = 1);

namespace uvb\Models\Attachments\Document\Previews;

use \Exception;

/**
 * Описывает модель превью аудио-сообщения.
 * Хотя непонятно как аудио-сообщение может быть превью, если при отправке аудио-сообщения оно отправляется как вложение, а не как документ
 */

class AudioMessage extends Preview
{
    /**
     * @ignore
     */
    private int $duration;

    /**
     * @var array<int>
     * @ignore
     */
    private array/*<int>*/ $waveform;

    /**
     * @ignore
     */
    private string $link_ogg, $link_mp3;

    /**
     * @ignore
     */
    public function __construct(array $sourceData)
    {
        if (!isset($sourceData["duration"]) || !is_integer($sourceData["duration"]))
        {
            throw new Exception("uvb\\Models\\Attachments\\Document\\Previews\\Preview: Invalid source data. 'duration' is missing or not int");
        }
        if (!isset($sourceData["waveform"]) || !is_array($sourceData["waveform"]))
        {
            throw new Exception("uvb\\Models\\Attachments\\Document\\Previews\\Preview: Invalid source data. 'waveform' is missing or not int");
        }
        if (!isset($sourceData["link_ogg"]) || !is_string($sourceData["link_ogg"]))
        {
            throw new Exception("uvb\\Models\\Attachments\\Document\\Previews\\Preview: Invalid source data. 'link_ogg' is missing or not int");
        }
        if (!isset($sourceData["link_mp3"]) || !is_string($sourceData["link_mp3"]))
        {
            throw new Exception("uvb\\Models\\Attachments\\Document\\Previews\\Preview: Invalid source data. 'link_mp3' is missing or not int");
        }

        $this->duration = $sourceData["duration"];
        $this->waveform = $sourceData["waveform"];
        $this->link_mp3 = $sourceData["link_mp3"];
        $this->link_ogg = $sourceData["link_ogg"];
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