<?php
declare(ticks = 1);

namespace uvb;

final class APIVersions
{
    /**
     * Последняя версия API
     */
    const API_VERSION = "1.0";

    /**
     * Возвращает список поддерживаемых версий UniversalVkBot API
     *
     * Появилось в API: 1.0
     *
     * @return array<int, string>
     */
    public static function Get() : array
    {
        return [self::API_VERSION];
    }

    /**
     * Возвращает последнюю поддерживаемую версию UniversalVkBot API (аналогично константе API_VERSION)
     *
     * Появилось в API: 1.0
     *
     * @return string
     */
    public static function Last() : string
    {
        $arr = self::Get();
        return $arr[count($arr) - 1];
    }
}