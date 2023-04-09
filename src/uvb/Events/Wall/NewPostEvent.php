<?php

namespace uvb\Events\Wall;

use uvb\Events\Event;
use uvb\Models\Group;

class NewPostEvent extends Event
{
    public function __construct(Group $group)
    {
        $this->isCancellable = true;
        parent::__construct($group);
    }
}