<?php
declare(ticks = 1);

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
    private ?Conversation $Conversation;

    /**
     * @ignore
     */
    private ?Message $Message;

    /**
     * @ignore
     */
    public function __construct(string $commandName, array $args, User $sender, ?Message $message, ?Conversation $conversation)
    {
        $this->CommandName = $commandName;
        $this->Arguments = $args;
        $this->Sender = $sender;
        $this->Conversation = $conversation;
        $this->Message = $message;
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
     * Получить объект беседы, в которую команда была введена
     *
     * @return Conversation|null Объект беседы. Всегда будет null, если сообщение было введено в личные сообщения бота либо в консоль
     */
    public function GetConversation() : ?Conversation
    {
        return $this->Conversation;
    }

    /**
     * @return ?Message Исходное сообщение пользователя
     */
    public function GetMessage() : ?Message
    {
        return $this->Message;
    }
}