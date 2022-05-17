<?php

namespace uvb\Models;

use \Exception;
use uvb\Bot;
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
     * @ignore
     */
    private static array/*<int, Group>*/ $LoadedGroups = [];

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
     * Не используйте конструктор этого класса! Используйте статический метод GetGroup(int $id)
     *
     * @ignore
     */
    public function __construct(int $GroupId)
    {
        if ($GroupId < 0)
        {
            $GroupId *= -1;
        }
        $this->GroupId = $GroupId;

        $groups_getByIdParams = array
        (
            "group_id" => $GroupId
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
    }

    /**
     * @return string Название сообщества
     */
    public function GetName() : string
    {
        return $this->GroupName;
    }

    /**
     * @return GroupAccessType Тип сообщества
     */
    public function GetGroupAccessType() : int
    {
        return $this->GroupAccessType;
    }

    /**
     * @return string Адрес сообщества
     */
    public function GetDomain() : string
    {
        return $this->Domain;
    }

    /**
     * Получить экземпляр группы, указанной в конфиге
     *
     * @param int $id Идентификатор сообщества. Не указывайте или укажите 0, если нужно получить объект сообщества, указанного в конфиге. Положительность числа не имеет значения. Оно автоматически преобразуется
     * @return Group
     */
    public static function Get($id = 0) : Group
    {
        $id = abs($id);
        if ($id == 0)
        {
            if (self::$instance == null)
            {
                $id = intval(SystemConfig::Get("group_id"));
                self::$instance = new Group($id);
                self::$LoadedGroups[$id] = self::$instance;
            }
            return self::$instance;
        }
        else
        {
            if (!isset(self::$LoadedGroups[$id]))
            {
                self::$LoadedGroups[$id] = new Group($id);
            }
        }
        return self::$LoadedGroups[$id];
    }

    /**
     * Получить список пользователей сообщества
     *
     * @return array<User> Список пользователей
     */
    public function GetMembers() : array
    {
        $userCache = UserCache::GetInstance();
        $result = [];
        $groups_getMembersParams = array
        (
            "group_id" => $this->GroupId,
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
                $members = $groups->getMembers(SystemConfig::Get("access_token"), $groups_getMembersParams);
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
     * @param User $user
     * @return bool TRUE - пользователей исключён из сообщества. FALSE - произошла ошибка (смотреть консоль)
     */
    public function KickMember(User $user) : bool
    {
        $params = array
        (
            "group_id" => $this->GroupId,
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
     * @ignore
     */
    private static function GetApi() : Groups
    {
        return Bot::GetVkApi()->groups();
    }

    public function GetVkId() : int
    {
        return $this->GroupId;
    }

    public function GetMention() : string
    {
        return "@club" . $this->GetVkId() . " (" . $this->GetName() . ")";
    }

    public function GetFullMention() : string
    {
        return $this->GetMention();
    }

    public function IsHuman() : bool
    {
       return false;
    }
}