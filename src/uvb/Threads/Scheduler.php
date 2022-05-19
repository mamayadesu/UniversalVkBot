<?php

namespace uvb\Threads;

use Application\Application;
use Threading\Thread;
use IO\Console;

/**
 * @ignore
 */

class Scheduler extends Thread
{
    private string $ip;
    private int $port;
    private bool $warned = false;

    public function Threaded(array $args) : void
    {
        $this->ip = $args[0];
        $this->port = intval($args[1]);
        $now = $now1 = 0;
        sleep(1);
        while (true)
        {
            $now = floor(microtime(true) * 1000);
            if (@file_get_contents("http://" . $this->ip . ":" . $this->port . "/scheduler"))
            {
                $now1 = floor(microtime(true) * 1000);
                $this->warned = false;
                self::millisleep(100 - ($now1 - $now));
            }
            else
            {
                if (!self::IsParentStillRunning())
                {
                    exit;
                }
                if (!$this->warned)
                {
                    Console::WriteLine("\nUniversalVkBot is unavailable for Scheduler service. Is server working correctly?");
                    $this->warned = true;
                }
            }
        }
    }

    public static function millisleep(int $milliseconds) : void
    {
        if ($milliseconds <= 0)
        {
            return;
        }
        time_nanosleep(0, ($milliseconds * 1000000) - 160000);
    }
}