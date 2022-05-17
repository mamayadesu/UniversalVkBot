<?php

namespace uvb\Models;

/**
 * Данный класс описывает введённую команду
 * @package uvb\Models
 *
 *
 */

final class Command
{
    /**
     * @ignore
     */
    private string $CommandName;

    /**
     * @ignore
     */
    private array $Arguments;

    /**
     * @ignore
     */
    private User $Sender;

    /**
     * @ignore
     */
    private int $ConversationId;

    /**
     * @ignore
     */
    public function __construct(string $commandName, array $args, User $sender, int $conversationId)
    {
        $this->CommandName = $commandName;
        $this->Arguments = $args;
        $this->Sender = $sender;
        $this->ConversationId = ($conversationId > 2000000000 ? $conversationId - 2000000000 : $conversationId);
    }

    /**
     * @return string Название команды
     */
    public function GetName() : string
    {
        return $this->CommandName;
    }

    /**
     * @return array<string> Параметры, переданные в команду
     */
    public function GetArguments() : array
    {
        return $this->Arguments;
    }

    /**
     * @return User Объект пользователя, который ввёл команду
     */
    public function GetUser() : User
    {
        return $this->Sender;
    }

    /**
     * Получить идентификатор беседы, в которую команда была введена
     *
     * @return int Идентификатор беседы. Всегда будет 0, если сообщение было введено в личные сообщения бота либо в консоль
     */
    public function GetConversationId() : int
    {
        return $this->ConversationId;
    }

    /**
     * Получить идентификатор беседы + 2000000000, в которую команда была введена
     *
     * @return int Идентификатор беседы + 2000000000. Всегда будет 0, если сообщение было введено в личные сообщения бота либо в консоль
     */
    public function GetConversationId2() : int
    {
        if ($this->ConversationId == 0)
        {
            return 0;
        }

        return $this->ConversationId + 2000000000;
    }
}