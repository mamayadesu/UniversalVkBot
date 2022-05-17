<?php

namespace uvb\System;

final class SystemConfig
{

    /**
     * Получить параметр конфигурации бота
     *
     * @param string $key Имя параметра
     * @return mixed Значение параметра
     */
    public static function Get(string $key)
    {
        return SystemConfigResource::GetConfig()[$key];
    }
}