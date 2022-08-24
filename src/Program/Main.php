<?php
declare(ticks = 1);

namespace Program;

use IO\Console;
use \Throwable;
use uvb\System\CrashHandler;

/**
 * @ignore
 */

class Main
{
    public function __construct(array $args)
    {
        try
        {
            $main = new \uvb\Main($args);
        }
        catch (Throwable $e)
        {
            Console::WriteLine("\nFATAL ERROR: Unhandled " . get_class($e) . " \"" . $e->getMessage() . "\" in " . $e->getFile() . " on line " . $e->getLine());
            CrashHandler::Handle($e);
            Console::WriteLine("");
            exit(255);
        }
    }
}