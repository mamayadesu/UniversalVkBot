<?php

namespace uvb\Repositories;

use IO\Console;
use uvb\Bot;
use uvb\cmm;
use uvb\Config;
use uvb\ConfigResource;
use uvb\ConversationIdsResource;
use uvb\Models\InboxMessage;
use uvb\Models\User;
use uvb\Models\BotKeyboard;
use \VK\Actions\Messages;
use \VK\Actions\Users;
use \VK\Client\VKApiClient;
use \VK\Exceptions\Api\VKApiAccessException;
use VK\Exceptions\Api\VKApiMessagesCantFwdException;
use VK\Exceptions\Api\VKApiMessagesChatBotFeatureException;
use \VK\Exceptions\Api\VKApiMessagesChatNotAdminException;
use VK\Exceptions\Api\VKApiMessagesChatUserNoAccessException;
use \VK\Exceptions\Api\VKApiMessagesChatUserNotInChatException;
use \VK\Exceptions\Api\VKApiMessagesContactNotFoundException;
use VK\Exceptions\Api\VKApiMessagesDenySendException;
use VK\Exceptions\Api\VKApiMessagesKeyboardInvalidException;
use VK\Exceptions\Api\VKApiMessagesPrivacyException;
use VK\Exceptions\Api\VKApiMessagesTooLongForwardsException;
use VK\Exceptions\Api\VKApiMessagesTooLongMessageException;
use VK\Exceptions\Api\VKApiMessagesTooManyPostsException;
use VK\Exceptions\Api\VKApiMessagesUserBlockedException;
use \VK\Exceptions\VKApiException;
use \VK\Exceptions\VKClientException;
use uvb\Rcon\RconResource;
use \Exception;

/**
 * Репозиторий для работы с сообщениями
 * @package uvb\Repositories
 *
 *
 */

class MessageRepository
{

    /**
     * Является ли сообщение личным
     *
     * @param int $peer_id Идентификатор получателя
     * @return bool TRUE - сообщение личное
     */
    public static function IsPrivateMessage(int $peer_id) : bool
    {
        return $peer_id < 2000000000;
    }

    /**
     * @ignore
     */
    private static function GetApi() : Messages
    {
        return (new VKApiClient())->messages();
    }

