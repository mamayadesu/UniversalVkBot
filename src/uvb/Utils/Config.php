<?php

namespace uvb\Utils;

use uvb\Bot;

/**
 * Класс-инструмент, позволяющий работать с конфигами. На данный момент поддерживается только JSON
 * @package uvb\Utils
 */

class Config
{
    /**
     * @ignore
     */
    private string $path;

    /**
     * @ignore
     */
    private array $data;

    /**
     * @ignore
     */
    private bool $autoSave, $closed = false;

    /**
     * @ignore
     */
    private $f;

    /**
     * Config constructor. Если файл не существует, создаёт его и заполняет данными по умолчанию
     * @param string $path Путь к файлу конфигурации
     * @param array $defaultData Содержимое конфига по умолчанию. Если файл не существует, создаёт его и заполняет указанными данными
     * @param bool $autoSave Авто-сохранение
     */
    public function __construct(string $path, array $defaultData = array(), bool $autoSave = false)
    {
        $this->autoSave = $autoSave;
        if (!file_exists($path))
        {
            $this->f = fopen($path, "a");
            fwrite($this->f, json_encode($defaultData, JSON_PRETTY_PRINT));
            $this->data = $defaultData;
        }
        else
        {
            $dataPreLoad1 = file_get_contents($path);
            $dataPreLoad = json_decode($dataPreLoad1, true);
            if ($dataPreLoad == null)
            {
                Bot::GetInstance()->GetLogger()->Error("An error occurred while loading config \"" . $path . "\". Loading default config data...");
                $this->data = $defaultData;
            }
            else
            {
                $this->data = $dataPreLoad;
            }
            $this->f = fopen($path, "a");
        }
    }

    /**
     * Получить значение параметра конфига по его ключу
     *
     * @param string $key Ключ
     * @param null $defaultValue Значение по умолчанию. Будет возвращено, если указанного ключа в конфиге нет
     * @param bool $saveDefaultIfNotExist Сохранять параметр со значением по умолчанию, если параметра с таким названием нет в конфиге
     * @return mixed|null Значение параметра. Будет возвращено NULL, если конфиг был закрыт
     */
    public function Get(string $key, /*mixed*/ $defaultValue = null, bool $saveDefaultIfNotExist = true) //: mixed
    {
        if ($this->closed)
        {
            return null;
        }
        $hasValue = isset($this->data[$key]);
        if (!$hasValue && $saveDefaultIfNotExist)
        {
            $this->Set($key, $defaultValue);
        }
        return ($hasValue ? $this->data[$key] : $defaultValue);
    }

    /**
     * Устанавливает значение параметра конфига по его ключу
     *
     * @param string $key Ключ
     * @param mixed $value Значение
     */
    public function Set(string $key, /*mixed*/ $value) : void
    {
        if ($this->closed)
        {
            return;
        }
        $this->data[$key] = $value;
        if ($this->autoSave)
        {
            $this->Save();
        }
    }

    /**
     * Сохраняет файл конфигурации. Не требуется, если ключено авто-сохранение
     */
    public function Save() : void
    {
        if ($this->closed)
        {
            return;
        }
        ftruncate($this->f, 0);
        fwrite($this->f, @json_encode($this->data, JSON_PRETTY_PRINT));
    }

    /**
     * Возвращает весь файл конфигурации в виде массива
     *
     * @return array|null Данные файла конфигурации в виде массива. Будет возвращено NULL, если конфиг был закрыт
     */
    public function GetAll() : ?array
    {
        if ($this->closed)
        {
            return null;
        }
        return $this->data;
    }

    /**
     * Закрывает файл конфигурации
     */
    public function Close() : void
    {
        if ($this->closed || $this->f == null)
        {
            $this->closed = true;
            return;
        }
        fclose($this->f);
        $this->data = array();
        $this->closed = true;
    }

    /**
     * Проверяет, был ли закрыт файл конфигурации
     *
     * @return bool TRUE - конфиг закрыт. FALSE - конфиг открыт
     */
    public function IsClosed() : bool
    {
        return $this->closed;
    }

    public function __destruct()
    {
        if (!$this->closed)
        {
            $this->Close();
        }
    }
}