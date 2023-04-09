<?php
declare(ticks = 1);

namespace uvb\Models\Wall;

use Exception;
use uvb\Bot;
use uvb\Models\Attachments\Attachment;
use uvb\Models\Entity;
use uvb\Models\Group;
use uvb\Models\User;
use uvb\System\SystemConfig;
use uvb\Utils\AttachmentParser;
use VK\Actions\Wall as VkApiWall;

/**
 * Модель, описывающая комментарий к посту
 */
final class Comment
{
    /**
     * @ignore
     */
    private int $Id, $Date, $OwnerId;

    /**
     * @ignore
     */
    private ?Entity $From = null, $Owner = null;

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
    private array $ChildComments = [];

    /**
     * @var array<Attachment>
     * @ignore
     */
    private array $Attachments = array();

    /**
     * @ignore
     */
    public function __construct(int $Id, int $OwnerId, int $Date, ?Entity $From, string $Text, array $Attachments, ?Entity $ReplyTo, array $ChildComments)
    {
        $this->Id = $Id;
        $this->Date = $Date;
        $this->From = $From;
        $this->Text = $Text;
        $this->OwnerId = $OwnerId;
        $this->Owner = $this->OwnerId < 0 ? Group::Get($this->OwnerId) : User::Get($this->OwnerId);
        foreach ($Attachments as $key => $value)
        {
            $this->Attachments[] = AttachmentParser::Parse($value);
        }

        $this->ReplyTo = $ReplyTo;
        $this->ChildComments = $ChildComments;
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
     * @return Comment[] Возвращает список дочерних комментариев
     */
    public function GetChildComments() : array
    {
        return $this->ChildComments;
    }

    /**
     * @return Attachment[] Вложения
     */
    public function GetAttachments() : array
    {
        return $this->Attachments;
    }

    /**
     * Удалить комментарий
     *
     * @return void
     * @throws Exception Не поддерживается для стен пользователей
     */
    public function Delete() : void
    {
        $wall = self::GetApi();

        if ($this->Owner instanceof User)
            throw new Exception("Not supported for users");

        $owner_id = -(abs($this->Owner->GetVkId()));

        $wall_deleteCommentParams = array(
            "owner_id" => $owner_id,
            "comment_id" => $this->Id
        );

        try
        {
            $wall->deleteComment(SystemConfig::Get("main_admin_access_token"), $wall_deleteCommentParams);
        }
        catch (Exception $e)
        {
            Bot::GetInstance()->GetLogger()->Error("(comment" . $owner_id . "_" . $this->Id .")->Delete(): " . $e->getMessage());
        }
    }

    /**
     * @ignore
     */
    private static function GetApi() : VkApiWall
    {
        return Bot::GetVkApi()->wall();
    }
}