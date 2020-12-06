<?php

namespace uvb\Models;

use uvb\Bot;
use uvb\cmm;
use uvb\Config;
use uvb\Repositories\MessageRepository;
use uvb\Repositories\UserRepository;

/**
 * Class User
 * @package uvb\Models
 *
 * Данный класс описывает пользователя VK
 */

class User
{
    /**
     * @ignore
     */
    private int $VkId, $Sex;

    /**
     * @ignore
     */
    private array/*<string, string>*/ $FirstName;

    /**
     * @ignore
     */
    private array/*<string, string>*/ $LastName;

    /**
     * @ignore
     */
    public function __construct(int $VkId, $FirstName, $LastName, $Sex = -1)
    {
        if (is_array($FirstName) && is_array($LastName) && ($Sex == 1 || $Sex == 2))
        {
            $this->____construct1($VkId, $FirstName, $LastName, $Sex);
        }
        else if (is_string($FirstName) && is_string($LastName) && ($Sex != 1 && $Sex != 2))
        {
            $this->____construct2($VkId, $FirstName, $LastName);
        }
        else
        {
            throw new \InvalidArgumentException("Invalid constructor arguments passed. Overloads:\n" .
                "(int VkId, array<string, string> FirstName, array<string, string> LastName, UserSex Sex)\n" .
                "(int VkId, string FirstName, string LastName)");
        }
    }

    /**
     * @ignore
     */
    private function ____construct1(int $VkId, array/*<string, string>*/ $FirstName, array/*<string, string>*/ $LastName, int $Sex)
    {
        $this->VkId = $VkId;
        $this->Sex = $Sex;
        $this->FirstName = $FirstName;
        $this->LastName = $LastName;

        $this->Correct();
    }

    /**
     * @ignore
     */
    private function ____construct2(int $VkId, string $FirstName, string $LastName)
    {
        $this->VkId = $VkId;
        $this->Sex = UserSex::MALE;
        $this->FirstName = array();
        $this->FirstName[UserNameCases::NOM] = $FirstName;
        $this->LastName[UserNameCases::NOM] = $LastName;

        $this->Correct();
    }

    /**
     * @ignore
     */
    public function __updateData(array/*<string, string>*/ $FirstName, array/*<string, string>*/ $LastName, int $Sex) : void
    {
        if (!$this->IsHuman())
        {
            return;
        }
        $this->FirstName = $FirstName;
        $this->LastName = $LastName;
        $this->Sex = $Sex;

        $this->Correct();
    }

    /**
     * @ignore
     */
    private function Correct() : void
    {
        if ($this->VkId < 1)
        {
            $nameCases = UserRepository::GetNameCases();
            foreach ($nameCases as $nc)
            {
                $this->FirstName[$nc] = $this->FirstName[UserNameCases::NOM];
                $this->LastName[$nc] = $this->LastName[UserNameCases::NOM];
            }
        }
        else
        {
            $arr1 = array();
            $arr2 = array();
            $nameCases = UserRepository::GetNameCases();
            foreach ($nameCases as $nc)
            {
                $arr1[$nc] = "";
                if (isset($this->FirstName[$nc]))
                {
                    $arr1[$nc] = $this->FirstName[$nc];
                }

                $arr2[$nc] = "";
                if (isset($this->LastName[$nc]))
                {
                    $arr2[$nc] = $this->LastName[$nc];
                }
            }
            $this->FirstName = $arr1;
            $this->LastName = $arr2;
        }
    }

    /**
     * Получить идентификатор пользователя
     *
     * @return int Идентификатор пользователя во ВКонтакте
     */
    public function GetVkId() : int
    {
        return $this->VkId;
    }

    /**
     * Получить пол человека
     *
     * @return int Возвращает пол человека. 1 - женский. 2 - мужской. Для сравнения рекомендуется использовать UserSex
     */
    public function GetSex() : int
    {
        return $this->Sex;
    }

    public function HasUnfilledNameCases() : bool
    {
        if (!$this->IsHuman())
        {
            return false;
        }
        $nameCases = UserRepository::GetNameCases();
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
        $nameCases = UserRepository::GetNameCases();
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
     * @return array<string> Массив имени человека во всех падежах
     */
    public function GetFirstNameAsArray() : array
    {
        return $this->FirstName;
    }

    /**
     * Получить имя человека
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
            UserRepository::Get($this->VkId);
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
     * @return array<string> Массив фамилии человека во всех падежах
     */
    public function GetLastNameAsArray() : array
    {
        return $this->LastName;
    }

    /**
     * Получить фамилию человека
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
            UserRepository::Get($this->VkId);
        }
        else if (!isset($this->LastName[$nameCase]) && !$autoQuery)
        {
            return "";
        }
        return $this->LastName[$nameCase];
    }

    /**
     * @return bool TRUE - объектом является живой человек. FALSE - объектом является консоль
     */
    public function IsHuman() : bool
    {
        return $this->GetVkId() > 0;
    }

    /**
     * Получить тэг для упоминания человека в беседе
     *
     * @param string $nameCase Падеж имени
     * @return string Текст упоминания человека
     */
    public function GetMention($nameCase = "nom") : string
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
     * @param string $text Текст сообщения
     * @param array<string> $attachments Список вложений. Вложения указывать в простом формате: <mediatype><owner>_<attachment>_<accesskey>. Например: photo1234_5678
     * @param BotKeyboard|null $keyboard Клавиатура бота. Если не нужно указывать клавиатуру бота, можно просто указать "пустой" экземпляр класса BotKeyboard или указать NULL
     */
    public function SendMessage(string $text, array $attachments, ?BotKeyboard $keyboard) : void
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
            MessageRepository::Send($text, $this, $attachments, $keyboard);
        }
        catch (\Exception $e)
        {
            Bot::GetInstance()->GetLogger()->Error("(User::SendMessage) " . cmm::g("user.sendmessage.error", [$this->GetMention(), $e->getMessage()]));
        }
    }

    /**
     * @return bool Является ли человек админом. Всегда будет TRUE, если объектом пользователя является консоль
     */
    public function IsAdmin() : bool
    {
        if (!$this->IsHuman())
        {
            return true;
        }

        return in_array($this->GetVkId(), Config::Get("admins"));
    }

    /**
     * Отправить сообщение человеку (эквивалент SendMessage(string $text, array<string> $attachments, BotKeyboard $keyboard)
     *
     * @param string $text Текст сообщения. Замечание: в тексте сообщения можно использовать тэги <user> и <fulluser>. Они будут использоваться как тэги упоминания с имени и тэги упоминания с именем и фамилией соответственно
     */
    public function Send(string $text) : void
    {
        $text = str_replace("<user>", $this->GetMention(), $text);
        $text = str_replace("<fulluser>", $this->GetFullMention(), $text);
        try
        {
            MessageRepository::Send($text, $this, [], new BotKeyboard());
        }
        catch (\Exception $e)
        {
            Bot::GetInstance()->GetLogger()->Log("(User::Send) " . cmm::g("user.sendmessage.error", [$this->GetMention(), $e->getMessage()]));
        }
    }
}