<?php

namespace uvb\Models\Wall;

use uvb\Bot;
use uvb\Models\User;
use uvb\System\SystemConfig;
use uvb\Services\UserCache;
use VK\Actions\Wall as VKWall;
use VK\Client\VKApiClient;
use \Exception;

/**
 * Модель стены сообщества
 */
final class Wall
{
    /**
     * @ignore
     */
    private int $GroupId;

    /**
     * @ignore
     */
    public function __construct(int $GroupId)
    {
        if ($GroupId < 0)
        {
            $GroupId *= -1;
        }
        $this->GroupId = $GroupId;
    }

    /**
     * Получить список записей сообщества.
     *
     * @param WallFilters $filters
     * @param int $offset Смещение. Начинается с 0
     * @param int $count Количество записей
     * @return array<int, Post>
     */
    public function GetPosts(string $filter = WallFilters::ALL, int $offset = 0, int $count = 100) : array/*<Post>*/
    {
        $userCache = UserCache::GetInstance();
        $result = [];
        $wall_getParams = array
        (
            "owner_id" => -$this->GroupId,
            "offset" => $offset,
            "count" => $count,
            "extended" => true,
            "fields" => User::UserFilters,
            "filter" => $filter
        );
        $wall = self::GetApi();
        $posts = array
        (
            "items" => []
        );

        do
        {
            try
            {
                $posts = $wall->get(SystemConfig::Get("main_admin_access_token"), $wall_getParams);
            }
            catch (Exception $e)
            {
                Bot::GetInstance()->GetLogger()->Error("Wall::GetPosts: Failed to get posts of wall group " . $this->GroupId . ". " . $e->getMessage());
                throw $e;
            }

            foreach ($posts["items"] as $item)
            {
                // ToDo
            }
            if ($count < 0)
            {
                $wall_getParams["offset"] += 100;
            }
            else
            {
                break;
            }
        }
        while (count($posts["items"]) > 0);
        return $result;
    }

    /**
     * Добавить метод поиска записей (?)
     */

    /**
     * @ignore
     */
    private static function GetApi() : VKWall
    {
        return Bot::GetVkApi()->wall();
    }
}