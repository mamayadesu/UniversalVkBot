<?php
declare(ticks = 1);

namespace uvb\System\Update;

use Application\Application;
use Data\String\BackgroundColors;
use Data\String\ColoredString;
use Data\String\ForegroundColors;
use IO\FileDirectory;
use uvb\APIVersions;
use uvb\cmm;
use uvb\Logger;
use uvb\Main;
use uvb\Plugin\Plugin;
use uvb\SystemLogger;

/**
 * @ignore
 */

final class Updater
{
    private Main $main;
    private string $checkUrl = "https://raw.githubusercontent.com/mamayadesu/UniversalVkBot/main/update/update.json";
    private bool $updateFound = false, $readyToPrepare = false, $readyToInstall = false, $updateWasFinished = false;
    private SystemLogger $sl;
    private Logger $logger;

    private string $newVersion, $phpVersion, $downloadUrl, $sourceUpdateFileData = "", $currentPackage = "";
    private array/*<string>*/ $supportedApi;
    private array/*<string, string>*/ $versions_history;

    public function __construct(Main $main, Logger $logger, SystemLogger $sl)
    {
        $this->main = $main;
        $this->logger = $logger;
        $this->sl = $sl;

        $updateDir = Application::GetExecutableDirectory() . "update" . DIRECTORY_SEPARATOR;
        $currentPackageFile = $updateDir . "current_package";
        $sourceUpdateFile = $updateDir . "update.json";
        if (!(is_dir($updateDir) && file_exists($currentPackageFile) && file_exists($sourceUpdateFile)))
        {
            return;
        }

        $this->sourceUpdateFileData = file_get_contents($sourceUpdateFile);
        $data = json_decode($this->sourceUpdateFileData, true);
        if ($data == null)
        {
            return;
        }
        $this->newVersion = $data["version"];
        $this->versions_history = $data["versions_history"];
        $this->supportedApi = $data["api_versions"];
        $this->phpVersion = $data["php_version"];
        $this->currentPackage = file_get_contents($currentPackageFile);

        $this->Log("Installing update " . $this->currentPackage . "...");
        forward_static_call(array("uvb\\System\\Update\\Packages\\" . $this->currentPackage, "InstallUpdate"), $this->main);

        $nextPackage = $this->FindNextPackage();
        if ($nextPackage == "")
        {
            $this->updateWasFinished = true;
            FileDirectory::Delete($updateDir);
            return;
        }
        $this->currentPackage = $nextPackage;
        $f = fopen($currentPackageFile, "w");
        fwrite($f, $nextPackage);
        fclose($f);

        $this->Log("Preparing to install package " . $this->currentPackage . "...");
        forward_static_call(array("uvb\\System\\Update\\Packages\\" . $this->currentPackage, "PreUpdateStart"), $this->main);
        exit(2);
    }

    public function UpdateWasFinished() : bool
    {
        return $this->updateWasFinished;
    }

    public function GetCurrentPackage() : string
    {
        return $this->currentPackage;
    }

    public function SetLogger(Logger $logger) : void
    {
        $this->logger = $logger;
    }

    private function dt() : string
    {
        return "[" . date("d.m.Y H:i:s", time()) . "] ";
    }


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

    public function Log(string $text) : void
    {
        $dt = $this->dt();
        $head = ColoredString::Get($dt, ForegroundColors::CYAN, BackgroundColors::BLACK);
        $pr = "[UPDATE]";
        $head .= ColoredString::Get($pr, ForegroundColors::PURPLE, BackgroundColors::BLACK);
        $not_colored_head = $dt . " " . $pr;
        $text = str_replace("\r", "", $text);
        $lines = explode("\n", $text);
        $output = "";
        $_output = "";
        foreach ($lines as $line)
        {
            $output .= $head . ColoredString::Get(" " . $line, ForegroundColors::WHITE, BackgroundColors::BLACK) . "\n";
            $_output .= $not_colored_head . " " . $line . "\n";
        }
        if (!$this->sl->IsColorsEnabled())
        {
            $output = $_output;
        }
        $this->sl->Log($output, $_output);
    }

    private function FindNextPackage() : string
    {
        $foundMyVersion = false;
        foreach ($this->versions_history as $version => $package)
        {
            if ($foundMyVersion)
            {
                return $package;
            }
            if ($package == $this->currentPackage)
            {
                $foundMyVersion = true;
            }
        }
        return "";
    }

