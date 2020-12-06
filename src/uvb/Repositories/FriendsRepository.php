<?php

namespace uvb\Repositories;

use uvb\Config;
use uvb\Models\User;
use VK\Actions\Friends;
use VK\Client\VKApiClient;

class FriendsRepository
{
    public const OUTCOMING_REQUESTS = true;
    public const INCOMING_REQUESTS = false;

    public static function GetFriends() : array
    {
        $result = [];
        $friends = self::GetApi();
        $friends_getParams = array
        (
            "count" => 5000,
            "offset" => 0,
            "fields" => "can_write_private_message"
        );
        $list = array
        (
            "items" => []
        );
        do
        {
            try
            {
                $list = $friends->get(Config::Get("access_token"), $friends_getParams);
            }
            catch (\Exception $e)
            {
                $list = array
                (
                    "items" => []
                );
            }
            foreach ($list["items"] as $item)
            {
                if ($item["can_write_private_message"] == 0)
                {
                    continue;
                }
                $result[] = new User($item["id"], $item["first_name"], $item["last_name"]);
            }
            $friends_getParams["offset"] += 5000;
        }
        while (count($list["items"]) > 0);
        return $result;
    }

    public static function Add(User $user) : void
    {
        $friends = self::GetApi();
        $friends_addParams = array
        (
            "user_id" => $user->GetVkId()
        );
        try
        {
            $friends->add(Config::Get("access_token"), $friends_addParams);
        }
        catch (\Exception $e)
        {
            //Logger::Log("Ошибка добавления пользователя id " . $user->GetVkId() . " (" . $user->GetFirstName() . " " . $user->GetLastName() . "). " . $e->getMessage());
        }
    }

    public static function Delete(User $user) : void
    {
        $friends = self::GetApi();
        $friends_deleteParams = array
        (
            "user_id" => $user->GetVkId()
        );
        try
        {
            $friends->delete(Config::Get("access_token"), $friends_deleteParams);
        }
        catch (\Exception $e)
        {
            //Logger::Log("Ошибка удаления пользователя id " . $user->GetVkId() . " (" . $user->GetFirstName() . " " . $user->GetLastName() . "). " . $e->getMessage());
        }
    }

    public static function GetRequests(bool $inOrOutComing) : array
    {
        $result = [];
        $friends = self::GetApi();
        $friends_getRequestsParams = array
        (
            "offset" => 0,
            "count" => 1000,
            "extended" => true,
            "out" => $inOrOutComing,
            "need_viewed" => true
        );
        $list = array
        (
            "items" => []
        );
        do
        {
            try
            {
                $list = $friends->getRequests(Config::Get("access_token"), $friends_getRequestsParams);
            }
            catch (\Exception $e)
            {

            }
            foreach ($list["items"] as $item)
            {
                if (isset($list["deactivated"]))
                {
                    continue;
                }
                $result[] = new User($item["user_id"], $item["first_name"], $item["last_name"]);
            }
            $friends_getRequestsParams["offset"] += 5000;
        }
        while (count($list["items"]) > 0);
        return $result;
    }

    private static function GetApi() : Friends
    {
        return (new VKApiClient())->friends();
    }
}