    /**
     * Удалить сообщение из беседы
     *
     * @param InboxMessage $msg Объект входящего сообщения
     * @param int $convId Идентификатор беседы
     * @return bool TRUE - сообщение удалено. FALSE - произошла ошибка (см. консоль)
     */
    public static function DeleteMessage(InboxMessage $msg, int $convId) : bool
    {
        $api = self::GetApi();
        $config = ConfigResource::GetConfig();
        $cids = ConversationIdsResource::$conversationIds;
        if ($convId < 2000000000)
        {
            $convId = $convId + 2000000000;
        }
        $cid = $cids->Get($convId);
        if ($cid == 0)
        {
            Bot::GetInstance()->GetLogger()->Error("MessageRepository::DeleteMessage: " . cmm::g("messagerepository.deletemessage.err1", [$convId]));
            return false;
        }
        $historyParams = array
        (
            "offset" => 0,
            "count" => 50,
            "peer_id" => $cid,
            "rev" => 0
        );
        try
        {
            $history = $api->getHistory($config["main_admin_access_token"], $historyParams);
        }
        catch (Exception $e)
        {
            Bot::GetInstance()->GetLogger()->Error("MessageRepository::DeleteMessage: Failed to get conversation history. " . $e->getMessage());
            return false;
        }

        $delete = false;
        $deleteParams = array
        (
            "delete_for_all" => 1
        );
        $a = json_encode($msg->GetAttachments());
        $percent = 0;
        foreach ($history["items"] as $item)
        {
            $delete = false;
            $deleteParams["message_ids"] = $item["id"] . "";
            if (isset($item["action"]))
            {
                continue;
            }
            if ($item["from_id"] != $msg->GetFrom()->GetVkId() || $item["date"] != $msg->GetDate() || $item["text"] != $msg->GetText())
            {
                continue;
            }
            similar_text($a, json_encode($item["attachments"]), $percent);
            if ($percent > 80)
            {
                $delete = true;
            }
            if ($delete)
            {
                try
                {
                    $api->delete($config["main_admin_access_token"], $deleteParams);
                }
                catch (Exception $e)
                {
                    Bot::GetInstance()->GetLogger()->Error("MessageRepository::DeleteMessage: Failed to delete message. " . $e->getMessage());
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Отправить сообщение в беседу
     *
     * @param string $message Текст сообщения
     * @param int $conversationId Идентификатор беседы
     * @param array<string> $attachments Список вложений. Массив должен содержать строки в формате <mediatype><owner>_<attachmentid>_<accesskey>
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
    public static function SendToConversation(string $message, int $conversationId, array $attachments) : void
    {
        $random_id = "";

        for ($i = 0; $i <= rand(5, 10); $i++)
        {
            $random_id .= rand(1, 20) . "";
        }

        $params = array
        (
            "peer_id" => $conversationId,
            "message" => $message,
            "random_id" => $random_id
        );
        if (count($attachments) > 0)
        {
            $params["attachment"] = implode(',', $attachments);
        }
        try
        {
            self::GetApi()->send(Config::Get("access_token"), $params);
        }
        catch (VKApiMessagesCantFwdException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesChatBotFeatureException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesChatUserNoAccessException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesContactNotFoundException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesDenySendException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesKeyboardInvalidException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesPrivacyException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesTooLongForwardsException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesTooLongMessageException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesTooManyPostsException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesUserBlockedException $e)
        {
            throw $e;
        }
        catch (VKApiException $e)
        {
            throw $e;
        }
        catch (VKClientException $e)
        {
            throw $e;
        }
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
     * @param string $message Текст сообщения
     * @param User $user Объект пользователя
     * @param array<string> $attachments Список вложений. Массив должен содержать строки в формате <mediatype><owner>_<attachmentid>_<accesskey>
     * @param BotKeyboard|null $keyboard Клавиатура бота. Если не нужно указывать клавиатуру, можно указать NULL
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
    public static function Send(string $message, User $user, array $attachments, ?BotKeyboard $keyboard) : void
    {
        try
        {
            self::Mailing($message, [$user], $attachments, $keyboard);
        }
        catch (VKApiMessagesCantFwdException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesChatBotFeatureException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesChatUserNoAccessException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesContactNotFoundException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesDenySendException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesKeyboardInvalidException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesPrivacyException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesTooLongForwardsException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesTooLongMessageException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesTooManyPostsException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesUserBlockedException $e)
        {
            throw $e;
        }
        catch (VKApiException $e)
        {
            throw $e;
        }
        catch (VKClientException $e)
        {
            throw $e;
        }
    }

    /**
     * Рассылка сообщения пользователям
     *
     * @param string $message Текст сообщения
     * @param array<User> $users Список объектов User
     * @param array<string> $attachments Список вложений. Массив должен содержать строки в формате <mediatype><owner>_<attachmentid>_<accesskey>
     * @param BotKeyboard|null $keyboard Клавиатура бота. Если не нужно указывать клавиатуру, можно указать NULL
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
    public static function Mailing(string $message, array $users, array $attachments, ?BotKeyboard $keyboard) : void
    {
        $vkIds = [];
        $limit = 0;
        $sentToConsole = false;
        $sentToRcon = false;
        $rcon = RconResource::$RconHandler;
        foreach ($users as $user)
        {
            if ($limit >= 100)
            {
                break;
            }
            if (!$user instanceof User)
            {
                continue;
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
            if ($user->GetVkId() < -3000000000 && $user->GetFirstName() == "RCON")
            {
                if ($sentToRcon)
                {
                    continue;
                }
                if ($message != "")
                {
                    $rcon->SetResponse("r" . abs($user->GetVkId() + 3000000000), $message);
                }
                else if ($message == "" && count($attachments) > 0)
                {
                    $rcon->SetResponse("r" . abs($user->GetVkId() + 3000000000), cmm::g("messagerepository.mailing.attachments"));
                }
                else if ($message == "" && count($attachments) == 0 && $keyboard != null && $keyboard->IsKeyboardFilled())
                {
                    $rcon->SetResponse("r" . abs($user->GetVkId() + 3000000000), cmm::g("messagerepository.mailing.keyboard"));
                }
                else
                {
                    $rcon->SetResponse("r" . abs($user->GetVkId() + 3000000000), cmm::g("messagerepository.mailing.emptymessage"));
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
        try
        {
            self::GetApi()->send(Config::Get("access_token"), $params);
        }
        catch (VKApiMessagesCantFwdException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesChatBotFeatureException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesChatUserNoAccessException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesContactNotFoundException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesDenySendException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesKeyboardInvalidException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesPrivacyException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesTooLongForwardsException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesTooLongMessageException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesTooManyPostsException $e)
        {
            throw $e;
        }
        catch (VKApiMessagesUserBlockedException $e)
        {
            throw $e;
        }
        catch (VKApiException $e)
        {
            throw $e;
        }
        catch (VKClientException $e)
        {
            throw $e;
        }
    }
}