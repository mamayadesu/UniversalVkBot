<?php

namespace uvb\System;

use Threading\Thread;

/**
 * @ignore
 */

class ExitCode extends Thread
{
    private int $exitCode = 0;

    public function Threaded(array $args) : void
    {
        while (true)
        {
            $this->WaitForParentAccess();
        }
    }

    public function Set(int $code) : void
    {
        $this->exitCode = $code;
    }

    public function Get() : int
    {
        return $this->exitCode;
    }

    public function Exit() : void
    {
        exit;
    }
}