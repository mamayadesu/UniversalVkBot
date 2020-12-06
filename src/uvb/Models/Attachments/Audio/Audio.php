<?php

namespace uvb\Models\Attachments\Audio;

use \Exception;
use uvb\Models\Attachments\Attachment;
use uvb\Models\Attachments\AttachmentTypes;

/**
 * Класс, описывающий аудиозапись в ВК
 * @package uvb\Models\Attachments\Audio
 */

class Audio extends Attachment
{
    /**
     * @ignore
     */
    private string $artist, $title, $trackCode, $url, $subtitle = "";

    /**
     * @ignore
     */
    private int $duration, $genreId = -1;

    /**
     * @ignore
     */
    private bool $isExplicit, $isFocusTrack, $shortVideosAllowed, $storiesAllowed, $storiesCoverAllowed, $noSearch = false;

    /**
     * @ignore
     */
    private array/*<AudioArtist>*/ $mainArtists = [], $featuredArtists = [];

    /**
     * @ignore
     */
    public function __construct(array $sourceData)
    {
        parent::__construct($sourceData, AttachmentTypes::AUDIO);

        if (!isset($sourceData["subtitle"]))
        {
            $sourceData["subtitle"] = "";
        }

        if (!isset($sourceData["no_search"]) || (isset($sourceData["no_search"]) && !is_int($sourceData["no_search"]) && !is_bool($sourceData["no_search"])))
        {
            $sourceData["no_search"] = false;
        }
        else if (is_int($sourceData["no_search"]))
        {
            $sourceData["no_search"] = !($sourceData["no_search"] == 0);
        }

        if (!isset($sourceData["featured_artists"]))
        {
            $sourceData["featured_artists"] = [];
        }

        if (!isset($sourceData["main_artists"]))
        {
            $sourceData["main_artists"] = [];
        }

        if (!isset($sourceData["genre_id"]))
        {
            $sourceData["genre_id"] = -1;
        }

        $strings = ["artist", "title", "track_code", "url", "subtitle"];
        $ints = ["duration", "genre_id"];
        $bools = ["is_explicit", "is_focus_track", "short_videos_allowed", "stories_allowed", "stories_cover_allowed"];
        $arrays = ["main_artists", "featured_artists"];

        if (isset($sourceData["featured_artists"]) && !is_array($sourceData["featured_artists"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Audio\\Audio: Invalid source data. 'featured_artists' must be an array, " . gettype($sourceData["featured_artists"] . " given"));
        }
        $noKeys = [];
        foreach ($strings as $item)
        {
            if (!isset($sourceData[$item]))
            {
                $noKeys[] = $item;
                continue;
            }

            if (!is_string($sourceData[$item]))
            {
                throw new Exception("\\uvb\\Models\\Attachments\\Audio\\Audio: Invalid source data. '" . $item . "' must be a string, " . gettype($sourceData[$item] . " given"));
            }
        }

        foreach ($ints as $item)
        {
            if (!isset($sourceData[$item]))
            {
                $noKeys[] = $item;
                continue;
            }

            if (!is_int($sourceData[$item]))
            {
                throw new Exception("\\uvb\\Models\\Attachments\\Audio\\Audio: Invalid source data. '" . $item . "' must be an integer, " . gettype($sourceData[$item] . " given"));
            }
        }

        foreach ($bools as $item)
        {
            if (!isset($sourceData[$item]))
            {
                $noKeys[] = $item;
                continue;
            }

            if (!is_bool($sourceData[$item]))
            {
                throw new Exception("\\uvb\\Models\\Attachments\\Audio\\Audio: Invalid source data. '" . $item . "' must be a boolean, " . gettype($sourceData[$item] . " given"));
            }
        }

        foreach ($arrays as $item)
        {
            if (!isset($sourceData[$item]))
            {
                $noKeys[] = $item;
                continue;
            }

            if (!is_array($sourceData[$item]))
            {
                throw new Exception("\\uvb\\Models\\Attachments\\Audio\\Audio: Invalid source data. '" . $item . "' must be an array, " . gettype($sourceData[$item] . " given"));
            }
        }

        if (count($noKeys) > 0)
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Audio\\Audio: Invalid source data. The next keys are missing: " . implode(", ", $noKeys));
        }

        $artist = null;
        foreach ($sourceData["main_artists"] as $sourceArtist)
        {
            try
            {
                $artist = new AudioArtist($sourceArtist);
            }
            catch (Exception $e)
            {
                throw $e;
                continue;
            }
            $this->mainArtists[] = $artist;
        }
        foreach ($sourceData["featured_artists"] as $sourceArtist)
        {
            try
            {
                $artist = new AudioArtist($sourceArtist);
            }
            catch (Exception $e)
            {
                throw $e;
                continue;
            }
            $this->featuredArtists[] = $artist;
        }

        $this->artist = $sourceData["artist"];
        $this->title = $sourceData["title"];
        $this->trackCode = $sourceData["track_code"];
        $this->url = $sourceData["url"];
        $this->subtitle = $sourceData["subtitle"];
        $this->duration = $sourceData["duration"];
        $this->genreId = $sourceData["genre_id"];
        $this->isExplicit = $sourceData["is_explicit"];
        $this->isFocusTrack = $sourceData["is_focus_track"];
        $this->shortVideosAllowed = $sourceData["short_videos_allowed"];
        $this->storiesAllowed = $sourceData["stories_allowed"];
        $this->storiesCoverAllowed = $sourceData["stories_cover_allowed"];
        $this->noSearch = $sourceData["no_search"];
    }

    /**
     * Получить имя исполнителя
     *
     * @return string Имя исполнителя
     */
    public function GetArtist() : string
    {
        return $this->artist;
    }

    /**
     * Получить название трека
     *
     * @return string Название аудиозаписи
     */
    public function GetTitle() : string
    {
        return $this->title;
    }

    /**
     * Получить трек-код
     *
     * @return string Трек-код
     */
    public function GetTrackCode() : string
    {
        return $this->trackCode;
    }

    /**
     * Получить URL на mp3-файл
     *
     * @return string Полная ссылка на MP3-файл
     */
    public function GetUrl() : string
    {
        return $this->url;
    }

    /**
     * Получить пометку к треку (Remix, Original Mix и т.п.)
     *
     * @return string Пометка к треку
     */
    public function GetSubtitle() : string
    {
        return $this->subtitle;
    }

    /**
     * Получить продолжительность трека
     *
     * @return int Продолжительность трека в секундах
     */
    public function GetDuration() : int
    {
        return $this->duration;
    }

    /**
     * Получить ID жанра песни
     *
     * @return int Номер жанра песни
     */
    public function GetGenreId() : int
    {
        return $this->genreId;
    }

    public function IsExplicit() : bool
    {
        return $this->isExplicit;
    }

    public function IsFocusTrack() : bool
    {
        return $this->isFocusTrack;
    }

    public function ShortVideosAllowed() : bool
    {
        return $this->shortVideosAllowed;
    }

    public function StoriesAllowed() : bool
    {
        return $this->storiesAllowed;
    }

    public function StoriesCoverAllowed() : bool
    {
        return $this->storiesCoverAllowed;
    }

    /**
     * Скрыт ли трек из поиска
     *
     * @return bool Скрыт ли трек из поиска
     */
    public function NoSearch() : bool
    {
        return $this->noSearch;
    }

    public function GetMainArtists() : array/*<AudioArtist>*/
    {
        return $this->mainArtists;
    }

    public function GetFeaturedArtists() : array/*<AudioArtist>*/
    {
        return $this->featuredArtists;
    }
}