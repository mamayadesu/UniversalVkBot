<?php
declare(ticks = 1);

namespace uvb\Services;

use \Exception;
use Scheduler\AsyncTask;
use Scheduler\IAsyncTaskParameters;
use uvb\cmm;
use uvb\Models\Wall\Comment;
use uvb\Models\Wall\Post;
use uvb\Utils\CpuUsage;

/**
 * @ignore
 */
class ResourcesWatcher
{
    const WALL_CHECK_INTERVAL = 3600;

    private static ?ResourcesWatcher $instance = null;

    /**
     * @var AsyncTask[]
     */
    private array $tasks;

    private int $highCpuUsageDetected = 0, $highRamUsageDetected = 0;

    public function __construct()
    {
        if (self::$instance !== null)
        {
            throw new Exception("You are unable to initialize this class.");
        }

        if (CpuUsage::IsRunning())
        {
            $this->tasks["cpuWatcher"] = new AsyncTask($this, 1000, false, function(AsyncTask $task, IAsyncTaskParameters $params) : void { $this->AsyncCpuCheck($task, $params); });
        }
        $this->tasks["ramWatcher"] = new AsyncTask($this, 1000, false, function(AsyncTask $task, IAsyncTaskParameters $params) : void { $this->AsyncRamCheck($task, $params); });

        $this->tasks["postsCacheWatcher"] = new AsyncTask($this, self::WALL_CHECK_INTERVAL * 1000, false, function(AsyncTask $task, IAsyncTaskParameters $params) : void { $this->AsyncPostsCacheCheck($task, $params); });

        $this->tasks["commentsCacheWatcher"] = new AsyncTask($this, self::WALL_CHECK_INTERVAL * 1000, false, function(AsyncTask $task, IAsyncTaskParameters $params) : void { $this->AsyncCommentsCacheCheck($task, $params); });
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

    private function AsyncPostsCacheCheck(AsyncTask $task, IAsyncTaskParameters $params) : void
    {
        $keys_to_delete = [];
        foreach (Post::GetPostsCache() as $key => $post)
        {
            if ((time() - $post->GetLoadedDate()) > self::WALL_CHECK_INTERVAL)
            {
                $keys_to_delete[] = $key;
            }
        }

        foreach ($keys_to_delete as $key)
        {
            Post::DeleteFromCache($key);
        }
    }

    private function AsyncCommentsCacheCheck(AsyncTask $task, IAsyncTaskParameters $params) : void
    {
        $keys_to_delete = [];
        foreach (Comment::GetCommentsCache() as $key => $post)
        {
            if ((time() - $post->GetLoadedDate()) > self::WALL_CHECK_INTERVAL)
            {
                $keys_to_delete[] = $key;
            }
        }

        foreach ($keys_to_delete as $key)
        {
            Comment::DeleteFromCache($key);
        }
    }

    public function ShutdownTasks() : void
    {
        foreach ($this->tasks as $task)
        {
            $task->Cancel();
        }
    }
}