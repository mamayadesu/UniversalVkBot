<?php

namespace uvb\Models;

use uvb\Models\Attachments\Attachment;
use \Exception;

/**
 * Плагин описывает входящее сообщение
 * @package uvb\Models
 *
 *
 */

class InboxMessage
{
    /**
     * @ignore
     */
    private int $MessageId, $Date, $PeerId;

    /**
     * @ignore
     */
    private User $From;

    /**
     * @ignore
     */
    private string $Text;

    /**
     * @ignore
     */
    private array/*<Attachment>*/ $Attachments;

    /**
     * @ignore
     */
    public function __construct(int $MessageId, int $Date, User $From, string $Text, int $PeerId, array/*<Attachment>*/ $Attachments)
    {
        $this->MessageId = $MessageId;
        $this->Date = $Date;
        $this->From = $From;
        $this->Text = $Text;
        $this->PeerId = $PeerId;

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
     * Получить дату отправки сообщения
     *
     * @return int Дата отправки сообщения в формате Unixtime
     */
    public function GetDate() : int
    {
        return $this->Date;
    }

    /**
     * Получить отправителя сообщения
     *
     * @return User Объект пользователя, отправивший сообщение
     */
    public function GetFrom() : User
    {
        return $this->From;
    }

    /**
     * Получить текст сообщения
     *
     * @return string Текст сообщения
     */
    public function GetText() : string
    {
        return $this->Text;
    }

    /**
     * @return int Идентификатор получателя сообщения
     */
    public function GetPeerId() : int
    {
        return $this->PeerId;
    }

    /**
     * @return int Идентификатор сообщения (на данный момент не работает с текущей версией VK API)
     */
    public function GetMessageId() : int
    {
        return $this->MessageId;
    }

    /**
     * Получить список вложений сообщения
     *
     * @return array<int, Attachment> Список вложений
     */
    public function GetAttachments() : array/*<Attachment>*/
    {
        return $this->Attachments;
    }
}