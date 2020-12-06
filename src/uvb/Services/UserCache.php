<?php

namespace uvb\Services;

use Application\Application;
use IO\FileDirectory;
use uvb\cmm;
use uvb\Main;
use uvb\Models\User;
use uvb\Models\UserSex;
use uvb\Repositories\UserRepository;
use \Exception;

/**
 * @ignore
 */

class UserCache
{
    private array $cached = array();
    private array $cachedTime = array();
    private array $cacheUpdated = array();
    private static ?UserCache $instance = null;
    private Main $main;
    const USER_CACHE_TIME = 86400;

    public function __construct(Main $main)
    {
        $this->main = $main;
        if (self::$instance != null)
        {
            throw new Exception("UserCache service already started!");
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
        $this->main->UpdateTitle();
        $path = $this->CheckDir();
        $content = "";
        $data = array();
        $user = null;
        $basename = "";
        $vkIdStr = "";
        $vkId = 0;

        $count = count(glob($path . "*.json"));
        $done = 0;

        $this->main->UpdateTitle();
        $currentPercentWas = 0;
        $currentPercent = 0;
        if ($outputProgress)
        {
            cmm::l("bot.loadingusers.progress", [$currentPercent]);
        }
        $updater = 0;
        foreach (glob($path . "*.json") as $fullname)
        {
            $updater++;
            $basename = basename($fullname);
            $vkIdStr = substr($basename, 0, strlen($basename) - 5);
            $vkIdStr = str_replace([",", "."], ["", ""], $vkIdStr);
            $content = file_get_contents($fullname);
            $data = json_decode($content, true);
            $vkId = intval($vkIdStr);
            if ($data == null || $vkId == 0 || !isset($data["firstname"]) || !isset($data["lastname"]) || !isset($data["updated"]) || !(is_string($data["firstname"]) || is_array($data["firstname"]) || is_string($data["lastname"]) || is_array($data["lastname"])))
            {
                FileDirectory::Delete($fullname); $this->main->UpdateTitle();
                continue;
            }
            $sex = UserSex::MALE;
            $constructor = 0;
            if (isset($data["sex"]) && ($data["sex"] == UserSex::MALE || $data["sex"] == UserSex::FEMALE))
            {
                $sex = $data["sex"];
            }
            else if (isset($data["sex"]))
            {
                FileDirectory::Delete($fullname);
                continue;
            }
            if (is_array($data["firstname"]) && is_array($data["lastname"]) && isset($data["sex"]))
            {
                $constructor = 1;
            }
            if (is_string($data["firstname"]) && is_string($data["lastname"]) && !isset($data["sex"]))
            {
                $constructor = 2;
            }
            if ($constructor == 0)
            {
                FileDirectory::Delete($fullname);
                continue;
            }
            $user = null;
            $data["updated"] = intval($data["updated"]);

            switch ($constructor)
            {
                default:
                    $user = new User($vkId, $data["firstname"], $data["lastname"], $data["sex"]);
                    break;

                case 2:
                    $user = new User($vkId, $data["firstname"], $data["lastname"]);
            }
            $this->cached[$vkId] = $user;
            $this->cachedTime[$vkId] = $data["updated"];
            $this->cacheUpdated[$vkId] = false;
            if ($updater == 100)
            {
                $updater = 0;
                $this->main->UpdateTitle();
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
        $this->main->UpdateTitle();
    }

    public function Save(bool $outputProgress = false) : void
    {
        $this->main->UpdateTitle();
        $path = $this->CheckDir();
        $data = array();
        $f = null;
        $count = 0;
        $done = 0;
        $this->main->UpdateTitle();
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
        $this->main->UpdateTitle();
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
            $data = array
            (
                "firstname" => $user->GetFirstNameAsArray(),
                "lastname" => $user->GetLastNameAsArray(),
                "sex" => $user->GetSex(),
                "updated" => $this->cachedTime[$vkId]
            );
            $f = fopen($path . $vkId . ".json", "w");
            fwrite($f, json_encode($data, JSON_PRETTY_PRINT));
            fclose($f);
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
            $this->main->UpdateTitle();
        }
    }

    public function Get(int $vkId) : ?User
    {
        if (!isset($this->cached[$vkId]))
        {
            return null;
        }
        /*if ((time() - $this->cachedTime[$vkId]) >= self::USER_CACHE_TIME)
        {
            unset($this->cachedTime[$vkId]);
            unset($this->cached[$vkId]);
            return null;
        }*/
        return $this->cached[$vkId];
    }

    public function NeedToUpdate(int $vkId) : bool
    {
        return ((time() - $this->cachedTime[$vkId]) >= self::USER_CACHE_TIME);
    }

    public function GetUsers() : array
    {
        $result = array();
        foreach ($this->cached as $vkId => $user)
        {
            /*if ((time() - $this->cachedTime[$vkId]) >= self::USER_CACHE_TIME)
            {
                unset($this->cachedTime[$vkId]);
                unset($this->cached[$vkId]);
                continue;
            }*/
            $result[$vkId] = $user;
        }
        return $result;
    }

    public function HasUser(int $vkId) : bool
    {
        if (!isset($this->cached[$vkId]))
        {
            return false;
        }
        /*if ((time() - $this->cachedTime[$vkId]) >= self::USER_CACHE_TIME)
        {
            unset($this->cachedTime[$vkId]);
            unset($this->cached[$vkId]);
            return false;
        }*/
        return true;
    }

    public function Add(User $user) : void
    {
        $this->main->UpdateTitle();
        if (!$user->IsHuman())
        {
            $this->main->UpdateTitle();
            return;
        }
        if (!isset($this->cached[$user->GetVkId()]))
        {
            $this->cached[$user->GetVkId()] = $user;
        }
        $this->cachedTime[$user->GetVkId()] = time();
        $this->cacheUpdated[$user->GetVkId()] = true;
        $this->main->UpdateTitle();
    }
}