<?php

namespace uvb;

class Config
{

    /**
     * Получить параметр конфигурации бота
     *
     * @param string $key Имя параметра
     * @return mixed Значение параметра
     */
    public static function Get(string $key)
    {
        return ConfigResource::GetConfig()[$key];
    }
}