<?php
declare(ticks = 1);

namespace uvb;

use Application\Application;
use \IO\Console;
use IO\FileDirectory;

/**
 * @ingore
 */
final class SystemLogger
{
    private $f = null;
    private string $pathToLogs;
    private bool $colorsEnabled, $paused = false;
    private static bool $initialized = false;
    private array $history = array();

    public function __construct(bool $colorsEnabled)
    {
        if (self::$initialized)
        {
            throw new \Exception("System Logger is already initialized");
        }
        self::$initialized = true;
        $this->colorsEnabled = $colorsEnabled;
        $this->pathToLogs = Application::GetExecutableDirectory() . "logs" . DIRECTORY_SEPARATOR;
        @mkdir($this->pathToLogs);
        $this->f = fopen($this->pathToLogs . "latest.log", "w");
    }

    public function IsColorsEnabled() : bool
    {
        return $this->colorsEnabled;
    }

    public function Log(string $console, string $file) : void
    {
        Console::Write($console);
        fwrite($this->f, $file);
    }

    public function CloseLogger() : void
    {
        fclose($this->f);
        @mkdir($this->pathToLogs);
        if (file_exists($this->pathToLogs . "latest.log"))
        {
            $filename = "";
            $i = 0;
            $time = time();
            while (file_exists($filename) || $filename == "")
            {
                $i++;
                $filename = $this->pathToLogs . date("d-m-Y", $time) . "-" . $i . ".log";
            }
            FileDirectory::Copy($this->pathToLogs . "latest.log", $filename);
        }
    }
}