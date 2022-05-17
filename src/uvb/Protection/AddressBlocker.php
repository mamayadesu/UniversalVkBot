<?php

namespace uvb\Protection;

use Application\Application;
use uvb\Main;

/**
 * Сервис блокировки IP-адресов
 * @package uvb\Protection
 *
 *
 */

class AddressBlocker
{
    /**
     * @ignore
     */
    private array $bannedIps = [];

    /**
     * @ignore
     */
    private Main $main;

    /**
     * @ignore
     */
    private static ?AddressBlocker $instance = null;

    /**
     * @ignore
     */
    public function __construct(Main $main)
    {
        if (self::$instance != null)
        {
            throw new \Exception("AddressBlocker is already initialized");
        }
        $this->main = $main;
        $path = Application::GetExecutableDirectory() . "banned_ips.txt";
        if (!file_exists($path))
        {
            $f = fopen($path, "w");
            fwrite($f, "");
            fclose($f);
        }
    }

    /**
     * Загружает список заблокированных IP-адресов и подсетей
     */
    public function Load() : void
    {
        $this->bannedIps = [];
        $path = Application::GetExecutableDirectory() . "banned_ips.txt";
        if (!file_exists($path))
        {
            $f = fopen($path, "w");
            fwrite($f, "");
            fclose($f);
            return;
        }

        $content = file_get_contents($path);
        $content = str_replace("\r", "", $content);

        $data = explode("\n", $content);

        \hat();
        foreach ($data as $ip)
        {
            $this->Ban($ip);
            \hat();
        }
    }

    /**
     * Заблокировать IP-адрес или подсеть
     *
     * @param string $address IP-адрес или подсеть. Если нужно заблокировать подсеть, можно указать: 5.123.47.* или 5.123.*.*
     * @return bool
     */
    public function Ban(string $address) : bool
    {
        $address1 = explode('.', $address);
        $allStars = true;
        foreach ($address1 as $n)
        {
            $n = str_replace(",", "", $n);
            if ($n != "*")
            {
                $allStars = false;
            }

            if ($n != "*" && (intval($n)) . "" != $n)
            {
                return false;
            }
        }
        if ($allStars)
        {
            return false;
        }
        if (!in_array($address, $this->bannedIps))
        {
            $this->bannedIps[] = $address;
        }
        return true;
    }

    /**
     * Разблокировать IP-адрес или подсеть
     *
     * @param string $address
     */
    public function Unban(string $address) : void
    {
        if (in_array($address, $this->bannedIps))
        {
            $newArr = [];
            foreach ($this->bannedIps as $addr)
            {
                if ($address == $addr)
                {
                    continue;
                }
                $newArr[] = $addr;
            }

            $this->bannedIps = $newArr;
        }
    }

    /**
     * Содержит ли URI или тело запроса потенциальную угрозу
     *
     * @param string $uri указанный URI или тело запроса
     * @return bool TRUE - URI или тело запроса содержит потенциальную угрозу
     */
    public static function UriHasThreat(string $uri) : bool
    {
        $keywords = ["wget","rm+-rf", "chmod", ".sh", "cd /tmp",
            "cd+/tmp", "phpmyadmin", "curl", "call_user_func_array", "ipconfig",
            "ifconfig"];
        foreach ($keywords as $word)
        {
            if (strpos($uri, $word) !== false || $uri != str_replace($word, "", $uri))
            {
                return true;
            }
        }
        return false;
    }

    /**
     * Сохраняет список заблокированных IP-адресов или подсетей
     */
    public function Save() : void
    {
        $path = Application::GetExecutableDirectory() . "banned_ips.txt";
        $f = fopen($path, "w");
        fwrite($f, implode("\n", $this->bannedIps));
        fclose($f);
    }

    /**
     * Заблокирован ли IP-адрес или подсеть
     *
     * @param string $address IP-адрес или подсеть
     * @param bool $strict Если указать TRUE, то будет искать конкретный IP-адрес или конкретную подсеть. Если указать FALSE - то можно указать подсеть и если какой-либо IP-адрес из этой подсети заблокирован, будет возвращено TRUE
     * @return bool TRUE - IP/подсеть заблокирован(а)
     */
    public function IsBanned(string $address, bool $strict = false) : bool
    {
        if ($strict)
        {
            return $this->__IsBanned2($address);
        }
        else
        {
            return $this->__IsBanned1($address);
        }
    }

    private function __IsBanned2(string $address) : bool
    {
        return in_array($address, $this->bannedIps);
    }

    private function __IsBanned1(string $address) : bool
    {
        $address1 = explode('.', $address);
        if (count($address1) != 4)
        {
            return false;
        }
        $allStars = true;
        foreach ($address1 as $n)
        {
            $n = str_replace(",", "", $n);
            if ($n != "*")
            {
                $allStars = false;
            }

            if ($n != "*" && (intval($n)) . "" != $n)
            {
                return false;
            }
        }
        if ($allStars)
        {
            return false;
        }

        $addr1 = [];
        foreach ($this->bannedIps as $addr)
        {
            $addr1 = explode('.', $addr);
            if (($addr1[0] == "*" || $addr1[0] == $address1[0]) || ($addr1[1] == "*" || $addr1[1] == $address1[1]) || ($addr1[2] == "*" || $addr1[2] == $address1[2]) || ($addr1[3] == "*" || $addr1[3] == $address1[3]))
            {
                return true;
            }
        }
        return false;
    }
}