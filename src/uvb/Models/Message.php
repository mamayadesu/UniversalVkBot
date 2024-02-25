<?php
declare(ticks = 1);

namespace uvb\Models;

use uvb\Bot;
use uvb\cmm;
use uvb\Models\Photo\ImageContext;
use uvb\System\SystemConfig;
use uvb\System\SystemConfigResource;
use uvb\Models\Attachments\Attachment;
use \Exception;
use VK\Actions\Messages;
use VK\Client\VKApiClient;
use VK\Exceptions\Api\VKApiMessagesCantFwdException;
use VK\Exceptions\Api\VKApiMessagesChatBotFeatureException;
use VK\Exceptions\Api\VKApiMessagesChatUserNoAccessException;
use VK\Exceptions\Api\VKApiMessagesContactNotFoundException;
use VK\Exceptions\Api\VKApiMessagesDenySendException;
use VK\Exceptions\Api\VKApiMessagesKeyboardInvalidException;
use VK\Exceptions\Api\VKApiMessagesPrivacyException;
use VK\Exceptions\Api\VKApiMessagesTooLongForwardsException;
use VK\Exceptions\Api\VKApiMessagesTooLongMessageException;
use VK\Exceptions\Api\VKApiMessagesTooManyPostsException;
use VK\Exceptions\Api\VKApiMessagesUserBlockedException;
use VK\Exceptions\VKApiException;
use VK\Exceptions\VKClientException;

/**
 * Плагин описывает входящее сообщение
 * @package uvb\Models
 *
 *
 */

final class Message
{
    /**
     * @ignore
     */
    private int $MessageId, $Date, $PeerId;

    /**
     * @ignore
     */
    private ?Conversation $Conversation;

    /**
     * @ignore
     */
    private User $From;

    /**
     * @ignore
     */
    private Group $Group;

    /**
     * @ignore
     */
    private string $Text;

    /**
     * @var array<Attachment>
     * @ignore
     */
    private array/*<Attachment>*/ $Attachments;

    /**
     * @ignore
     */
    private ?Geolocation $Geolocation = null;

    /**
     * @ignore
     */
    public function __construct(int $MessageId, int $Date, User $From, Group $group, string $Text, int $PeerId, array/*<Attachment>*/ $Attachments, ?Conversation $Conversation, Geolocation $geolocation = null)
    {
        $this->MessageId = $MessageId;
        $this->Conversation = $Conversation;
        $this->Date = $Date;
        $this->From = $From;
        $this->Text = $Text;
        $this->PeerId = $PeerId;
        $this->Geolocation = $geolocation;
        $this->Group = $group;

        $newArr = [];
        for ($i = 0; $i < count($Attachments); $i++)
        {
            if (!isset($Attachments[$i]))
            {
                throw new Exception("uvb\\Models\\InboxMessage: Attachments must be a list, not a dictionary");
            }
            if (!$Attachments[$i] instanceof Attachment)
            {
                throw new Exception("uvb\\Models\\InboxMessage: Item on index " . $i . " is not attachment");
            }

            $newArr[] = $Attachments[$i];
        }
        $this->Attachments = $newArr;
    }
    /**
     * @ignore
     */
    public static function GetApi() : Messages
    {
        return Bot::GetVkApi()->messages();
    }

