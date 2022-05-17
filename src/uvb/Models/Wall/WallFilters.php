<?php

namespace uvb\Models\Wall;

use Data\Enum;

class WallFilters extends Enum
{
    const SUGGEST = "suggest";
    const POSTPONED = "postponed";
    const OWNER = "owner";
    const OTHERS = "others";
    const ALL = "all";
}