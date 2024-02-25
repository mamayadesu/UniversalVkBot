<?php
declare(ticks = 1);

namespace uvb\Models;

use uvb\ConversationIdsResource;
use \Exception;
use uvb\Services\UserCache;
use uvb\System\SystemConfig;

class Conversation
{
    /**
     * @var array<int, Conversation>
     * @ignore
     */
    private static array $conversations = array();

    /**
     * @ignore
     */
    private int $id, $updated = 0;

    /**
     * @ignore
     */
    private string $chatName;

    /**
     * @var User[]
     * @ignore
     */
    private array $users = [];

    /**
     * @ignore
     */
    private Group $group;

    /**
     * @ignore
     */
    public function __construct(int $conversationId, Group $group)
    {
        $this->id = $conversationId;
        $this->group = $group;
        self::$conversations[$conversationId] = $this;
    }

    /**
     * Возвращает объект беседы
     *
     * Появилось в API: 1.0
     *
     * @param int $conversationId Идентификатор беседы (от лица бота/сообщества)
     * @param Group $group Объект группы
     * @return Conversation
     */
    public static function Get(int $conversationId, Group $group) : Conversation
    {
        if ($conversationId < 2000000000)
        {
            $conversationId += 2000000000;
        }
        return self::$conversations[$conversationId] ?? new Conversation($conversationId, $group);
    }

    /**
     * @ignore
     */
    private function UpdateCache() : void
    {
        if (($this->updated + 300) >= time())
        {
            return;
        }
        $this->updated = time();
        $idForBot = $this->id;
        $idForAdmin = ConversationIdsResource::$conversationIds->GetByBot($idForBot);

        if ($idForAdmin == 0)
        {
            throw new Exception("Conversation ID for admin of this chat is not specified");
        }

        $messages_getChatParams = array(
            "chat_id" => $idForAdmin - 2000000000,
            "fields" => User::UserFilters,
            "name_case" => "nom"
        );

        $response = Message::GetApi()->getChat(SystemConfig::Get("main_admin_access_token"), $messages_getChatParams);

        $this->chatName = $response["title"];

        $userCache = UserCache::GetInstance();
        /** @var User[] $users */
        $users = [];
        foreach ($response["users"] as $arr_user)
        {
            if (!$userCache->HasUser($arr_user["id"]))
            {
                $user = User::ParseVkProfile($arr_user);
                $userCache->Add($user);
            }
            else
            {
                if ($userCache->NeedToUpdate($arr_user["id"]))
                {
                    $user = $userCache->Get($arr_user["id"]);
                    $user->__updateData(array("nom" => $arr_user["first_name"]), array("nom" => $arr_user["last_name"]),
                        ($arr_user["sex"] ?? UserSex::MALE),
                        ($arr_user["bdate"] ?? ""),
                        (isset($arr_user["city"]) ? $arr_user["city"]["title"] : ""),
                        (isset($arr_user["county"]) ? $arr_user["county"]["title"] : ""),
                        ($arr_user["domain"] ?? ""),
                        ($arr_user["status"] ?? ""));
                    $userCache->Add($user);
                }
                else
                {
                    $user = User::Get($arr_user["id"]);
                }
            }
            $users[] = $user;
        }

        $this->users = $users;
    }

    /**
     * Возвращает название беседы
     *
     * Появилось в API: 1.0
     *
     * @return string
     * @throws Exception
     */
    public function GetName() : string
    {
        $this->UpdateCache();
        return $this->chatName;
    }

    /**
     * Возвращает список пользователей беседы
     *
     * Появилось в API: 1.0
     *
     * @return User[]
     * @throws Exception
     */
    public function GetUsers() : array
    {
        $this->UpdateCache();
        return $this->users;
    }

    /**
     * Возвращает ID беседы от лица бота/сообщества (по умолчанию ID беседы + 2000000000)
     *
     * Появилось в API: 1.0
     *
     * @param bool $normalize Нормализовать идентификатор беседы (вычитает 2000000000)
     * @return int
     */
    public function GetId(bool $normalize = true) : int
    {
        return $normalize ? $this->id - 2000000000 : $this->id;
    }

    /**
     * Отправить сообщение в беседу
     *
     * Появилось в API: 1.0
     *
     * @param string $message Текст сообщения
     * @param array<string> $attachments Список вложений. Массив должен содержать строки в формате <mediatype><owner>_<attachmentid>_<accesskey>
     * @param Group|null $by_group От лица какого сообщества отправить сообщение
     * @param Geolocation|null $geolocation Геолокация
     * @return void
     * @throws \VK\Exceptions\Api\VKApiMessagesCantFwdException
     * @throws \VK\Exceptions\Api\VKApiMessagesChatBotFeatureException
     * @throws \VK\Exceptions\Api\VKApiMessagesChatUserNoAccessException
     * @throws \VK\Exceptions\Api\VKApiMessagesContactNotFoundException
     * @throws \VK\Exceptions\Api\VKApiMessagesDenySendException
     * @throws \VK\Exceptions\Api\VKApiMessagesKeyboardInvalidException
     * @throws \VK\Exceptions\Api\VKApiMessagesPrivacyException
     * @throws \VK\Exceptions\Api\VKApiMessagesTooLongForwardsException
     * @throws \VK\Exceptions\Api\VKApiMessagesTooLongMessageException
     * @throws \VK\Exceptions\Api\VKApiMessagesTooManyPostsException
     * @throws \VK\Exceptions\Api\VKApiMessagesUserBlockedException
     * @throws \VK\Exceptions\VKApiException
     * @throws \VK\Exceptions\VKClientException
     */
    public function SendMessage(string $message, array $attachments = [], ?Group $by_group = null, ?Geolocation $geolocation = null) : void
    {
        Message::SendToConversation($message, $this, $attachments, $by_group, $geolocation);
    }

    /**
     * Исключить пользователя из беседы
     *
     * Появилось в API: 1.0
     *
     * @param User $user
     * @return void
     */
    public function KickUser(User $user) : void
    {
        $message_removeChatUserParams = array(
            "chat_id" => $this->id - 2000000000,
            "user_id" => $user->GetVkId()
        );

        try
        {
            Message::GetApi()->removeChatUser($this->group->GetAccessToken(), $message_removeChatUserParams);
        }
        catch (Exception $e)
        {
            return;
        }

        foreach ($this->users as $index => $usr)
        {
            if ($usr->GetVkId() == $user->GetVkId())
            {
                unset($this->users[$user->GetVkId()]);
            }
        }
        $this->users = array_values($this->users);
    }
}