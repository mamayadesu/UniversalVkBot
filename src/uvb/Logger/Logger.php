<?php

namespace uvb;

use IO\Console;

/**
 * Логгеры используют само ядро бота и плагины
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
     * Получить цветную строку
     *
     * @param string @str Входящая строка
     * @param ForegroundColors $foreColor Цвет текста
     * @param BackgroundColors $backColor Цвет фона
     *
     * @return string Цветная строка
     */
    public function GetColoredString(string $str, string $foreColor, string $backColor) : string
    {
        if (!$this->sl->IsColorsEnabled())
        {
            return $str;
        }

        $coloredStr = "\033[" . $foreColor . "m";
        $coloredStr .= "\033[" . $backColor . "m";
        $coloredStr .= $str . "\033[1;37m";
        return $coloredStr;
    }

    /**
     * Записать в логи на уровне обычных логов
     *
     * @param string $text Текст для записи
     */
    public function Log(string $text) : void
    {
        $dt = $this->dt();
        $head = $this->GetColoredString($dt, ForegroundColors::CYAN, BackgroundColors::BLACK);
        $pr = "[LOG]";
        if ($this->prefix != "")
        {
            $pr .= " [" . $this->prefix . "]";
        }
        if ($this->prefix == "CONSOLE")
        {
            $pr = " {CONSOLE}";
        }
        $head .= $this->GetColoredString($pr, ForegroundColors::WHITE, BackgroundColors::BLACK);
        $text = str_replace("\r", "", $text);
        $lines = explode("\n", $text);
        $output = "";
        $_output = "";
        foreach ($lines as $line)
        {
            $output .= $head . $this->GetColoredString(" " . $line, ForegroundColors::WHITE, BackgroundColors::BLACK) . "\n";
            $_output .= $dt . $pr . " " . $line . "\n";
        }
        $output .= ($this->sl->IsColorsEnabled() ? "\033[0m" : "");
        $_output = preg_replace("/\\033\[([0-9]+)\;([0-9]+)\m/", "", $_output);
        $_output = str_replace("\033[0m", "", $_output);
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
        $head = $this->GetColoredString($dt, ForegroundColors::CYAN, BackgroundColors::BLACK);
        $pr = "[WARNING]";
        if ($this->prefix != "")
        {
            $pr .= " [" . $this->prefix . "]";
        }
        $head .= $this->GetColoredString($pr, ForegroundColors::YELLOW, BackgroundColors::BLACK);
        $text = str_replace("\r", "", $text);
        $lines = explode("\n", $text);
        $output = "";
        $_output = "";
        foreach ($lines as $line)
        {
            $output .= $head . $this->GetColoredString(" " . $line, ForegroundColors::YELLOW, BackgroundColors::BLACK) . "\n";
            $_output .= $dt . $pr . " " . $line . "\n";
        }
        $output .= ($this->sl->IsColorsEnabled() ? "\033[0m" : "");
        $_output = preg_replace("/\\033\[([0-9]+)\;([0-9]+)\m/", "", $_output);
        $_output = str_replace("\033[0m", "", $_output);
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
        $head = $this->GetColoredString($dt, ForegroundColors::CYAN, BackgroundColors::BLACK);
        $pr = "[ERROR]";
        if ($this->prefix != "")
        {
            $pr .= " [" . $this->prefix . "]";
        }
        $head .= $this->GetColoredString($pr, ForegroundColors::DARK_RED, BackgroundColors::BLACK);
        $text = str_replace("\r", "", $text);
        $lines = explode("\n", $text);
        $output = "";
        $_output = "";
        foreach ($lines as $line)
        {
            $output .= $head . $this->GetColoredString(" " . $line, ForegroundColors::DARK_RED, BackgroundColors::BLACK) . "\n";
            $_output .= $dt . $pr . " " . $line . "\n";
        }
        $output .= ($this->sl->IsColorsEnabled() ? "\033[0m" : "");
        $_output = preg_replace("/\\033\[([0-9]+)\;([0-9]+)\m/", "", $_output);
        $_output = str_replace("\033[0m", "", $_output);
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
        $head = $this->GetColoredString($dt, ForegroundColors::CYAN, BackgroundColors::BLACK);
        $pr = "[CRITICAL]";
        if ($this->prefix != "")
        {
            $pr .= " [" . $this->prefix . "]";
        }
        $head .= $this->GetColoredString($pr, ForegroundColors::BLACK, BackgroundColors::RED);
        $text = str_replace("\r", "", $text);
        $lines = explode("\n", $text);
        $output = "";
        $_output = "";
        foreach ($lines as $line)
        {
            $output .= $head . $this->GetColoredString(" " . $line, ForegroundColors::BLACK, BackgroundColors::RED) . "\n";
            $_output .= $dt . $pr . " " . $line . "\n";
        }
        $output .= ($this->sl->IsColorsEnabled() ? "\033[0m" : "");
        $_output = preg_replace("/\\033\[([0-9]+)\;([0-9]+)\m/", "", $_output);
        $_output = str_replace("\033[0m", "", $_output);
        $this->sl->Log($output, $_output);
    }
}