<?php
declare(ticks = 1);

namespace uvb\System\Update\Packages;

use uvb\Main;

/**
 * @ignore
 */

interface IPackage
{
    public static function PreUpdateStart(Main $main) : void;
    public static function InstallUpdate(Main $main) : void;
}