<?php

namespace uvb\Threads;

use Application\Application;
use Threading\Thread;
use IO\Console;

/**
 * @ignore
 */

class Ticker extends Thread
{
    private string $ip;
    private int $port;

    public function Threaded(array $args) : void
    {
        //Application::SetTitle("UniversalVkBot Ticker Thread");
        //Console::WriteLine("Ticker thread started");
        $this->ip = $args[0];
        $this->port = intval($args[1]);
        sleep(2);
        while (true)
        {
            /*if (!self::IsParentStillRunning())
            {
                exit;
            }*/
            if (@file_get_contents("http://" . $this->ip . ":" . $this->port . "/ticker"))
            {
                $this->millisleep(500);
            }
            else
            {
                exit;
            }
        }
    }

    private function millisleep(int $milliseconds) : void
    {
        $end = floor(microtime(true) * 1000) + $milliseconds;
        while (floor(microtime(true) * 1000) <= $end)
        {

        }
    }
}