<?php
declare(ticks = 1);

namespace uvb\Models\Wall;

use uvb\Bot;
use uvb\Models\Group;
use uvb\Models\User;
use uvb\Models\UserSex;
use uvb\System\SystemConfig;
use uvb\Services\UserCache;
use uvb\Utils\EntitiesParser;
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
     * @param WallFilters $filter
     * @param int $offset Смещение. Начинается с 0
     * @param int $count Количество записей
     * @param bool $allPosts Если TRUE, в цикле загружает все записи со стены
     * @return Post[]
     */
    public function GetPosts(string $filter = WallFilters::ALL, int $offset = 0, int $count = 100, bool $allPosts = false) : array
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
        $tempEntities = [];
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

            $tempEntities = EntitiesParser::Parse($posts, $tempEntities);

            foreach ($posts["items"] as $item)
            {
                $result[] = new Post(
                    $item["id"],
                    $item["date"],
                    $item["text"],
                    (bool)$item["marked_as_ads"],
                    (bool)$item["is_favorite"],
                    $tempEntities[$item["from_id"]] ?? null,
                    $tempEntities[$item["owner_id"]] ?? null,
                    isset($item["created_by"]) ? $tempEntities[$item["created_by"]] : null,
                    $item["attachments"] ?? []
                );
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
        while ($allPosts && count($posts["items"]) > 0);
        return $result;
    }

    /**
     * @ignore
     */
    private static function GetApi() : VKWall
    {
        return Bot::GetVkApi()->wall();
    }
}