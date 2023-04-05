<?php
declare(ticks = 1);

namespace uvb\Threads;

use Application\Application;
use Threading\Thread;
use uvb\Utils\CpuUsage;

// используется только в Linux. В Windows не поддерживается
/**
 * @ignore
 */
class CpuChecker extends Thread
{
    public function Threaded(array $args) : void
    {
        new CpuUsage();
        while ($this->IsParentStillRunning())
        {
            if (IS_WINDOWS)
            {
                $cpu = 0;
            }
            else
            {
                unset($output);
                exec("top -b -n 1 -d 0.2 -p " . $this->GetParentThreadPid() . " | tail -1 | awk '{print $9}'", $output, $result_code); // выводит хуйню в консоль. Исправить
                $cpu = -1;
                if (is_array($output) && isset($output[0]))
                {
                    $cpu = floatval($output[0]);
                }
            }
            $oldCpu = CpuUsage::GetValue();
            if ($oldCpu != $cpu)
            {
                CpuUsage::SetValue($cpu);
            }
        }
    }
}