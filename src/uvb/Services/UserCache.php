<?php
declare(ticks = 1);

namespace uvb\Services;

use Application\Application;
use IO\FileDirectory;
use uvb\cmm;
use uvb\Main;
use uvb\Models\User;
use \Exception;

/**
 * @ignore
 */

final class UserCache
{
    private array $cached = array(), $cachedTime = array(), $cacheUpdated = array();
    private static ?UserCache $instance = null;
    private Main $main;
    const USER_CACHE_TIME = 432000; // срок хранения кэша пользователей 5 дней

    public function __construct(Main $main)
    {
        $this->main = $main;
        if (self::$instance != null)
        {
            throw new Exception("UserCache service is already started!");
        }
        self::$instance = $this;
    }

    public static function GetInstance() : ?UserCache
    {
        return self::$instance;
    }

    private function CheckDir() : string
    {
        $path = Application::GetExecutableDirectory() . "users";
        if (!is_dir($path) && file_exists($path))
        {
            FileDirectory::Delete($path);
        }
        $path .= DIRECTORY_SEPARATOR;
        if (!is_dir($path))
        {
            mkdir($path);
        }
        return $path;
    }

    public function Load(bool $outputProgress = false) : void
    {
        
        $this->cached = array();
        $this->cachedTime = array();
        $this->cacheUpdated = array();
        $path = $this->CheckDir();
        $content = "";
        $data = array();
        $user = null;
        $basename = "";
        $vkIdStr = "";
        $vkId = 0;

        $count = count(glob($path . "*.json"));
        $done = 0;

        
        $currentPercentWas = 0;
        $currentPercent = 0;
        if ($outputProgress)
        {
            cmm::l("bot.loadingusers.progress", [$currentPercent]);
        }
        $updater = 0;
        foreach (glob($path . "*.json") as $filename)
        {
            $updater++;
            $basename = basename($filename);
            $vkIdStr = substr($basename, 0, strlen($basename) - 5);
            $vkIdStr = str_replace([",", "."], ["", ""], $vkIdStr);
            $content = file_get_contents($filename);
            $data = json_decode($content, true);
            $vkId = intval($vkIdStr);
            if ($data == null || $vkId == 0 || !isset($data["updated"]) || !is_integer($data["updated"]) ||
                !isset($data["firstname"]) || !is_array($data["firstname"]) || !isset($data["firstname"]["nom"]) || !is_string($data["firstname"]["nom"]) ||
                !isset($data["lastname"]) || !is_array($data["lastname"]) || !isset($data["lastname"]["nom"]) || !is_string($data["lastname"]["nom"]) ||
                !isset($data["sex"]) || !is_integer($data["sex"]) ||
                !isset($data["birthday"]) || !is_string($data["birthday"]) ||
                !isset($data["city"]) || !is_string($data["city"]) ||
                !isset($data["country"]) || !is_string($data["country"]) ||
                !isset($data["domain"]) || !is_string($data["domain"]) ||
                !isset($data["status"]) || !is_string($data["status"])
            )
            {
                
                FileDirectory::Delete($filename);
                continue;
            }
            $user = new User($vkId, $data["firstname"], $data["lastname"], $data["sex"], $data["birthday"], $data["city"], $data["country"], $data["domain"], $data["status"]);
            $this->cached[$vkId] = $user;
            $this->cachedTime[$vkId] = $data["updated"];
            $this->cacheUpdated[$vkId] = false;
            if ($updater == 100)
            {
                $updater = 0;
                
            }
            $done++;
            $currentPercent = floor($done / $count * 100);
            if ($currentPercent != $currentPercentWas)
            {
                $currentPercentWas = $currentPercent;
                if ($outputProgress)
                {
                    cmm::l("bot.loadingusers.progress", [$currentPercent]);
                }
            }
        }
        
    }

