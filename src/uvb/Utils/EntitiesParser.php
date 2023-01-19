<?php

namespace uvb\Utils;

use uvb\Models\Group;
use uvb\Models\User;
use uvb\Models\UserSex;
use uvb\Services\UserCache;

class EntitiesParser
{
    public static function Parse(array $response, array $data = []) : array
    {
        $userCache = UserCache::GetInstance();
        if (isset($response["profiles"]))
        {
            foreach ($response["profiles"] as $profile)
            {
                if (isset($data[$profile["id"]]))
                    continue;

                if (!$userCache->HasUser($profile["id"]))
                {
                    $data[$profile["id"]] = User::ParseVkProfile($profile);
                }
                else if ($userCache->NeedToUpdate($profile["id"]))
                {
                    $data[$profile["id"]] = $userCache->Get($profile["id"]);
                    $data[$profile["id"]]->__updateData(
                        array(
                            "nom" => $profile["first_name"]
                        ),
                        array(
                            "nom" => $profile["last_name"]
                        ),
                        $profile["sex"] ?? UserSex::UNKNOWN,
                        $profile["bdate"] ?? "",
                        isset($profile["city"]) ? $profile["city"]["title"] : "",
                        isset($profile["country"]) ? $profile["country"]["title"] : "",
                        $profile["domain"] ?? "",
                        $profile["status"] ?? "");
                }
                else
                {
                    $data[$profile["id"]] = User::Get($profile["id"]);
                }
            }
        }

        if (isset($response["groups"]))
        {
            foreach ($response["groups"] as $group)
            {
                if (isset($data[-$group["id"]]))
                    continue;

                $data[-$group["id"]] = Group::Get($group["id"]);
            }
        }
        return $data;
    }
}