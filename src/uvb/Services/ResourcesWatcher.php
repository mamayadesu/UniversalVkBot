<?php

namespace uvb\Services;

use \Exception;
use Scheduler\AsyncTask;
use Scheduler\IAsyncTaskParameters;
use uvb\cmm;
use uvb\Utils\CpuUsage;

class ResourcesWatcher
{
    private static ?ResourcesWatcher $instance = null;

    private ?AsyncTask $ramWatcher = null, $cpuWatcher = null;

    private int $highCpuUsageDetected = 0, $highRamUsageDetected = 0;

    public function __construct()
    {
        if (self::$instance !== null)
        {
            throw new Exception("You are unable to initialize this class.");
        }

        if (CpuUsage::IsRunning())
        {
            $this->cpuWatcher = new AsyncTask($this, 1000, false, function(AsyncTask $task, IAsyncTaskParameters $params) : void { $this->AsyncCpuCheck($task, $params); });
        }
        $this->ramWatcher = new AsyncTask($this, 1000, false, function(AsyncTask $task, IAsyncTaskParameters $params) : void { $this->AsyncRamCheck($task, $params); });
    }

    private function AsyncCpuCheck(AsyncTask $task, IAsyncTaskParameters $params) : void
    {
        $value = CpuUsage::GetValue();

        if ($value > 85)
        {
            if ($this->highCpuUsageDetected == 0)
            {
                $this->highCpuUsageDetected = time();
            }
            else if (time() - $this->highCpuUsageDetected > 300)
            {
                cmm::w("resourcewarning.high_cpu", [$value]);
                $this->highCpuUsageDetected = 0;
            }
        }
        else
        {
            $this->highCpuUsageDetected = 0;
        }
    }

    private function AsyncRamCheck(AsyncTask $task, IAsyncTaskParameters $params) : void
    {
        $value = RamController::GetInstance()->GetUsagePercent();

        if ($value > 85)
        {
            if ($this->highRamUsageDetected == 0)
            {
                $this->highRamUsageDetected = time();
            }
            else if (time() - $this->highRamUsageDetected > 300)
            {
                cmm::w("resourcewarning.high_ram", [$value]);
                $this->highRamUsageDetected = time();
            }
        }
        else
        {
            $this->highRamUsageDetected = 0;
        }
    }

    public function ShutdownTasks() : void
    {
        $this->ramWatcher->Cancel();

        if ($this->cpuWatcher !== null)
        {
            $this->cpuWatcher->Cancel();
        }
    }
}