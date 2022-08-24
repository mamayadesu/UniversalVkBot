<?php
declare(ticks = 1);

namespace uvb\Models\Wall;

use uvb\Models\Attachments\Attachment;
use uvb\Models\Entity;

/**
 * Модель, описывающая комментарий к посту
 */
final class Comment
{
    /**
     * @ignore
     */
    private int $Id, $Date;

    /**
     * @ignore
     */
    private ?Entity $From = null;

    /**
     * @ignore
     */
    private string $Text;

    /**
     * @ignore
     */
    private ?Entity $ReplyTo = null;

    /**
     * @ignore
     */
    private ?Comment $ReplyToComment = null;

    /**
     * @var array<Attachment>
     * @ignore
     */
    private array/*<Attachment>*/ $Attachments = array();

    public function __construct(int $Id, int $Date, ?Entity $From, string $Text, array $Attachments, ?Entity $ReplyTo, ?Comment $ReplyToComment)
    {
        $this->Id = $Id;
        $this->Date = $Date;
        $this->From = $From;
        $this->Text = $Text;
        foreach ($Attachments as $key => $value)
        {if(!$value instanceof Attachment)continue;
            $this->Attachments[] = $value;
        }

        $this->ReplyTo = $ReplyTo;
        $this->ReplyToComment = $ReplyToComment;
    }

    /**
     * @return int Идентификатор комментария
     */
    public function GetId() : int
    {
        return $this->Id;
    }

    /**
     * @return int Дата добавления комментария в формате unixtime
     */
    public function GetDate() : int
    {
        return $this->Date;
    }

    /**
     * @return Entity|null Автор комментария (пользователь или сообщество)
     */
    public function GetFrom() : ?Entity
    {
        return $this->From;
    }

    /**
     * @return string Текст комментария
     */
    public function GetText() : string
    {
        return $this->Text;
    }

    /**
     * @return Entity|null Автор комментария, на который был дан ответ (текущий комментарий), если данный комментарий является ответом
     */
    public function GetReplyTo() : ?Entity
    {
        return $this->ReplyTo;
    }

    /**
     * @return Comment|null Комментарий, на который был дан ответ (текущий комментарий), если данный комментарий является ответом
     */
    public function GetReplyToComment() : ?Comment
    {
        return $this->ReplyToComment;
    }

    /**
     * @return array|Attachment[] Вложения
     */
    public function GetAttachments() : array/*<Attachment>*/
    {
        return $this->Attachments;
    }
}