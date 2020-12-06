<?php

namespace uvb\Repositories;

use \Exception;
use uvb\Bot;
use uvb\Models\User;
use uvb\Services\UserCache;
use \VK\Client\VKApiClient;
use \VK\Actions\Users;
use uvb\Config;
use uvb\Main;

/**
 * Репозиторий для работы с пользователями
 * @package uvb\Repositories
 *
 *
 */

class UserRepository
{

    /**
     * Получить объект пользователя по его идентификатору
     *
     * @param int $vkId Идентификатор пользователя
     * @return User|null Объект пользователя. NULL в случае, если пользователя с таким идентификатор нет
     */
    public static function Get(int $vkId) : ?User
    {
        $result = self::GetUsers([$vkId]);
        if (isset($result[0]))
        {
            return $result[0];
        }
        else
        {
            return null;
        }
    }

    /**
     * Получить объект пользователя "КОНСОЛЬ"
     *
     * @return User Консоль как объект пользователя
     */
    public static function GetConsoleAsUser() : User
    {
        return Main::GetConsoleAsUser();
    }

    public static function GetNameCases() : array
    {
        return ["nom", "gen", "dat", "acc", "ins", "abl"];
    }

    /**
     * @ignore
     */
    private static function GenerateParams(array $params) : array
    {
        $result = [];
        $c = -1;
        foreach (self::GetNameCases() as $nameCase)
        {
            $c++;
            $result[$c] = array();
            foreach ($params as $key => $value)
            {
                $result[$c][$key] = $value;
            }
            $result[$c]["name_case"] = $nameCase;
        }
        return $result;
    }

    /**
     * Получить несколько пользователей
     *
     * @param array<int> $vkIds2 Список идентификаторов пользователей. В массиве могут находиться только целые числа
     * @return array<User> Список пользователей
     */
    public static function GetUsers(array $vkIds2) : array
    {
        $userCache = UserCache::GetInstance();
        $vkIds1 = [];
        $limit = 0;
        foreach ($vkIds2 as $id)
        {
            if ($limit >= 100)
            {
                break;
            }
            if (intval($id) < 1)
            {
                continue;
            }
            $vkIds1[] = intval($id);
            $limit++;
        }
        $ignored = [];
        $result = [];
        $userFromUserCache = null;
        foreach ($vkIds1 as $vkId)
        {
            $userFromUserCache = $userCache->Get($vkId);
            if ($userFromUserCache != null && !$userCache->NeedToUpdate($vkId) && !$userFromUserCache->HasUnfilledNameCases())
            {
                $ignored[] = $vkId;
                $result[] = $userFromUserCache;
            }
        }
        $vkIds = [];
        foreach ($vkIds1 as $vkId)
        {
            if (in_array($vkId, $ignored))
            {
                continue;
            }
            $vkIds[] = $vkId;
        }
        if (count($vkIds) == 0)
        {
            return $result;
        }
        $users = self::GetApi();
        $params = array
        (
            "user_ids" => implode(',', $vkIds),
            "fields" => "last_seen,sex"
        );

        $multiParams = self::GenerateParams($params);
        $result = [];
        $r = null;
        $foundVkIds = [];
        $vkidToSex = array();
        $vkidToFirstNameCases = array();
        $vkidToLastNameCases = array();

        foreach ($multiParams as $p)
        {
            try
            {
                $r = $users->get(Config::Get("access_token"), $p);
            }
            catch (Exception $e)
            {
                Bot::GetInstance()->GetLogger()->Error("UserRepository::GetUsers: Failed to get user or users. " . $e->getMessage());
                return array();
            }
            foreach ($r as $row)
            {
                if (!in_array($row["id"], $foundVkIds))
                {
                    $foundVkIds[] = $row["id"];
                }
                $vkidToSex[$row["id"]] = $row["sex"];
                if (!isset($vkidToFirstNameCases[$row["id"]]))
                {
                    $vkidToFirstNameCases[$row["id"]] = array();
                    $vkidToLastNameCases[$row["id"]] = array();
                }
                $vkidToFirstNameCases[$row["id"]][$p["name_case"]] = $row["first_name"];
                $vkidToLastNameCases[$row["id"]][$p["name_case"]] = $row["last_name"];
            }
        }



        $firstNames = array();
        $lastNames = array();
        $userRes = null;
        foreach ($foundVkIds as $id)
        {
            $firstNames = $vkidToFirstNameCases[$id];
            $lastNames = $vkidToLastNameCases[$id];
            $sex = $vkidToSex[$id];

            $userRes = $userCache->Get($id);
            if ($userRes == null)
            {
                $userRes = new User($id, $firstNames, $lastNames, $sex);
            }
            else if ($userCache->NeedToUpdate($id) || $userRes->HasUnfilledNameCases())
            {
                $userRes->__updateData($firstNames, $lastNames, $sex);
            }
            $userCache->Add($userRes);
            $result[] = $userRes;
        }

        return $result;
    }

    /*public static function GetUsersOld(array $vkIds2) : array
    {
        $userCache = UserCacheResource::$userCache;
        $vkIds1 = [];
        $limit = 0;
        foreach ($vkIds2 as $id)
        {
            if ($limit >= 100)
            {
                break;
            }
            if (intval($id) < 1)
            {
                continue;
            }
            $vkIds1[] = intval($id);
            $limit++;
        }
        $ignored = [];
        $result = [];
        foreach ($vkIds1 as $vkId)
        {
            if ($userCache->HasUser($vkId) && !$userCache->NeedToUpdate($vkId))
            {
                $ignored[] = $vkId;
                $result[] = $userCache->Get($vkId);
            }
        }
        $vkIds = [];
        foreach ($vkIds1 as $vkId)
        {
            if (in_array($vkId, $ignored))
            {
                continue;
            }
            $vkIds[] = $vkId;
        }
        if (count($vkIds) == 0)
        {
            return $result;
        }
        $users = self::GetApi();
        $params = array
        (
            "user_ids" => implode(',', $vkIds),
            "fields" => "can_write_private_message"
        );
        try
        {
            $response = $users->get(Config::Get("access_token"), $params);
        }
        catch (\Exception $e)
        {
            Bot::GetInstance()->GetLogger()->Error("UserRepository::GetUsers: Failed to get user or users. " . $e->getMessage());
            return array();
        }
        $user = null;
        for ($i = 0; $i < count($response); $i++)
        {
            if ($userCache->HasUser($response[$i]["id"]) && $userCache->NeedToUpdate($response[$i]["id"]))
            {
                $user = $userCache->Get($response[$i]["id"]);
                $user->__updateData($response[$i]["first_name"], $response[$i]["last_name"]);
            }
            else if (!$userCache->HasUser($response[$i]["id"]))
            {
                $user = new User($response[$i]["id"], $response[$i]["first_name"], $response[$i]["last_name"]);
            }
            $userCache->Add($user);
            $result[] = $user;
        }
        return $result;
    }*/

    /**
     * @ignore
     */
    private static function GetApi() : Users
    {
        return (new VKApiClient())->users();
    }
}