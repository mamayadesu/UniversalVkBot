<?php
declare(ticks = 1);

namespace uvb\Utils;

use \Exception;
use Threading\SuperGlobalArray;
use uvb\Main;

/**
 * Информация об использовании CPU (только для Linux)
 */

class CpuUsage
{
    /**
     * @ignore
     */
    private static ?CpuUsage $instance = null;

    /**
     * @ignore
     */
    private SuperGlobalArray $sga;

    /**
     * @ignore
     */
    private float $cpu = 0;

    /**
     * @ignore
     */
    private float $lastCpuChecked = 0;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        if (self::$instance != null)
            throw new Exception("Not initializable class");

        self::$instance = $this;
        $this->cpu = 0;
        $this->lastCpuChecked = 0;
        $this->sga = SuperGlobalArray::GetInstance();
    }

    /**
     * @return float Процент использования CPU. Работает только Linux-системах. Для Windows этот метод всегда возвращает ноль.
     */
    public static function GetValue() : float
    {
        $me = self::$instance;
        if (IS_WINDOWS) return 0;
        $time = intval(microtime(true) * 1000);
        if ($time >= $me->lastCpuChecked + 100)
        {
            $me->lastCpuChecked = $time;
            $me->cpu = $me->sga->Get(["cpu_usage"]);
        }
        return $me->cpu;
    }

    /**
     * @ignore
     */
    public static function SetValue(float $value) : void
    {
        $me = self::$instance;
        $me->sga->Set(["cpu_usage"], $value);
    }
}