<?php

namespace uvb\Services\Scheduler;

use \Closure;
use \Exception;
use Throwable;

/**
 * Модель, описывающая асинхронную задачу
 */

final class AsyncTask
{
    /**
     * @ignore
     */
    private Closure $TaskCallback;

    /**
     * @ignore
     */
    private ?Scheduler $Owner = null;

    /**
     * @ignore
     */
    private int $Interval, $TaskId;

    /**
     * @ignore
     */
    private bool $RunOnce, $Cancelled = false, $Executed = false;

    /**
     * @ignore
     */
    private array/*<mixed, mixed>*/ $Parameters;

    /**
     * @ignore
     */
    private float $NextExecution, $ExecutedTimes = 0;

    /**
     * @ignore
     */
    private bool $ExecutingRightNow = false;

    /**
     * @param int $Interval Интервал выполнения задачи в миллисекундах (может быть только кратен и не меньше 100)
     * @param bool $RunOnce Запустить задачу только один раз
     * @param callable $TaskCallback Callback-функция, которая будет выполняться асинхронно. Учтите, что функция ВСЕГДА находится в контексте основного класса плагина! В callback-функцию передаётся параметр, указывающий на объект этой задачи (AsyncTask)
     * @param array<mixed, mixed> $Parameters Дополнительные параметры
     * @throws Exception Выбрасывает исключение, если интервал не кратен 100 или меньше 100
     */
    public function __construct(int $Interval, bool $RunOnce, callable $TaskCallback, array $Parameters = array())
    {
        if ($Interval == 0 || $Interval < 100)
        {
            throw new Exception("Interval cannot be 0 or less than 100");
        }

        if (substr($Interval, -2) != "00")
        {
            throw new Exception("Interval must be multiple of 100");
        }
        $this->TaskCallback = $TaskCallback;
        $this->RunOnce = $RunOnce;
        $this->Interval = $Interval;
        $this->TaskId = TaskCounter::GetNext();
        $this->Parameters = $Parameters;
    }

    /**
     * @ignore
     */
    public function __setOwner(Scheduler $scheduler, SchedulerMaster $sm) : void
    {
        $this->Owner = $scheduler;
    }

    /**
     * Выполнить задачу вручную.
     * Обратите внимание, что после ручного выполнения задачи она получит статус "Выполнена" и время следующего выполнения изменится.
     * Также стоит заметить, если для задачи установлен параметр "Выполнить один раз", то задача больше не будет выполняться.
     * Задача не может выполняться рекурсивно
     *
     * @return void
     * @throws Exception Выбрасывает исключение, если для задачи не указан планировщик задач
     */
    public function Execute() : void
    {
        if ($this->Owner == null)
        {
            $this->Cancel();
            throw new \Exception("Task doesn't have an owner");
        }
        if (($this->Executed && $this->RunOnce) || $this->Cancelled || $this->ExecutingRightNow)
        {
            return;
        }

        // предотвращаем рекурсивное выполнение задачи
        $this->ExecutingRightNow = true;

        try
        {
            $this->TaskCallback->call($this->Owner->GetPlugin(), $this);
        }
        catch (Throwable $e)
        {
            $this->ExecutingRightNow = false;
            throw $e;
        }
        $this->ExecutingRightNow = false;
        $this->Executed = true;
        $this->NextExecution = floor(microtime(true) * 1000 + $this->Interval);
        if ($this->RunOnce)
        {
            $this->NextExecution = 0;
        }
        $this->ExecutedTimes++;
    }

    /**
     * Отменить задачу
     *
     * @return void
     */
    public function Cancel() : void
    {
        $this->Cancelled = true;
        $this->NextExecution = 0;

        unset($this->Owner);
    }

    /**
     * @return bool Отменена ли задача
     */
    public function IsCancelled() : bool
    {
        return $this->Cancelled;
    }

    /**
     * @return array<mixed, mixed> Дополнительные параметры
     */
    public function GetParameters() : array/*<mixed, mixed>*/
    {
        return $this->Parameters;
    }

    /**
     * @return bool Установлен ли параметр "Выполнить один раз"
     */
    public function IsOnce() : bool
    {
        return $this->RunOnce;
    }

    /**
     * @return bool Была ли задача выполнена хотя бы один раз
     */
    public function WasExecuted() : bool
    {
        return $this->Executed;
    }

    /**
     * @return float Время следующего выполнения задачи в формате Unixtime. Время возвращается целым числом в миллисекундах. Параметр возвращается типом float, поскольку число может иметь достаточно высокое значение и не поддерживаться на 32-битных системах.
     */
    public function GetNextExecution() : float
    {
        return $this->NextExecution;
    }

    /**
     * Задать новое время следующего выполнения задачи
     *
     * @param float $Time Время в формате Unixtime до миллисекунд. Несмотря на то, что параметр имеет тип float, число должно быть целым. Обязательно должно быть кратным 100. Обязательно должно быть хотя бы больше на 100 миллисекунд от текущего времени. Если время не указать, тогда оно автоматически задастся "текущее время + интервал"
     * @return void
     * @throws Exception Выбрасывается исключение, если указанное время не кратно 100 либо разница между указанным временем и текущим меньше 100 миллисекунд
     */
    public function SetNextExecution(float $Time = 0) : void
    {
        $Time = floor($Time);
        $now = floor(microtime(true) * 1000);
        if ($Time == 0)
        {
            $Time = $now + $this->Interval;
            while (substr($Time, -2) != "00")
            {
                $Time++;
            }
        }
        if ($Time - 100 <= $now)
        {
            throw new Exception("Next datetime of execution must be at least more on 100 milliseconds from now");
        }
        if (substr($Time, -2) != "00")
        {
            throw new Exception("Next execution time must be multiple of 100");
        }
        $this->NextExecution = $Time;
    }

    /**
     * @return int Идентификатор задачи
     */
    public function GetTaskId() : int
    {
        return $this->TaskId;
    }

    /**
     * @return float Сколько раз задача была выполнена
     */
    public function GetExecutedTimes() : float
    {
        return $this->ExecutedTimes;
    }
}