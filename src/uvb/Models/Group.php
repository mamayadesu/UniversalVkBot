<?php
declare(ticks = 1);

namespace uvb\Models;

use \Exception;
use uvb\Bot;
use uvb\Models\Wall\Wall;
use uvb\System\SystemConfig;
use uvb\Services\UserCache;
use VK\Actions\Groups;
use VK\Client\VKApiClient;

/**
 * Модель сообщества
 */
final class Group implements Entity
{
    /**
     * @var array<int, Group>
     * @ignore
     */
    private static array $LoadedGroups = [];

    /**
     * @ignore
     */
    private int $GroupId, $GroupAccessType;

    /**
     * @ignore
     */
    private string $GroupName, $Domain;

    /**
     * @ignore
     */
    private static ?Group $instance = null;

    /**
     * @ignore
     */
    private Wall $GroupWall;

    /**
     * Не используйте конструктор этого класса! Используйте статический метод GetGroup(int $id)
     *
     * @ignore
     */
    public function __construct(int $GroupId)
    {
        if ($GroupId > 0)
        {
            $GroupId *= -1;
        }
        $this->GroupId = $GroupId;

        $groups_getByIdParams = array
        (
            "group_id" => abs($GroupId)
        );

        $groups = self::GetApi();
        try
        {
            $response = $groups->getById(SystemConfig::Get("main_admin_access_token"), $groups_getByIdParams)[0];
        }
        catch (Exception $e)
        {
            Bot::GetInstance()->GetLogger()->Error("Group::Get: Failed to get group " . $this->GroupId . ". " . $e->getMessage());
            throw $e;
        }

        $this->GroupName = $response["name"];
        $this->GroupAccessType = $response["is_closed"];
        $this->Domain = $response["screen_name"];
        $this->GroupWall = new Wall($GroupId);
    }

    /**
     * Появилось в API: 1.0
     *
     * @return Wall Стена сообщества
     */
    public function GetWall() : Wall
    {
        return $this->GroupWall;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return string Название сообщества
     */
    public function GetName() : string
    {
        return $this->GroupName;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return GroupAccessType Тип сообщества
     */
    public function GetGroupAccessType() : int
    {
        return $this->GroupAccessType;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return string Адрес сообщества
     */
    public function GetDomain() : string
    {
        return $this->Domain;
    }

    /**
     * Получить экземпляр группы, указанной в конфиге
     *
     * Появилось в API: 1.0
     *
     * @param int $vkId Идентификатор сообщества. Положительность числа не имеет значения. Оно автоматически преобразуется
     * @return Group
     */
    public static function Get(int $vkId) : Group
    {
        $id = abs($vkId);
        if (!isset(self::$LoadedGroups[$id]))
        {
            self::$LoadedGroups[$id] = new Group($id);
        }
        return self::$LoadedGroups[$id];
    }

    /**
     * Получить список пользователей сообщества
     *
     * Появилось в API: 1.0
     *
     * @return array<User> Список пользователей
     */
    public function GetMembers() : array
    {
        $userCache = UserCache::GetInstance();
        $result = [];
        $groups_getMembersParams = array
        (
            "group_id" => abs($this->GroupId),
            "offset" => 0,
            "count" => 1000,
            "fields" => User::UserFilters
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
                $members = $groups->getMembers($this->GetAccessToken(), $groups_getMembersParams);
            }
            catch (Exception $e)
            {
                Bot::GetInstance()->GetLogger()->Error("Group::GetMembers: Failed to get members of group " . $this->GroupId . ". " . $e->getMessage());
                throw $e;
            }
            foreach ($members["items"] as $item)
            {
                if ($userCache->HasUser($item["id"]))
                {
                    $user = $userCache->Get($item["id"]);
                    $user->__updateData(array("nom" => $item["first_name"]), array("nom" => $item["last_name"]),
                        ($item["sex"] ?? UserSex::MALE),
                        ($item["bdate"] ?? ""),
                        (isset($item["city"]) ? $item["city"]["title"] : ""),
                        (isset($item["county"]) ? $item["county"]["title"] : ""),
                        ($item["domain"] ?? ""),
                        ($item["status"] ?? "")
                    );
                    $userCache->Add($user);
                }
                else
                {
                    $user = User::Get($item["id"]);
                }
                $result[] = $user;
            }
            $groups_getMembersParams["offset"] += 1000;
        }
        while (count($members["items"]) > 0);
        return $result;
    }

    /**
     * Исключить пользователя из сообщества (указанное в конфигурации бота) или отменить его заявку на вступление в сообщество
     *
     * Появилось в API: 1.0
     *
     * @param User $user
     * @return bool TRUE - пользователей исключён из сообщества. FALSE - произошла ошибка (смотреть консоль)
     */
    public function KickMember(User $user) : bool
    {
        $params = array
        (
            "group_id" => abs($this->GroupId),
            "user_id" => $user->GetVkId()
        );
        try
        {
            (self::GetApi())->removeUser(SystemConfig::Get("main_admin_access_token"), $params);
        }
        catch (Exception $e)
        {
            Bot::GetInstance()->GetLogger()->Critical("Error Group::KickMember(User->GetVkId() = " . $user->GetVkId() . "): " . $e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Одобрить вступление пользователя в сообщество
     *
     * Появилось в API: 1.0
     *
     * @param User $user
     * @return bool
     */
    public function ApproveUserRequest(User $user) : bool
    {
        $params = array
        (
            "group_id" => abs($this->GroupId),
            "user_id" => $user->GetVkId()
        );
        try
        {
            (self::GetApi())->approveRequest(SystemConfig::Get("main_admin_access_token"), $params);
        }
        catch (Exception $e)
        {
            Bot::GetInstance()->GetLogger()->Critical("Error Group::ApproveUserRequest(User->GetVkId() = " . $user->GetVkId() . "): " . $e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Возвращает access-token данной группы, если он заполнен в config.json. В противном случае будет возвращено NULL
     *
     * Появилось в API: 1.0
     *
     * @return string|null
     */
    public function GetAccessToken() : ?string
    {
        $group_in_config = "club" . (-$this->GroupId);

        if (isset(SystemConfig::Get("groups_to_access_tokens")[$group_in_config]))
        {
            return SystemConfig::Get("groups_to_access_tokens")[$group_in_config];
        }
        return null;
    }

    /**
     * @ignore
     */
    public static function GetApi() : Groups
    {
        return Bot::GetVkApi()->groups();
    }

    /**
     * Появилось в API: 1.0
     *
     * @return int
     */
    public function GetVkId() : int
    {
        return $this->GroupId;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return string
     */
    public function GetMention() : string
    {
        return "@club" . (-$this->GetVkId()) . " (" . $this->GetName() . ")";
    }

    /**
     * Появилось в API: 1.0
     *
     * @return string
     */
    public function GetFullMention() : string
    {
        return $this->GetMention();
    }

    /**
     * Появилось в API: 1.0
     *
     * @return bool
     */
    public function IsHuman() : bool
    {
       return false;
    }
}