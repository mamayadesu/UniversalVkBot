<?php
declare(ticks = 1);

namespace uvb\Models;

use Exception;
use Throwable;
use uvb\Admins;
use uvb\Bot;
use uvb\cmm;
use uvb\System\SystemConfig;
use uvb\Main;
use uvb\Models\Message;
use uvb\Services\UserCache;
use VK\Actions\Users;
use VK\Client\VKApiClient;

/**
 * Class User
 * @package uvb\Models
 *
 * Данный класс описывает пользователя VK
 */

final class User implements Entity
{
    public const UserFilters = "can_write_private_message,sex,bdate,city,country,domain,status";
    /**
     * @ignore
     */
    private int $VkId, $Sex;

    /**
     * @var array<string, string>
     * @ignore
     */
    private array $FirstName;

    /**
     * @var array<string, string>
     * @ignore
     */
    private array $LastName;

    /**
     * @var string
     * @ignore
     */
    private string $Birthday, $City, $Country, $Domain, $Status;

    /**
     * @ignore
     */
    public function __construct(int $VkId, array $FirstName, array $LastName, int $Sex, string $Birthday, string $City, string $Country, string $Domain, string $Status)
    {
        $this->CheckCases(true, $FirstName);
        $this->CheckCases(false, $LastName);
        if (!UserSex::HasItem($Sex))
        {
            throw new Exception("Invalid 'Sex' (" . $Sex . ") given");
        }
        $this->VkId = $VkId;
        $this->FirstName = $FirstName;
        $this->LastName = $LastName;
        $this->Birthday = $Birthday;
        $this->City = $City;
        $this->Country = $Country;
        $this->Domain = $Domain;
        $this->Status = $Status;
        $this->Sex = $Sex;
    }

    /**
     * @ignore
     */
    private function CheckCases(bool $firstName, array $a) : void
    {
        if (!isset($a["nom"]))
        {
            throw new Exception(($firstName ? "'FirstName'" : "'LastName'") . " must contain at least 'nom' case");
        }
        $values = UserNameCases::GetValues();
        foreach ($a as $key => $value)
        {
            if (!in_array($key, $values) || !is_string($value))
            {
                throw new Exception("Invalid '" . $key . "' in " . ($firstName ? "'FirstName'" : "'LastName'") . " given (" . gettype($value) . ")");
            }
        }
    }

    /**
     * @ignore
     */
    public function __updateData(array $FirstName, array $LastName, int $Sex, string $Birthday, string $City, string $Country, string $Domain, string $Status) : void
    {
        if (!$this->IsHuman())
        {
            return;
        }

        $this->CheckCases(true, $FirstName);
        $this->CheckCases(false, $LastName);
        if (!UserSex::HasItem($Sex))
        {
            throw new Exception("Invalid 'Sex' (" . $Sex . ") given");
        }
        foreach ($FirstName as $key => $value)
        {
            $this->FirstName[$key] = $value;
        }
        foreach ($LastName as $key => $value)
        {
            $this->LastName[$key] = $value;
        }
        $this->Birthday = $Birthday;
        $this->City = $City;
        $this->Country = $Country;
        $this->Domain = $Domain;
        $this->Status = $Status;
    }

    /**
     * Получить идентификатор пользователя
     *
     * Появилось в API: 1.0
     *
     * @return int Идентификатор пользователя во ВКонтакте
     */
    public function GetVkId() : int
    {
        return $this->VkId;
    }

    /**
     * Получить имя пользователя
     *
     * Появилось в API: 1.0
     *
     * @return string
     */
    public function GetName() : string
    {
        return $this->GetFirstName() . " " . $this->GetLastName();
    }

