<?php

namespace uvb\Services\Scheduler;

use uvb\Plugin\Plugin;
use \Exception;

/**
 * Планировщик задач для плагинов
 * Данный класс предоставляет запускать асинхронные задачи с установленным интервалом.
 * Это позволит плагинам выполнять объёмные задачи по-итерационно, не занимая весь процесс сервера.
 * Учтите, что асинхронные задачи запускаются в одном потоке с сервером.
 */

final class Scheduler
{
    /**
     * @ignore
     */
    private ?Plugin $Plugin = null;

    /**
     * @ignore
     */
    private SchedulerMaster $Master;

    /**
     * @ignore
     */
    private array/*<int, Task>*/ $Tasks = array();

    /**
     * @ignore
     */
    public function __construct(SchedulerMaster $Master)
    {
        $this->Master = $Master;
    }

    /**
     * @ignore
     */
    public function __setPlugin(Plugin $plugin) : void
    {
        if ($this->Plugin != null)
        {
            throw new Exception("You're not allowed to call system method");
        }
        $this->Plugin = $plugin;
    }

    /**
     * Добавить задачу в планировщик
     *
     * @param AsyncTask $Task
     * @throws Exception
     */
    public function AddTask(AsyncTask $Task) : void
    {
        $Task->__setOwner($this, $this->Master);
        $Task->SetNextExecution();
        $this->Master->AddTaskToQueue($Task);
        $this->Tasks[$Task->GetTaskId()] = $Task;
    }

    /**
     * @return array<int, AsyncTask> Список всех задач планировщика. Ключ элемента - идентификатор задачи
     */
    public function GetTasks() : array/*<int, Task>*/
    {
        return $this->Tasks;
    }

    /**
     * Получить плагин, к которому относится данный планировщик
     *
     * @return Plugin|null
     */
    public function GetPlugin() : ?Plugin
    {
        return $this->Plugin;
    }

    /**
     * @ignore
     */
    public function __dispose() : void
    {
        unset($this->Plugin);
    }
}