    /**
     * ToDo Сделать команду, выполняющая этот метод
     */
    public function Clear() : void
    {
        
        $outputProgress = true;
        $path = $this->CheckDir();
        $data = array();
        $f = null;
        $count = 0;
        $done = 0;
        foreach ($this->cached as $vkId => $user)
        {if (!$user instanceof User)continue;
            if (!$this->cacheUpdated[$vkId])
            {
                continue;
            }
            $count++;
        }
        $currentPercentWas = 0;
        $currentPercent = 0;
        if ($outputProgress)
        {
            $this->main->bot->GetLogger()->Log($currentPercent);
        }
        
        foreach ($this->cached as $vkId => $user)
        {if (!$user instanceof User)continue;
            if (!$this->cacheUpdated[$vkId])
            {
                continue;
            }
            unset($user);
            unset($this->cached[$vkId]);

            unset($this->cachedTime[$vkId]);
            unset($this->cacheUpdated[$vkId]);
            @unlink($path . $vkId . ".json");
            $done++;
            $currentPercent = floor($done / $count * 100);
            if ($currentPercent != $currentPercentWas)
            {
                $currentPercentWas = $currentPercent;
                if ($outputProgress)
                {
                    $this->main->bot->GetLogger()->Log($currentPercent);
                }
            }
            
        }
    }

    public function Save(bool $outputProgress = false, bool $freeRam = false) : void
    {
        
        $path = $this->CheckDir();
        $data = array();
        $f = null;
        $count = 0;
        $done = 0;
        foreach ($this->cached as $vkId => $user)
        {
            if (!$user instanceof User)
            {
                continue;
            }
            if (!$this->cacheUpdated[$vkId])
            {
                continue;
            }
            $count++;
        }
        $currentPercentWas = 0;
        $currentPercent = 0;
        if ($outputProgress)
        {
            cmm::l("bot.savingusers.progress", [$currentPercent]);
        }
        
        foreach ($this->cached as $vkId => $user)
        {if(!$user instanceof User)continue;
            if (!$this->cacheUpdated[$vkId])
            {
                continue;
            }
            $data = array
            (
                "firstname" => $user->GetFirstNameAsArray(),
                "lastname" => $user->GetLastNameAsArray(),
                "sex" => $user->GetSex(),
                "updated" => $this->cachedTime[$vkId],
                "birthday" => $user->GetBirthday(),
                "city" => $user->GetCity(),
                "country" => $user->GetCountry(),
                "domain" => $user->GetDomain(),
                "status" => $user->GetStatus()
            );
            $f = fopen($path . $vkId . ".json", "w");
            fwrite($f, json_encode($data, JSON_PRETTY_PRINT));
            fclose($f);

            if ($freeRam)
            {
                unset($this->cached[$vkId], $this->cachedTime[$vkId], $this->cacheUpdated[$vkId], $user);
            }

            $done++;
            $currentPercent = floor($done / $count * 100);
            if ($currentPercent != $currentPercentWas)
            {
                $currentPercentWas = $currentPercent;
                if ($outputProgress)
                {
                    cmm::l("bot.savingusers.progress", [$currentPercent]);
                }
            }
            
        }
    }

    public function Get(int $vkId) : ?User
    {
        if ($vkId == 0)
            return Main::GetConsoleAsUser();

        if (!isset($this->cached[$vkId]))
            return null;

        return $this->cached[$vkId];
    }

    public function NeedToUpdate(int $vkId) : bool
    {
        if ($vkId == 0)
            return false;

        return ((time() - $this->cachedTime[$vkId]) >= self::USER_CACHE_TIME);
    }

    public function GetUsers() : array
    {
        $result = array();
        foreach ($this->cached as $vkId => $user)
        {
            $result[$vkId] = $user;
        }
        return $result;
    }

    public function HasUser(int $vkId) : bool
    {
        if (!isset($this->cached[$vkId]) && $vkId != 0)
        {
            return false;
        }
        return true;
    }

    public function Add(User $user) : void
    {
        if (!$user->IsHuman())
        {
            return;
        }
        if (!isset($this->cached[$user->GetVkId()]))
        {
            $this->cached[$user->GetVkId()] = $user;
        }
        $this->cachedTime[$user->GetVkId()] = time();
        $this->cacheUpdated[$user->GetVkId()] = true;
        
    }
}