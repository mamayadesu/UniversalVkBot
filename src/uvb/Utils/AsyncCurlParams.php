<?php

namespace uvb\Utils;

use Scheduler\IAsyncTaskParameters;

class AsyncCurlParams implements IAsyncTaskParameters
{
    public ?int $mrc, $active;
    public $mh, $ch;

    public int $_current_stage;
}