    public function UpdateCommand() : bool
    {
        if (!$this->updateFound)
        {
            $this->CheckForUpdates();
            return false;
        }
        else
        {
            if (!$this->readyToPrepare)
            {
                $pluginsWontWork = [];

                $plugins = $this->main->pluginManager->GetPlugins();
                foreach ($plugins as $plugin)
                {if(!$plugin instanceof Plugin)continue;
                    if (!in_array($plugin->GetAPIVersion(), $this->supportedApi))
                    {
                        $pluginsWontWork[] = $plugin->GetPluginName();
                    }
                }

                $supportedApiOutput = "";
                foreach ($this->supportedApi as $supportedApi)
                {
                    $supportedApiOutput .= "  " . $supportedApi . "\n";
                }

                cmm::l("system.update.confirm", [$this->newVersion, $supportedApiOutput, APIVersions::API_VERSION, $this->phpVersion]);
                if (count($pluginsWontWork) > 0)
                {
                    cmm::w("system.update.pluginswarning", [implode(", ", $pluginsWontWork)]);
                }
                $this->readyToPrepare = true;
                return false;
            }
            else
            {
                cmm::l("system.update.downloading", []);
                $updateDir = Application::GetExecutableDirectory() . "update";
                if (file_exists($updateDir) && !is_dir($updateDir))
                {
                    FileDirectory::Delete($updateDir);
                }
                $updateDir .= DIRECTORY_SEPARATOR;
                if (!@mkdir($updateDir))
                {
                    //$this->logger->Error("Failed to create update directory");
                    //return false;
                }
                $f = fopen($updateDir . "update.json", "w");
                if (!$f)
                {
                    //$this->logger->Error("Failed to create update file");
                    //return false;
                }
                if (fwrite($f, $this->sourceUpdateFileData) === false)
                {
                    //$this->logger->Error("Failed to write data to update file");
                    //fclose($f);
                    //return false;
                }
                fclose($f);
                $f = fopen($updateDir . "current_package", "w");
                if (!$f)
                {
                    //$this->logger->Error("Failed to create current_package file");
                    //return false;
                }

                $this->currentPackage = $this->versions_history[Application::GetVersion()];
                $nextPackage = $this->FindNextPackage();
                $this->currentPackage = $nextPackage;

                if (fwrite($f, $this->currentPackage) === false)
                {
                    $this->logger->Error("Failed to write data to current_package file");
                    fclose($f);
                    FileDirectory::Delete($updateDir);
                    return false;
                }
                fclose($f);

                $fp = fopen($updateDir . "update.phar", "w+");
                if (!$fp)
                {
                    //$this->logger->Error("Failed to create update package");
                    //return false;
                }

                $ch = curl_init($this->downloadUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 120);
                $fileData = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
                if ($http_code != 200)
                {
                    $this->logger->Error("Failed to download update package");
                    fclose($fp);
                    FileDirectory::Delete($updateDir);
                    return false;
                }
                if (fwrite($fp, $fileData) === false)
                {
                    //$this->logger->Error("Failed to save update package");
                    //fclose($fp);
                    //return false;
                }
                fclose($fp);
                $this->readyToInstall = true;

                $this->main->bot->InstallUpdate();
                return true;
            }
        }
    }

    public function IsReadyToPrepare() : bool
    {
        return $this->readyToPrepare;
    }

    public function IsReadyToInstall() : bool
    {
        return $this->readyToInstall;
    }

    public function CheckForUpdates() : void
    {
        cmm::l("system.update.checking", []);
        $this->updateFound = false;
        $this->readyToInstall = false;
        $this->readyToPrepare = false;
        $ch = curl_init($this->checkUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        if ($http_code != 200)
        {
            cmm::e("system.update.fail.server", []);
            return;
        }
        $this->sourceUpdateFileData = $result;
        $data = json_decode($result, true);
        if ($data == null)
        {
            cmm::e("system.update.fail.data", []);
            var_dump($result);
            return;
        }

        $currentVersion = Application::GetVersion();
        if ($data["version"] == $currentVersion)
        {
            cmm::l("system.update.noupdates", []);
            return;
        }
        $this->newVersion = $data["version"];
        $this->versions_history = $data["versions_history"];
        $this->supportedApi = $data["api_versions"];
        $this->phpVersion = $data["php_version"];
        $this->downloadUrl = $data["url"];

        cmm::l("system.update.title", [$this->newVersion]);
        cmm::l("system.update.text", []);
        $this->updateFound = true;
    }
}