<?php
declare(ticks = 1);

namespace uvb\Models\Buttons\Colors;

use Data\Enum;

class Color extends Enum
{
    const PRIMARY = "primary";
    const SECONDARY = "secondary";
    const NEGATIVE = "negative";
    const POSITIVE = "positive";
}