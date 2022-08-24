<?php
declare(ticks = 1);

namespace uvb\Models;

use Data\Enum;

class BanReason extends Enum
{
    const Other = 0;
    const Spam = 1;
    const MembersInsulting = 2;
    const ObsceneExpressions = 3;
    const NonTopicContent = 4;
}