    /**
     * Появилось в API: 1.0
     *
     * @return int Дата отправки сообщения в формате Unixtime
     */
    public function GetDate() : int
    {
        return $this->Date;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return User Объект пользователя, отправивший сообщение
     */
    public function GetFrom() : User
    {
        return $this->From;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return Group Группа, в личные сообщения которой написали
     */
    public function GetGroup() : Group
    {
        return $this->Group;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return Geolocation|null Геолокация (если она отправлена)
     */
    public function GetGeolocation() : ?Geolocation
    {
        return $this->Geolocation;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return string Текст сообщения
     */
    public function GetText() : string
    {
        return $this->Text;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return int Идентификатор получателя сообщения
     */
    public function GetPeerId() : int
    {
        return $this->PeerId;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return ?Conversation Идентификатор сообщения в диалоге
     */
    public function GetConversation() : ?Conversation
    {
        return $this->Conversation;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return int Идентификатор сообщения (на данный момент не работает с текущей версией VK API)
     */
    public function GetMessageId() : int
    {
        return $this->MessageId;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return Attachment[] Список вложений
     */
    public function GetAttachments() : array
    {
        return $this->Attachments;
    }

    /**
     * Является ли сообщение личным
     *
     * Появилось в API: 1.0
     *
     * @return bool
     */
    public function IsPrivate() : bool
    {
        return $this->PeerId < 2000000000;
    }

    /**
     * Удалить сообщение из беседы
     *
     * Появилось в API: 1.0
     *
     * @return bool TRUE - сообщение удалено. FALSE - произошла ошибка (см. консоль)
     */
    public function Delete(?Group $group_deleter = null) : bool
    {
        if ($this->IsPrivate())
        {
            throw new Exception("Private messages cannot be deleted");
        }
        if ($group_deleter === null)
        {
            $group_deleter = $this->Group;
        }
        $api = self::GetApi();
        $deleteParams = array(
            "delete_for_all" => true,
            "conversation_message_ids" => $this->MessageId,
            "peer_id" => $this->GetPeerId()
        );

        try
        {
            $api->delete($group_deleter->GetAccessToken(), $deleteParams);
        }
        catch (Exception $e)
        {
            Bot::GetInstance()->GetLogger()->Error("Message::DeleteMessage: Failed to delete message. " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
            return false;
        }
        return true;
    }

    /**
     * Загружает изображение для отправки пользователю в личные сообщения
     *
     * Появилось в API: 1.0
     *
     * @param ImageContext[] $contexts
     * @param User $user
     * @param Group $fromGroup
     * @return string[] Список загруженных фотографий в формате строк, например `photo-12345_67890`
     * @throws VKApiException
     * @throws VKApiMessagesDenySendException
     * @throws VKClientException
     */
    public static function UploadImages(array $contexts, User $user, Group $fromGroup) : array
    {
        if (count($contexts) > 10)
        {
            throw new Exception("You cannot upload more than 10 attachments!");
        }
        foreach ($contexts as $index => $context)
        {
            if (!$context instanceof ImageContext)
            {
                throw new Exception("ImageContext expected, " . (gettype($context) == "object" ? "object of '" . get_class($context) . "'" : gettype($context)) . " given at index " . $index);
            }

            if ($context->IsDestroyed())
            {
                throw new Exception("ImageContext is destroyed at index " . $index);
            }
        }
        $photos_getMessagesUploadServerParams = [
            "peer_id" => $user->GetVkId()
        ];

        $photos = Bot::GetVkApi()->photos();
        $getMessagesUploadServerResponse = $photos->getMessagesUploadServer($fromGroup->GetAccessToken(), $photos_getMessagesUploadServerParams);

        if (!isset($getMessagesUploadServerResponse["upload_url"]))
        {
            throw new Exception("Upload URL wasn't received");
        }
        $final_result = [];
        foreach ($contexts as $index => $context)
        {
            $delimiter = "-------------" . uniqid();

            $fileFields = array(
                "photo." . explode('/', $context->GetMime())[1] => array(
                    "type" => $context->GetMime(),
                    "content" => $context->GetBinary()
                )
            );

            $data = "";
            foreach ($fileFields as $name => $file)
            {
                $data .= "--" . $delimiter . "\r\n";
                $data .= "Content-Disposition: form-data; name=\"photo\";" .
                    " filename=\"" . $name . "\"" . "\r\n";
                $data .= "Content-Type: " . $file["type"] . "\r\n";
                $data .= "\r\n";
                $data .= $file["content"] . "\r\n";
            }

            $data .= "--" . $delimiter . "--\r\n";
            $ch = curl_init($getMessagesUploadServerResponse["upload_url"]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER , array(
                "Content-Type: multipart/form-data; boundary=" . $delimiter,
                "Content-Length: " . strlen($data)));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = @json_decode(curl_exec($ch), true);
            if ($result === null)
            {
                throw new Exception("UploadImages: Wrong response from server at context with index " . $index);
            }
            if ($result["photo"] == "")
            {
                throw new Exception("UploadImages: Image wasn't uploaded. At context with index " . $index);
            }

            $photos_saveMessagesPhotoParams = array(
                "server" => $result["server"],
                "hash" => $result["hash"],
                "photo" => $result["photo"]
            );

            $response = $photos->saveMessagesPhoto($fromGroup->GetAccessToken(), $photos_saveMessagesPhotoParams);

            $final_result[] = "photo" . $response[0]["owner_id"] . "_" . $response[0]["id"];
        }

        return $final_result;
    }

    /**
     * Ответить пользователю на сообщение. Если это сообщение в беседе, оно также будет отправлено в беседу
     *
     * Появилось в API: 1.0
     *
     * @param string $message Текст сообщения
     * @param array $attachments Список вложений. Вложения указывать в простом формате: <mediatype><owner>_<attachment>_<accesskey>. Например: photo1234_5678
     * @param BotKeyboard|null $keyboard Клавиатура бота (не нужна, если это сообщение в беседе)
     * @param Geolocation|null $geolocation Геолокация
     * @return void
     * @throws VKApiException
     * @throws VKApiMessagesCantFwdException
     * @throws VKApiMessagesChatBotFeatureException
     * @throws VKApiMessagesChatUserNoAccessException
     * @throws VKApiMessagesContactNotFoundException
     * @throws VKApiMessagesDenySendException
     * @throws VKApiMessagesKeyboardInvalidException
     * @throws VKApiMessagesPrivacyException
     * @throws VKApiMessagesTooLongForwardsException
     * @throws VKApiMessagesTooLongMessageException
     * @throws VKApiMessagesTooManyPostsException
     * @throws VKApiMessagesUserBlockedException
     * @throws VKClientException
     */
    public function Reply(string $message, array $attachments = [], ?BotKeyboard $keyboard = null, ?Geolocation $geolocation = null) : void
    {
        if ($this->IsPrivate())
        {
            $this->GetFrom()->SendMessage($message, $attachments, $this->Group, $keyboard, $geolocation);
        }
        else
        {
            Message::SendToConversation($message, Conversation::Get($this->PeerId, $this->Group), $attachments, $this->Group, $geolocation);
        }
    }

    /**
     * Отправить сообщение в беседу
     *
     * Появилось в API: 1.0
     *
     * @param string $message Текст сообщения
     * @param Conversation $conversation Объект беседы
     * @param array<string> $attachments Список вложений. Массив должен содержать строки в формате <mediatype><owner>_<attachmentid>_<accesskey>
     * @param Group|null $by_group От лица какого сообщества отправить сообщение
     * @param Geolocation|null $geolocation Геолокация
     * @throws VKApiException
     * @throws VKApiMessagesCantFwdException
     * @throws VKApiMessagesChatBotFeatureException
     * @throws VKApiMessagesChatUserNoAccessException
     * @throws VKApiMessagesContactNotFoundException
     * @throws VKApiMessagesDenySendException
     * @throws VKApiMessagesKeyboardInvalidException
     * @throws VKApiMessagesPrivacyException
     * @throws VKApiMessagesTooLongForwardsException
     * @throws VKApiMessagesTooLongMessageException
     * @throws VKApiMessagesTooManyPostsException
     * @throws VKApiMessagesUserBlockedException
     * @throws VKClientException
     */
    public static function SendToConversation(string $message, Conversation $conversation, array $attachments = [], ?Group $by_group = null, ?Geolocation $geolocation = null) : void
    {
        if ($by_group === null)
        {
            $by_group = Bot::GetInstance()->GetDefaultGroup();
        }
        $random_id = "";

        for ($i = 0; $i <= rand(5, 10); $i++)
        {
            $random_id .= rand(1, 20) . "";
        }

        $params = array
        (
            "peer_id" => $conversation->GetId(false),
            "message" => $message,
            "random_id" => $random_id
        );
        if ($geolocation != null && $geolocation->Longitude != null && $geolocation->Latitude != null)
        {
            $params["lat"] = $geolocation->Latitude;
            $params["long"] = $geolocation->Longitude;
        }
        if (count($attachments) > 0)
        {
            $params["attachment"] = implode(',', $attachments);
        }
        self::GetApi()->send($by_group->GetAccessToken(), $params);
    }

    /**
     * @ignore
     */
    private static function ConsoleOutput(string $text) : void
    {
        Bot::GetInstance()->__gcl()->Log($text);
    }

    /**
     * Отправить личное сообщение пользователю от имени сообщества
     *
     * Появилось в API: 1.0
     *
     * @param string $message Текст сообщения
     * @param User $user Объект пользователя
     * @param array<string> $attachments Список вложений. Массив должен содержать строки в формате <mediatype><owner>_<attachmentid>_<accesskey>
     * @param BotKeyboard|null $keyboard Клавиатура бота. Если не нужно указывать клавиатуру, можно указать NULL
     * @param Geolocation|null $geolocation Геолокация
     * @throws VKApiException
     * @throws VKApiMessagesCantFwdException
     * @throws VKApiMessagesChatBotFeatureException
     * @throws VKApiMessagesChatUserNoAccessException
     * @throws VKApiMessagesContactNotFoundException
     * @throws VKApiMessagesDenySendException
     * @throws VKApiMessagesKeyboardInvalidException
     * @throws VKApiMessagesPrivacyException
     * @throws VKApiMessagesTooLongForwardsException
     * @throws VKApiMessagesTooLongMessageException
     * @throws VKApiMessagesTooManyPostsException
     * @throws VKApiMessagesUserBlockedException
     * @throws VKClientException
     */
    public static function Send(string $message, User $user, ?Group $by_group = null, array $attachments = [], ?BotKeyboard $keyboard = null, ?Geolocation $geolocation = null) : void
    {
        self::Mailing($message, [$user], $by_group, $attachments, $keyboard, $geolocation);
    }

    /**
     * Рассылка сообщения пользователям
     *
     * Появилось в API: 1.0
     *
     * @param string $message Текст сообщения
     * @param array<User> $users Список объектов User
     * @param array<string> $attachments Список вложений. Массив должен содержать строки в формате <mediatype><owner>_<attachmentid>_<accesskey>
     * @param BotKeyboard|null $keyboard Клавиатура бота. Если не нужно указывать клавиатуру, можно указать NULL
     * @param Geolocation|null $geolocation Геолокация
     * @throws VKApiException
     * @throws VKApiMessagesCantFwdException
     * @throws VKApiMessagesChatBotFeatureException
     * @throws VKApiMessagesChatUserNoAccessException
     * @throws VKApiMessagesContactNotFoundException
     * @throws VKApiMessagesDenySendException
     * @throws VKApiMessagesKeyboardInvalidException
     * @throws VKApiMessagesPrivacyException
     * @throws VKApiMessagesTooLongForwardsException
     * @throws VKApiMessagesTooLongMessageException
     * @throws VKApiMessagesTooManyPostsException
     * @throws VKApiMessagesUserBlockedException
     * @throws VKClientException
     */
    public static function Mailing(string $message, array $users, ?Group $by_group = null, array $attachments = [], ?BotKeyboard $keyboard = null, ?Geolocation $geolocation = null) : void
    {
        if ($by_group === null)
        {
            $by_group = Bot::GetInstance()->GetDefaultGroup();
        }
        $vkIds = [];
        $limit = 0;
        $sentToConsole = false;
        foreach ($users as $user)
        {if(!$user instanceof User) continue;
            if ($limit >= 100)
            {
                break;
            }
            if ($user->GetVkId() == 0 && $user->GetFirstName() == "CONSOLE")
            {
                if ($sentToConsole)
                {
                    continue;
                }
                if ($message != "")
                {
                    self::ConsoleOutput($message);
                }
                else if ($message == "" && count($attachments) > 0)
                {
                    self::ConsoleOutput(cmm::g("messagerepository.mailing.attachments"));
                }
                else if ($message == "" && count($attachments) == 0 && $keyboard != null && $keyboard->IsKeyboardFilled())
                {
                    self::ConsoleOutput(cmm::g("messagerepository.mailing.keyboard"));
                }
                else
                {
                    self::ConsoleOutput(cmm::g("messagerepository.mailing.emptymessage"));
                }
                $sentToConsole = true;
                continue;
            }
            if (in_array($user->GetVkId(), $vkIds))
            {
                continue;
            }
            $vkIds[] = $user->GetVkId();
            $limit++;
        }
        $random_id = "";

        for ($i = 0; $i <= rand(5, 10); $i++)
        {
            $random_id .= rand(1, 20) . "";
        }
        if (count($vkIds) == 1)
        {
            $params = array
            (
                "user_id" => $vkIds[0] . "",
                "peer_id" => $vkIds[0] . "",
                "message" => $message,
                "random_id" => $random_id
            );
        }
        else if (count($vkIds) > 1)
        {
            $params = array
            (
                "user_ids" => implode(',', $vkIds),
                "message" => $message,
                "random_id" => $random_id
            );
        }
        else
        {
            return;
        }
        if (count($attachments) > 0)
        {
            $params["attachment"] = implode(',', $attachments);
        }
        if ($keyboard != null && $keyboard->IsKeyboardFilled())
        {
            $params["keyboard"] = $keyboard->ConvertToJson();
        }
        if ($geolocation != null && $geolocation->Longitude != null && $geolocation->Latitude != null)
        {
            $params["lat"] = $geolocation->Latitude;
            $params["long"] = $geolocation->Longitude;
        }
        self::GetApi()->send($by_group->GetAccessToken(), $params);
    }
}