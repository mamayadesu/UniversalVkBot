<?php

namespace uvb\Services\Scheduler;

use \Exception;
use uvb\Main;

/**
 * @ignore
 */
final class SchedulerMaster
{
    private static ?SchedulerMaster $instance = null;
    private Main $main;

    private array/*<int, ?AsyncTask>*/ $Queue = array();

    public function __construct(Main $main)
    {
        if (self::$instance != null)
        {
            throw new Exception("SchedulerMaster already initialized");
        }
        $this->main = $main;
        self::$instance = $this;
    }

    public function AddTaskToQueue(AsyncTask $task) : void
    {
        $this->Queue[$task->GetTaskId()] = $task;
    }

    public function Handle() : void
    {
        if (count($this->Queue) == 0)
            return;

        $time = intval(microtime(true) * 1000);
        foreach ($this->Queue as $TaskId => $Task)
        {if(!$Task instanceof AsyncTask)continue;
            if ($Task->IsCancelled() || ($Task->IsOnce() && $Task->WasExecuted()))
            {
                unset($this->Queue[$TaskId]);
                unset($Task);
                continue;
            }

            if ($Task->GetNextExecution() <= $time)
            {
                $Task->Execute();
            }
        }
    }
}