<?php
declare(ticks = 1);

namespace uvb;

use Application\Application;
use \Exception;

final class Admins
{
    /**
     * @ignore
     */
    private static bool $initialized = false;

    /**
     * @ignore
     * @var int[]
     */
    private static array $admins = [];

    /**
     * @ignore
     */
    final public static function Initialize() : void
    {
        if (self::$initialized)
        {
            throw new Exception("This class is already initialized");
        }

        $path_to_file = Application::GetExecutableDirectory() . "admins.txt";

        if (!file_exists($path_to_file))
        {
            @touch($path_to_file);
        }

        if (!file_exists($path_to_file))
        {
            throw new Exception("Failed to create 'admins.txt' file");
        }

        $admins_raw = file_get_contents($path_to_file);
        $admins_raw = str_replace("\r", "", $admins_raw);
        $admins_raw_arr = explode("\n", $admins_raw);

        foreach ($admins_raw_arr as $line)
        {
            $line_int = intval($line);
            if ($line_int < 1 || in_array($line_int, self::$admins))
            {
                continue;
            }

            self::$admins[] = $line_int;
        }

        $admins_raw_new = implode("\n", self::$admins);

        if ($admins_raw != $admins_raw_new)
        {
            self::SaveAdmins();
        }
    }

    /**
     * @ignore
     */
    final public static function SaveAdmins() : void
    {
        $path_to_file = Application::GetExecutableDirectory() . "admins.txt";
        if (!file_exists($path_to_file))
        {
            @touch($path_to_file);
        }

        if (!file_exists($path_to_file))
        {
            throw new Exception("Failed to create 'admins.txt' file");
        }

        file_put_contents($path_to_file, implode("\n", self::$admins));
    }

    /**
     * Возвращает список идентификаторов администраторов
     *
     * Появилось в API: 1.0
     *
     * @return int[]
     */
    final public static function GetAdmins() : array
    {
        return array_merge([0], self::$admins);
    }

    /**
     * @ignore
     */
    final public static function AddAdmin(int $vk_id) : void
    {
        if ($vk_id < 1)
        {
            return;
        }

        if (!in_array($vk_id, self::$admins))
        {
            self::$admins[] = $vk_id;
        }
        self::SaveAdmins();
    }

    /**
     * @ignore
     */
    final public static function RemoveAdmin(int $vk_id) : void
    {
        if ($vk_id < 1)
        {
            return;
        }

        $new_arr = [];
        foreach (self::$admins as $id)
        {
            if ($id < 1 || $id == $vk_id)
            {
                continue;
            }
            $new_arr[] = $id;
        }

        self::$admins = $new_arr;
        self::SaveAdmins();
    }
}