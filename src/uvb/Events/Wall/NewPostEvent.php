<?php

namespace uvb\Events\Wall;

use uvb\Events\Event;

class NewPostEvent extends Event
{
    public function __construct()
    {
        $this->isCancellable = true;
    }
}