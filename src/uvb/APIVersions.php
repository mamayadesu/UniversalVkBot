<?php

namespace uvb;

class APIVersions
{
    /**
     * Последняя версия API
     */
    const API_VERSION = "7.0-pre-release-1.0.1";

    /**
     * Возвращает список поддерживаемых версий UniversalVkBot API
     *
     * @return array<int, string>
     */
    public static function Get() : array
    {
        return ["7.0-pre-release-0.1", "7.0-pre-release-0.2", "7.0-pre-release-0.3", "7.0-pre-release-0.4", "7.0-pre-release-1.0", self::API_VERSION];
    }

    /**
     * Возвращает последнюю поддерживаемую версию UniversalVkBot API (аналогично константе API_VERSION)
     *
     * @return string
     */
    public static function Last() : string
    {
        $arr = self::Get();
        return $arr[count($arr) - 1];
    }
}