    /**
     * Появилось в API: 1.0
     *
     * @return UserSex Пол пользователя.
     */
    public function GetSex() : int
    {
        return $this->Sex;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return string День рождения пользователя. Возвращает в формате <день.месяц.год>. Если год у пользователя не указан, то <день.месяц>. Если дата рождения не указана - пустая строка.
     */
    public function GetBirthday() : string
    {
        return $this->Birthday;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return string Город, указанный у пользователя
     */
    public function GetCity() : string
    {
        return $this->City;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return string Страна, указанная у пользователя
     */
    public function GetCountry() : string
    {
        return $this->Country;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return string Домен пользователя. Может быть "idАЙДИПОЛЬЗОВАТЕЛЯ" либо кастомный домен, если он его задал в настройках
     */
    public function GetDomain() : string
    {
        return $this->Domain;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return string Статус пользователя
     */
    public function GetStatus() : string
    {
        return $this->Status;
    }

    public function HasUnfilledNameCases() : bool
    {
        if (!$this->IsHuman())
        {
            return false;
        }
        $nameCases = User::GetNameCases();
        foreach ($nameCases as $nc)
        {
            if (!isset($this->FirstName[$nc]) || $this->FirstName[$nc] == "" || !isset($this->LastName[$nc]) || $this->LastName[$nc] == "")
            {
                return true;
            }
        }
        return false;
    }

    /**
     * @ignore
     */
    private static function CheckNameCase(string $nameCase) : string
    {
        $nameCases = User::GetNameCases();
        if (!in_array($nameCase, $nameCases))
        {
            return UserNameCases::NOM;
        }
        return $nameCase;
    }

    /**
     * Получить имя человека во всех падежах.
     * Замечание: если имя в каком-либо падеже не было загружено, то данный метод не будет отправлять запрос к VK API. Для этого можете использовать метод GetFirstName(UserNameCases $nameCase, bool $autoQuery)
     *
     * Появилось в API: 1.0
     *
     * @return array<string> Массив имени человека во всех падежах
     */
    public function GetFirstNameAsArray() : array
    {
        return $this->FirstName;
    }

    /**
     * Получить имя человека
     *
     * Появилось в API: 1.0
     *
     * @param UserNameCases $nameCase Падеж имени, в котором нужно его получить
     * @param bool $autoQuery Отправлять ли запрос к VK API, если имя в указанном падеже не было загружено
     * @return string Имя человека в указанном падеже
     */
    public function GetFirstName(string $nameCase = "nom", bool $autoQuery = true) : string
    {
        $nameCase = self::CheckNameCase($nameCase);
        if ($this->HasUnfilledNameCases() && $autoQuery)
        {
            User::Get($this->VkId);
        }
        else if (!isset($this->FirstName[$nameCase]) && !$autoQuery)
        {
            return "";
        }
        return $this->FirstName[$nameCase];
    }

    /**
     * Получить фамилию человека во всех падежах.
     * Замечание: если фамилия в каком-либо падеже не было загружено, то данный метод не будет отправлять запрос к VK API. Для этого можете использовать метод GetLastName(UserNameCases $nameCase, bool $autoQuery)
     *
     * Появилось в API: 1.0
     *
     * @return array<string> Массив фамилии человека во всех падежах
     */
    public function GetLastNameAsArray() : array
    {
        return $this->LastName;
    }

    /**
     * Получить фамилию человека
     *
     * Появилось в API: 1.0
     *
     * @param UserNameCases $nameCase Падеж фамилии, в котором нужно её получить
     * @param bool $autoQuery Отправлять ли запрос к VK API, если фамилия в указанном падеже не была загружена
     * @return string Фамилия человека в указанном падеже
     */
    public function GetLastName(string $nameCase = "nom", bool $autoQuery = true) : string
    {
        $nameCase = self::CheckNameCase($nameCase);
        if ($this->HasUnfilledNameCases() && $autoQuery)
        {
            User::Get($this->VkId);
        }
        else if (!isset($this->LastName[$nameCase]) && !$autoQuery)
        {
            return "";
        }
        return $this->LastName[$nameCase];
    }

    /**
     * Появилось в API: 1.0
     *
     * @return bool TRUE - объектом является живой человек. FALSE - объектом является консоль
     */
    public function IsHuman() : bool
    {
        return $this->GetVkId() > 0;
    }

    /**
     * Получить тэг для упоминания человека в беседе
     *
     * Появилось в API: 1.0
     *
     * @param string $nameCase Падеж имени
     * @return string Текст упоминания человека
     */
    public function GetMention(string $nameCase = "nom") : string
    {
        if ($this->GetVkId() == 0 && $this->GetFirstName() == "CONSOLE")
        {
            return "*Console";
        }
        return "@id" . $this->GetVkId() . " (" . $this->GetFirstName($nameCase) . ")";
    }

    /**
     * Получить тэг для упоминания человека в беседе с его фамилией
     *
     * Появилось в API: 1.0
     *
     * @param string $nameCase Падеж имени и фамилии
     * @return string Текст упоминания человека
     */
    public function GetFullMention($nameCase = "nom") : string
    {
        if ($this->GetVkId() == 0 && $this->GetFirstName() == "CONSOLE")
        {
            return "*Console";
        }
        return "@id" . $this->GetVkId() . " (" . $this->GetFirstName($nameCase) . " " . $this->GetLastName($nameCase) . ")";
    }

    /**
     * Отправить личное сообщение человеку
     *
     * Появилось в API: 1.0
     *
     * @param string $text Текст сообщения
     * @param array<string> $attachments Список вложений. Вложения указывать в простом формате: <mediatype><owner>_<attachment>_<accesskey>. Например: photo1234_5678
     * @param BotKeyboard|null $keyboard Клавиатура бота. Если не нужно указывать клавиатуру бота, можно просто указать "пустой" экземпляр класса BotKeyboard или указать NULL
     * @param Geolocation|null $geolocation Геолокация
     */
    public function SendMessage(string $text, array $attachments = [], ?Group $by_group = null, ?BotKeyboard $keyboard = null, ?Geolocation $geolocation = null) : void
    {
        $text = str_replace("<user>", $this->GetMention(), $text);
        $text = str_replace("<fulluser>", $this->GetFullMention(), $text);

        $text = str_replace(
            ["<nom>", "<gen>", "<dat>", "<acc>", "<ins>", "<abl>",
                "<fnom>", "<fgen>", "<fdat>", "<facc>", "<fins>", "<fabl>"],
            [$this->GetMention(UserNameCases::NOM), $this->GetMention(UserNameCases::GEN), $this->GetMention(UserNameCases::DAT), $this->GetMention(UserNameCases::ACC), $this->GetMention(UserNameCases::INS), $this->GetMention(UserNameCases::ABL),
                $this->GetFullMention(UserNameCases::NOM), $this->GetFullMention(UserNameCases::GEN), $this->GetFullMention(UserNameCases::DAT), $this->GetFullMention(UserNameCases::ACC), $this->GetFullMention(UserNameCases::INS), $this->GetFullMention(UserNameCases::ABL)],
            $text);
        try
        {
            Message::Send($text, $this, $by_group, $attachments, $keyboard, $geolocation);
        }
        catch (\Exception $e)
        {
            Bot::GetInstance()->GetLogger()->Error("(User::SendMessage) " . cmm::g("user.sendmessage.error", [$this->GetMention(), $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()]));
        }
    }

    /**
     * Появилось в API: 1.0
     *
     * @return bool Является ли человек админом. Всегда будет TRUE, если объектом пользователя является консоль
     */
    public function IsAdmin() : bool
    {
        if (!$this->IsHuman())
        {
            return true;
        }

        return in_array($this->GetVkId(), Admins::GetAdmins());
    }

    /**
     * Назначить или убрать из списка администраторов аккаунт VK
     *
     * Появилось в API: 1.0
     *
     * @param bool $set
     * @return void
     */
    public function SetAdmin(bool $set = true) : void
    {
        if ($set)
        {
            Admins::AddAdmin($this->GetVkId());
        }
        else
        {
            Admins::RemoveAdmin($this->GetVkId());
        }
    }

    /**
     * Отправить сообщение человеку (эквивалент SendMessage(string $text, array<string> $attachments, BotKeyboard $keyboard)
     *
     * Появилось в API: 1.0
     *
     * @param string $text Текст сообщения. Замечание: в тексте сообщения можно использовать теги <user> и <fulluser>. Они будут использоваться как тэги упоминания с имени и тэги упоминания с именем и фамилией соответственно
     */
    public function Send(string $text, ?Group $group = null) : void
    {
        if ($group == null)
        {
            $group = Bot::GetInstance()->GetDefaultGroup();
        }
        $text = str_replace("<user>", $this->GetMention(), $text);
        $text = str_replace("<fulluser>", $this->GetFullMention(), $text);
        try
        {
            Message::Send($text, $this, $group, []);
        }
        catch (Exception $e)
        {
            Bot::GetInstance()->GetLogger()->Log("(User::Send) " . cmm::g("user.sendmessage.error", [$this->GetMention(), $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()]));
        }
    }

    /**
     * Разблокировать пользователя в сообществе
     *
     * Появилось в API: 1.0
     *
     * @param Group $group
     * @return void
     */
    public function Unban(Group $group) : void
    {
        $groups = Group::GetApi();

        $groups_unbanParams = array(
            "group_id" => $group->GetVkId(),
            "owner_id" => $this->GetVkId()
        );

        try
        {
            $groups->unban(SystemConfig::Get("main_admin_access_token"), $groups_unbanParams);
        }
        catch (Throwable $e)
        {
            Bot::GetInstance()->GetLogger()->Critical("Failed to unban user '" . $this->GetVkId() . "' in group '" . $group->GetVkId() . "'. " . $e->getMessage());
        }
    }

    /**
     * Получить объект пользователя по его идентификатору
     *
     * Появилось в API: 1.0
     *
     * @param int $vkId Идентификатор пользователя
     * @return User|null Объект пользователя. NULL в случае, если пользователя с таким идентификатор нет
     */
    public static function Get(int $vkId) : ?User
    {
        $result = self::GetUsers([$vkId]);
        foreach ($result as $id => $user)
        {
            return $user;
        }
        return null;
    }

    /**
     * Получить объект "консоли"
     *
     * Появилось в API: 1.0
     *
     * @return User Консоль как объект пользователя
     */
    public static function GetConsoleAsUser() : User
    {
        return Main::GetConsoleAsUser();
    }

    /**
     * Получить список кодов падежей
     *
     * Появилось в API: 1.0
     *
     * @return string[]
     */
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
     * Появилось в API: 1.0
     *
     * @param array<int> $vkIds2 Список идентификаторов пользователей. В массиве могут находиться только целые числа
     * @return array<?User> Список пользователей
     * @throws Exception
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
                $result[$vkId] = $userFromUserCache;
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
            "fields" => User::UserFilters
        );

        $multiParams = self::GenerateParams($params);
        $result = [];
        $r = null;
        $foundVkIds = [];
        $vkidToSex = array();
        $vkidToFirstNameCases = array();
        $vkidToLastNameCases = array();
        $vkidToStatus = array();
        $vkidToBirthday = array();
        $vkidToCity = array();
        $vkidToCountry = array();
        $vkidToDomain = array();
        $isNullUser = false;
        foreach ($multiParams as $p)
        {
            
            $isNullUser = false;
            try
            {
                $r = $users->get(SystemConfig::Get("main_admin_access_token"), $p);
            }
            catch (Exception $e)
            {
                $isNullUser = true;
            }
            foreach ($r as $row)
            {
                if ($isNullUser)
                {
                    $result[$row["id"]] = null;
                    continue;
                }
                if (!in_array($row["id"], $foundVkIds))
                {
                    $foundVkIds[] = $row["id"];
                }
                $vkidToSex[$row["id"]] = $row["sex"];
                $vkidToBirthday[$row["id"]] = "";
                if (isset($row["bdate"]))
                    $vkidToBirthday[$row["id"]] = $row["bdate"];

                $vkidToCity[$row["id"]] = "";
                if (isset($row["city"]))
                    $vkidToCity[$row["id"]] = $row["city"]["title"];

                $vkidToCountry[$row["id"]] = "";
                if (isset($row["country"]))
                    $vkidToCountry[$row["id"]] = $row["country"]["title"];

                $vkidToStatus[$row["id"]] = "";
                if (isset($row["status"]))
                    $vkidToStatus[$row["id"]] = $row["status"];

                $vkidToDomain[$row["id"]] = $row["domain"];
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
            $birthday = $vkidToBirthday[$id];
            $city = $vkidToCity[$id];
            $country = $vkidToCountry[$id];
            $domain = $vkidToDomain[$id];
            $status = $vkidToStatus[$id];

            $userRes = $userCache->Get($id);
            
            if ($userRes == null)
            {
                $userRes = new User($id, $firstNames, $lastNames, $sex, $birthday, $city, $country, $domain, $status);
            }
            else if ($userCache->NeedToUpdate($id) || $userRes->HasUnfilledNameCases())
            {
                $userRes->__updateData($firstNames, $lastNames, $sex, $birthday, $city, $country, $domain, $status);
            }
            $userCache->Add($userRes);
            $result[$id] = $userRes;
        }

        return $result;
    }

    public static function ParseVkProfile(array $profile) : User
    {
        return new User(
            $profile["id"],
            array(
                "nom" => $profile["first_name"]
            ),
            array(
                "nom" => $profile["last_name"]
            ),
            $profile["sex"] ?? UserSex::UNKNOWN,
            $profile["bdate"] ?? "",
            isset($profile["city"]) ? $profile["city"]["title"] : "",
            isset($profile["county"]) ? $profile["country"]["title"] : "",
            $profile["domain"] ?? "",
            $profile["status"] ?? ""
        );
    }

    /**
     * @ignore
     */
    private static function GetApi() : Users
    {
        return Bot::GetVkApi()->users();
    }
}