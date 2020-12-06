<?php

namespace uvb\Rcon;

use IO\Console;

/**
 * @ignore
 */

class RconHandler
{
    private array $responses = array();

    public function __construct()
    {

    }

    public function SetResponse(string $name, string $text) : void
    {
        $text = str_replace("\r", "", $text);
        $lines = explode("\n", $text);
        if (!isset($this->responses[$name]))
        {
            $this->responses[$name] = array();
        }
        foreach ($lines as $line)
        {
            $this->responses[$name][] = $line;
        }
    }

    public function GetResponse(string $name) : string
    {
        if (!isset($this->responses[$name]))
        {
            return "";
        }
        $output = $this->RconOutput(implode("\n", $this->responses[$name]));
        unset($this->responses[$name]);
        return $output;
    }

    private function RconOutput(string $text) : string
    {
        $prefix = "[" . date("d.m.Y H:i:s", time()) . "] {RCON} ";
        $text = str_replace("\r", "", $text);
        $lines = explode("\n", $text);
        $output1 = [];
        foreach ($lines as $line)
        {
            $output1[] = $prefix . $line;
        }
        $output = implode("\n", $output1);
        return $output;
    }
}