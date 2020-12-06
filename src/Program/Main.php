<?php

namespace Program;

use IO\Console;

/**
 * @ignore
 */

class Main
{
    public function __construct(array $args)
    {
        $swooleLoaded = true;
        if (!class_exists("\\Swoole\\Http\\Server") || !class_exists("\\Swoole\\Http\\Request") || !class_exists("\\Swoole\\Http\\Response"))
        {
            $swooleLoaded = false;
            //Console::WriteLine("Swoole extension is not loaded. Loading Swoole emulator...");
            including(__DIR__ . DIRECTORY_SEPARATOR . "../Swoole");
        }
        new \uvb\Main($args, $swooleLoaded);
    }
}