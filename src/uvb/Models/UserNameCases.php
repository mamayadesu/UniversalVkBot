<?php
declare(ticks = 1);

namespace uvb\Models;

use Data\Enum;

class UserNameCases extends Enum
{
    const NOM = "nom";
    const GEN = "gen";
    const DAT = "dat";
    const ACC = "acc";
    const INS = "ins";
    const ABL = "abl";
}