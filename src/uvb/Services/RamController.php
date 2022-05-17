<?php

namespace uvb\Services;

use Data\String\ForegroundColors;
use IO\Console;
use uvb\Main;
use \Exception;

/**
 * Сервис контроля оперативной памяти
 */

class RamController
{
    /**
     * @ignore
     */
    private static ?RamController $instance = null;

    /**
     * @ignore
     */
    private int $TotalMemory = 0, $AllocatedMemory = -1;

    /**
     * @ignore
     */
    private Main $main;

    /**
     * @ignore
     */
    public function __construct(Main $main)
    {
        if (self::$instance != null)
        {
            throw new Exception("RamController is already started!");
        }
        $this->main = $main;
        ini_set("memory_limit", "-1");
        self::$instance = $this;
        if (IS_WINDOWS)
        {
            exec("wmic MEMORYCHIP get Capacity", $output, $result_code);
            foreach ($output as $line)
            {
                $this->TotalMemory += intval($line);
            }
        }
        else
        {
            exec("grep MemTotal /proc/meminfo | awk '{print $2 / 1024}'", $output, $result_code);
            $this->TotalMemory = intval(floatval($output[0]) * 1000);
        }
    }

    /**
     * @ignore
     */
    public static function GetInstance() : ?RamController
    {
        return self::$instance;
    }

    /**
     * @return int Процент использования оперативной памяти
     */
    public function GetUsagePercent() : int
    {
        if ($this->AllocatedMemory == -1 || $this->TotalMemory == 0)
        {
            return 0;
        }
        $usage = memory_get_usage(false);
        $total = $this->TotalMemory;

        $percent = $usage / $total * 100;
        return $percent;
    }

    /**
     * @return int Использование оперативной памяти в байтах
     */
    public function GetUsage() : int
    {
        return memory_get_usage(false);
    }

    /**
     * @return int Оперативная память машины
     */
    public function GetTotalMemory() : int
    {
        return $this->TotalMemory;
    }

    /**
     * @return int Выделенная оперативная память в байтах
     */
    public function GetAllocatedMemory() : int
    {
        return $this->AllocatedMemory;
    }

    /**
     * Устанавливает выделенную оперативную память в байтах
     *
     * @param int $memory
     * @return void
     * @throws Exception
     */
    public function SetAllocatedMemory(int $memory) : void
    {
        if ($memory < -1 || $memory == 0)
        {
            throw new Exception("Allocated memory can be higher than zero or can be -1");
        }

        if ($memory == -1)
            $this->AllocatedMemory = $this->TotalMemory;
        else
            $this->AllocatedMemory = $memory;
    }

    /**
     * @ignore
     */
    public function Check() : void
    {
        ini_set("memory_limit", "-1");
        if ($this->main->bot != null && $this->main->bot->IsShuttingDown())
        {
            return;
        }
        $usage = $this->GetUsage();

        if ($usage > $this->AllocatedMemory && $this->AllocatedMemory != -1)
        {
            if ($this->main->bot != null)
            {
                $this->main->bot->GetLogger()->Critical("!!!!! UNIVERSALVKBOT IS OUT OF MEMORY. EMERGENCY SHUTTING DOWN!!!");
                $this->main->bot->Shutdown();
            }
            else
            {
                Console::WriteLine("UNIVERSALVKBOT IS OUT OF MEMORY. ABORTING SYSTEM STARTING.", ForegroundColors::RED);
                var_dump($usage);
                var_dump($this->AllocatedMemory);
                exit;
            }
        }
    }

    /**
     * Переводит такие числа, как 256K, 1G, 1024M в байты
     *
     * @param string $memory
     * @return int
     */
    public static function ParseMemory(string $memory) : int
    {
        sscanf($memory, '%u%c', $number, $suffix);
        if (isset($suffix))
        {
            $number = $number * pow(1024, strpos(" KMG", strtoupper($suffix)));
        }
        return intval($number);
    }
}