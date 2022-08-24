<?php
declare(ticks = 1);

namespace uvb;

use Data\String\BackgroundColors;
use Data\String\ColoredString;
use Data\String\ForegroundColors;

/**
 * Предназначен для записи логов в файл и консоль. Используется как ядром, так и плагинами
 * @package uvb
 */

class Logger
{
    /**
     * @ignore
     */
    private string $prefix;

    /**
     * @ignore
     */
    private SystemLogger $sl;

    /**
     * @ignore
     */
    public function __construct(string $prefix, SystemLogger $sl)
    {
        $this->prefix = $prefix;
        $this->sl = $sl;
    }

    /**
     * @ignore
     */
    private function dt() : string
    {
        return "[" . date("d.m.Y H:i:s", time()) . "] ";
    }

    /**
     * Записать в логи на уровне обычных логов
     *
     * @param string $text Текст для записи
     */
    public function Log(string $text) : void
    {
        $dt = $this->dt();
        $head = ColoredString::Get($dt, ForegroundColors::CYAN);
        $pr = "[LOG]";
        if ($this->prefix != "")
        {
            $pr .= " [" . $this->prefix . "]";
        }
        if ($this->prefix == "CONSOLE")
        {
            $pr = " {CONSOLE}";
        }
        $head .= ColoredString::Get($pr, ForegroundColors::WHITE);
        $not_colored_head = $dt . " " . $pr;
        $text = str_replace("\r", "", $text);
        $lines = explode("\n", $text);
        $output = "";
        $_output = "";
        foreach ($lines as $line)
        {
            $output .= $head . ColoredString::Get(" " . $line, ForegroundColors::WHITE) . "\n";
            $_output .= $not_colored_head . " " . $line . "\n";
        }
        if (!$this->sl->IsColorsEnabled())
        {
            $output = $_output;
        }
        $this->sl->Log($output, $_output);
    }

    /**
     * Записать в логи на уровне предупреждения
     *
     * @param string $text Текст для записи
     */
    public function Warn(string $text) : void
    {
        $dt = $this->dt();
        $head = ColoredString::Get($dt, ForegroundColors::CYAN);
        $pr = "[WARNING]";
        if ($this->prefix != "")
        {
            $pr .= " [" . $this->prefix . "]";
        }
        if ($this->prefix == "CONSOLE")
        {
            $pr = " {CONSOLE}";
        }
        $head .= ColoredString::Get($pr, ForegroundColors::YELLOW);
        $not_colored_head = $dt . " " . $pr;
        $text = str_replace("\r", "", $text);
        $lines = explode("\n", $text);
        $output = "";
        $_output = "";
        foreach ($lines as $line)
        {
            $output .= $head . ColoredString::Get(" " . $line, ForegroundColors::YELLOW) . "\n";
            $_output .= $not_colored_head . " " . $line . "\n";
        }
        if (!$this->sl->IsColorsEnabled())
        {
            $output = $_output;
        }
        $this->sl->Log($output, $_output);
    }

    /**
     * Записать в логи на уровне ошибки
     *
     * @param string $text Текст для записи
     */
    public function Error(string $text) : void
    {
        $dt = $this->dt();
        $head = ColoredString::Get($dt, ForegroundColors::CYAN);
        $pr = "[ERROR]";
        if ($this->prefix != "")
        {
            $pr .= " [" . $this->prefix . "]";
        }
        if ($this->prefix == "CONSOLE")
        {
            $pr = " {CONSOLE}";
        }
        $head .= ColoredString::Get($pr, ForegroundColors::RED);
        $not_colored_head = $dt . " " . $pr;
        $text = str_replace("\r", "", $text);
        $lines = explode("\n", $text);
        $output = "";
        $_output = "";
        foreach ($lines as $line)
        {
            $output .= $head . ColoredString::Get(" " . $line, ForegroundColors::RED) . "\n";
            $_output .= $not_colored_head . " " . $line . "\n";
        }
        if (!$this->sl->IsColorsEnabled())
        {
            $output = $_output;
        }
        $this->sl->Log($output, $_output);
    }

    /**
     * Записать в логи на уровне критической ошибки
     *
     * @param string $text Текст для записи
     */
    public function Critical(string $text) : void
    {
        $dt = $this->dt();
        $head = ColoredString::Get($dt, ForegroundColors::CYAN);
        $pr = "[CRITICAL]";
        if ($this->prefix != "")
        {
            $pr .= " [" . $this->prefix . "]";
        }
        if ($this->prefix == "CONSOLE")
        {
            $pr = " {CONSOLE}";
        }
        $head .= ColoredString::Get($pr, ForegroundColors::WHITE, BackgroundColors::RED);
        $not_colored_head = $dt . " " . $pr;
        $text = str_replace("\r", "", $text);
        $lines = explode("\n", $text);
        $output = "";
        $_output = "";
        foreach ($lines as $line)
        {
            $output .= $head . ColoredString::Get(" " . $line, ForegroundColors::WHITE, BackgroundColors::RED) . "\n";
            $_output .= $not_colored_head . " " . $line . "\n";
        }
        if (!$this->sl->IsColorsEnabled())
        {
            $output = $_output;
        }
        $this->sl->Log($output, $_output);
    }
}