<?php

namespace uvb;

use Application\Application;

final class ConsoleMessagesManager
{
    private array $langs;
    private string $currentLang = "";
    public static array $langs1 = array();
    public static string $currentLang1 = "";
    private Main $main;

    public function __construct(Main $main)
    {
        $this->main = $main;
        $pathToLanguages = Application::GetExecutableDirectory() . "languages" . DIRECTORY_SEPARATOR;
        if (!is_dir($pathToLanguages))
        {
            @mkdir($pathToLanguages);
        }
        $pathToResources = Application::GetExecutableFileName();
        if (basename($pathToResources) == "autoload.php")
        {
            $pathToResources = dirname($pathToResources) . DIRECTORY_SEPARATOR . "languages";
        }
        else
        {
            $pathToResources = str_replace("\\", "/", $pathToResources);
            $pathToResources .= "/languages/";
            $pathToResources = "phar://" . $pathToResources;
        }
        $files = ["en.txt", "ru.txt"];
        $f = null;
        foreach ($files as $file)
        {
            if (!file_exists($pathToLanguages . $file))
            {
                $f = fopen($pathToLanguages . $file, "w");
                fwrite($f, file_get_contents($pathToResources . $file));
                fclose($f);
            }
        }
        cmm::$consoleMessagesManager = $this;
        $last_lang = "en";
        $path = Application::GetExecutableDirectory() . "last_language.txt";
        if (file_exists($path))
        {
            $last_lang = str_replace(["\r", "\n"], ["", ""], file_get_contents($path));
        }
        else
        {
            $f = fopen($path, "w");
            fwrite($f, $last_lang);
            fclose($f);
        }
        $this->LoadLanguages();
        $this->SetLanguage("en");
        $this->SetLanguage($last_lang);
    }

    public function SetLanguage(string $langId) : bool
    {
        if (!isset($this->langs[$langId]))
        {
            Bot::GetInstance()->GetLogger()->Log("Language-pack " . $langId . " is not loaded");
            return false;
        }
        $path = Application::GetExecutableDirectory() . "last_language.txt";
        $this->currentLang = $langId;
        $f = fopen($path, "w");
        fwrite($f, $langId);
        fclose($f);
        return true;
    }

    public function GetMessage(string $msgId, array $params) : string
    {
        if ($this->currentLang == "" || !isset($this->langs[$this->currentLang][$msgId]))
        {
            return $msgId;
        }
        $message = $this->langs[$this->currentLang][$msgId];
        $c = -1;
        foreach ($params as $param)
        {
            $c++;
            $message = str_replace("%s" . $c, $param, $message);
        }
        return $message;
    }

    public function LoadLanguages() : void
    {
        $path = Application::GetExecutableDirectory() . "languages" . DIRECTORY_SEPARATOR;
        $fullpath = "";
        $contents = "";
        $data = [];
        $row = [];
        $langId = "";
        $msgId = "";
        $msgText = "";
        \hat();
        foreach (glob($path . "*.txt") as $fullpath)
        {
            if (is_dir($fullpath))
            {
                continue;
            }
            $filename = basename($fullpath);
            $contents = str_replace("\r", "", file_get_contents($fullpath));
            $data = explode("\n", $contents);
            $langId = substr($filename, 0, strlen($filename) - 4);
            Bot::GetInstance()->GetLogger()->Log("Loading language package " . $langId);
            $this->langs[$langId] = array();
            foreach ($data as $row1)
            {
                $row = explode('=', $row1);
                $msgId = $row[0];
                array_shift($row);
                $msgText = implode('=', $row);

                $this->langs[$langId][$msgId] = str_replace("\\n", "\n", $msgText);
            }
            \hat();
        }
    }
}