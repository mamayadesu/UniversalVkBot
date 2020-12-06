<?php

namespace uvb\Repositories;

use uvb\Bot;
use uvb\Config;
use uvb\Models\User;
use uvb\Services\UserCache;
use VK\Actions\Groups;
use VK\Client\VKApiClient;
use \Exception;

/**
 * Репозиторий для взаимодействия с пользователями сообщества
 * @package uvb\Repositories
 *
 *
 */

class GroupsRepository
{

    /**
     * Получить список пользователей сообщества
     *
     * @param int $group_id Идентификатор сообщества (положительное число)
     * @return array<User> Список пользователей
     */
    public static function GetMembers(int $group_id = -1) : array
    {
        if ($group_id != -1)
        {
            return self::__GetMembers1($group_id);
        }
        else
        {
            return self::__GetMembers2();
        }
    }

    /**
     * @ignore
     */
    private static function __GetMembers1(int $group_id) : array
    {
        Bot::GetInstance()->GetLogger()->Warn("GetMembers(int group_id) temporarily disabled");
        return [];
    }

    /*private static function __GetMembers1(int $group_id) : array
    {
        $userCache = UserCache::GetInstance();
        $result = [];
        $groups_getMembersParams = array
        (
            "group_id" => $group_id,
            "offset" => 0,
            "count" => 1000,
            "fields" => "can_write_private_message,sex"
        );
        $members = array
        (
            "items" => []
        );
        $groups = self::GetApi();
        $user = null;
        do
        {
            try
            {
                $members = $groups->getMembers(Config::Get("access_token"), $groups_getMembersParams);
            }
            catch (Exception $e)
            {
                Bot::GetInstance()->GetLogger()->Error("GroupsRepository::GetMembers: Failed to get members of group " . $group_id . ". " . $e->getMessage());
                break;
            }
            foreach ($members["items"] as $item)
            {
                if ($userCache->HasUser($item["id"]))
                {
                    $user = $userCache->Get($item["id"]);
                    $user->__updateData($item["first_name"], $item["last_name"]);
                }
                else
                {
                    $user = new User($item["id"], $item["first_name"], $item["last_name"]);
                }
                $result[] = $user;
                $userCache->Add($user);
            }
            $groups_getMembersParams["offset"] += 1000;
        }
        while (count($members["items"]) > 0);
        return $result;
    }*/

    /**
     * @ignore
     */
    private static function __GetMembers2() : array
    {
        return self::GetMembers(intval(Config::Get("group_id")));
    }

    /**
     * Исключить пользователя из сообщества (указанное в конфигурации бота) или отменить его заявку на вступление в сообщество
     *
     * @param User $user
     * @return bool TRUE - пользователей исключён из сообщества. FALSE - произошла ошибка (смотреть консоль)
     */
    public static function KickMember(User $user) : bool
    {
        $params = array
        (
            "group_id" => Config::Get("group_id"),
            "user_id" => $user->GetVkId()
        );
        try
        {
            (self::GetApi())->removeUser(Config::Get("main_admin_access_token"), $params);
        }
        catch (Exception $e)
        {
            Bot::GetInstance()->GetLogger()->Critical("Error GroupsRepository::KickMember(User::GetVkId() = " . $user->GetVkId() . "): " . $e->getMessage());
            return false;
        }
        return true;
    }

    private static function GetApi() : Groups
    {
        return (new VKApiClient())->groups();
    }
}