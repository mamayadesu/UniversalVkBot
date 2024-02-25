<?php
declare(ticks = 1);

namespace uvb\System;

use Application\Application;
use \Throwable;
use uvb\APIVersions;
use uvb\Bot;
use uvb\Models\Command;
use uvb\Plugin\Plugin;

class CrashHandler
{
    /**
     * Сохраняет краш-дамп из любого исключения, наследующий интерфейс Throwable
     *
     * Появилось в API: 1.0
     *
     * @param Throwable $e Исключение
     * @param Plugin|null $plugin Объект плагина. Если это ошибка системы, должно быть null
     * @param Command|null $command Если это команда, здесь должен быть объект команды
     * @return string Название сохранённого файла
     */
    public static function Handle(Throwable $e, ?Plugin $plugin = null, ?Command $command = null) : string
    {
        $pluginName = "System";
        $pluginVersion = Application::GetVersion();
        $pluginApi = APIVersions::API_VERSION;
        $uvbApi = APIVersions::API_VERSION;
        $supportedApis = APIVersions::Get();
        $file = $e->getFile();
        $eval = false;
        $file1 = preg_replace("/\([0-9]+\) \: ([a-zA-Z'()\s]+)/", "$2", $file);
        if ($file1 != $file)
        {
            $eval = true;
            $file = $file1;
        }
        $line = $e->getLine();
        $message = $e->getMessage();
        $code = $e->getCode();
        $prevFile = "";
        $prevLine = 0;
        $prevEval = false;

        $commandName = "";
        $commandArguments = [];
        $commandSender = "Unknown";
        if ($command != null)
        {
            $commandName = $command->GetName();
            $commandArguments = $command->GetArguments();
            $commandSender1 = $command->GetUser();
            if ($commandSender1 != null)
            {
                $commandSender = $commandSender1->GetVkId() . "";
            }
        }

        $trace = $e->getTrace();

        $prevTrace = null;

        if (isset($trace[0]))
        {
            $prevTrace = $trace[0];
            $prevFile = $prevTrace["file"];
            $prevLine = $prevTrace["line"];

            $prevFile1 = preg_replace("/\([0-9]+\) \: ([a-zA-Z'()\s]+)/", "$2", $prevFile);
            if ($prevFile1 != $prevFile)
            {
                $prevEval = true;
                $prevFile = $prevFile1;
            }
        }

        if ($plugin != null)
        {
            $pluginName = $plugin->GetPluginName();
            $pluginVersion = $plugin->GetVersion();
            $pluginApi = $plugin->GetAPIVersion();
        }

        $text = "Unhandled " . get_class($e) . "\n";
        $text .= "Message error: " . $message . "\n";
        $text .= "Line: " . $line . "\n";
        $text .= "Plugin name: " . $pluginName . "\n";
        $text .= "Plugin version: " . $pluginVersion . "\n";
        $text .= "Time: " . date("d.m.Y H:i:s", time()) . "\n";

        if ($commandName != "")
        {
            $text .= "\nCommand: " . $commandName . "\n";
            $text .= "Command arguments: " . implode(' ', $commandArguments) . "\n";
            $text .= "Sender: " . $commandSender . "\n";
        }

        $text .= "\n\nTHERE IS SOME CODE WHERE EXCEPTION WAS THROWN\n";
        if ($eval)
        {
            $text .= "[*] EVAL CODE";
        }
        else
        {
            $content = file_get_contents($file);
            $content = str_replace("\r", "", $content);
            $lines = explode("\n", $content);

            for ($i = $line - 20; $i <= $line + 20; $i++)
            {
                if (!isset($lines[$i - 1]))
                {
                    continue;
                }
                if ($i == $line)
                {
                    $text .= "[" . $i . "*] ";
                }
                else
                {
                    $text .= "[" . $i . "] ";
                }
                $text .= $lines[$i - 1] . "\n";
            }
        }

        $text .= "\n\nTHERE IS SOME CODE OF THE PREVIOUS FILE\n";
        if ($prevEval)
        {
            $text .= "[*] EVAL CODE";
        }
        else
        {
            $prevContent = file_get_contents($prevFile);
            $prevContent = str_replace("\r", "", $prevContent);
            $lines = explode("\n", $prevContent);

            for ($i = $prevLine - 20; $i <= $prevLine + 20; $i++)
            {
                if (!isset($lines[$i - 1]))
                {
                    continue;
                }
                if ($i == $prevLine)
                {
                    $text .= "[" . $i . "*] ";
                }
                else
                {
                    $text .= "[" . $i . "] ";
                }
                $text .= $lines[$i - 1] . "\n";
            }
        }
        $text .= "\n\nTRACE:\n" . $e->getTraceAsString();
        $dir = Application::GetExecutableDirectory() . "crash-reports" . DIRECTORY_SEPARATOR;
        @mkdir($dir);

        $crashReportName = "crash-report-" . $pluginName . "-" . md5(microtime(true)) . ".log";
        $f = fopen($dir . $crashReportName, "w+");
        fwrite($f, $text);
        fclose($f);
        Bot::GetInstance()->GetLogger()->Log("Crash dump was saved as " . $dir . $crashReportName);
        return $crashReportName;
    }
}