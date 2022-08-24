<?php
declare(ticks = 1);

namespace uvb\Models;

use Data\Enum;

class UserSex extends Enum
{
    const UNKNOWN = 0;
    const FEMALE = 1;
    const MALE = 2;
}