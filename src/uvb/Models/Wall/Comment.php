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
    private bool $Loaded = false;

    /**
     * @var array<string, Comment>
     * @ignore
     */
    private static array $CommentsCache = [];

    /**
     * @ignore
     */
    private int $Id, $Date, $LoadedDate = 0;

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
    private ?Comment $ReplyToComment = null;

    /**
     * @ignore
     */
    private ?Post $Post;

    /**
     * @var array<Attachment>
     * @ignore
     */
    private array $Attachments = array();

    /**
     * @ignore
     */
    public function __construct(int $Id, ?Entity $Owner, ?Post $Post = null, ?int $Date = null, ?Entity $From = null, ?string $Text = null, ?array $Attachments = null, ?Entity $ReplyTo = null, ?Comment $ReplyToComment = null)
    {
        $this->Id = $Id;
        $this->Owner = $Owner;
        if ($Date !== null) $this->Date = $Date;
        if ($From !== null) $this->From = $From;
        if ($Text !== null) $this->Text = $Text;
        if ($Post !== null) $this->Post = $Post;

        if ($Attachments !== null)
        {
            foreach ($Attachments as $key => $value)
            {
                $this->Attachments[] = AttachmentParser::Parse($value);
            }
        }

        if ($ReplyTo !== null) $this->ReplyTo = $ReplyTo;
        if ($ReplyToComment !== null) $this->ReplyToComment = $ReplyToComment;
    }

    /**
     * @ignore
     */
    public static function Factory(int $Id, ?Entity $Owner, ?Post $Post = null, ?int $Date = null, ?Entity $From = null, ?string $Text = null, ?array $Attachments = null, ?Entity $ReplyTo = null, ?Comment $ReplyToComment = null) : ?Comment
    {
        if (
            $Post === null &&
            $Date === null &&
            $From === null &&
            $Text === null &&
            $Attachments === null &&
            $ReplyTo === null &&
            $ReplyToComment === null
        )
        {
            return self::factory2($Id, $Owner);
        }
        else
        {
            return self::factory1($Id, $Owner, $Post, $Date, $From, $Text, $Attachments, $ReplyTo, $ReplyToComment);
        }
    }

    /**
     * @ignore
     */
    public static function factory1(int $Id, ?Entity $Owner, ?Post $Post, int $Date, ?Entity $From, string $Text, array $Attachments, ?Entity $ReplyTo, ?Comment $ReplyToComment) : ?Comment
    {
        if (isset(self::$CommentsCache["comment" . $Owner->GetVkId() . "_" . $Id]))
        {
            $comment = self::$CommentsCache["comment" . $Owner->GetVkId() . "_" . $Id];
            $comment->Text = $Text;
            $comment->Attachments = [];
            $comment->LoadedDate = time();
            $comment->Loaded = true;
            foreach ($Attachments as $a)
            {
                $attachment = AttachmentParser::Parse($a);
                if ($attachment != null && $attachment->GetMediaType() != "unknown")
                {
                    $comment->Attachments[] = $attachment;
                }
            }
        }
        else
        {
            $comment = new Comment($Id, $Owner, $Post, $Date, $From, $Text, $Attachments, $ReplyTo, $ReplyToComment);
            $comment->Loaded = true;
            $comment->LoadedDate = time();
            if (isset(SystemConfig::Get("groups_to_access_tokens")["club" . (-$Owner->GetVkId())]) && SystemConfig::Get("enable_wall_cache"))
            {
                self::$CommentsCache["comment" . $Owner->GetVkId() . "_" . $Id] = $comment;
            }
        }
        return $comment;
    }

    /**
     * @ignore
     */
    private static function factory2(int $id, ?Entity $owner) : ?Comment
    {
        if (isset(self::$CommentsCache["comment" . $owner->GetVkId() . "_" . $id]))
        {
            return self::$CommentsCache["comment" . $owner->GetVkId() . "_" . $id];
        }

        $comment = new Comment($id, $owner);
        $comment->Loaded = false;
        if (isset(SystemConfig::Get("groups_to_access_tokens")["club" . (-$owner->GetVkId())]) && SystemConfig::Get("enable_wall_cache"))
            self::$CommentsCache["comment" . $owner->GetVkId() . "_" . $id] = $comment;
        return $comment;
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
        $this->LoadComment();
        return $this->Date;
    }

    /**
     * @return Entity|null Автор комментария (пользователь или сообщество)
     */
    public function GetFrom() : ?Entity
    {
        $this->LoadComment();
        return $this->From;
    }

    /**
     * @return string Текст комментария
     */
    public function GetText() : string
    {
        $this->LoadComment();
        return $this->Text;
    }

    /**
     * @return Entity|null Автор комментария, на который был дан ответ (текущий комментарий), если данный комментарий является ответом
     */
    public function GetReplyTo() : ?Entity
    {
        $this->LoadComment();
        return $this->ReplyTo;
    }


    /**
     * @return Comment|null Комментарий, на который был дан ответ, если данный комментарий является ответом
     */
    public function GetReplyToComment() : ?Comment
    {
        $this->LoadComment();
        return $this->ReplyToComment;
    }

    /**
     * @return Attachment[] Вложения
     */
    public function GetAttachments() : array
    {
        $this->LoadComment();
        return $this->Attachments;
    }

    /**
     * @ignore
     */
    public function GetLoadedDate() : int
    {
        return $this->LoadedDate;
    }

    /**
     * Получить объект записи, к которому относится комментарий.
     *
     * @return Post|null
     */
    public function GetPost() : ?Post
    {
        return $this->Post;
    }

    /**
     * @return Comment[]
     * @ignore
     */
    public static function GetCommentsCache() : array
    {
        return self::$CommentsCache;
    }

    /**
     * @ignore
     */
    public function LoadComment() : void
    {
        if ($this->Loaded)
        {
            $this->LoadedDate = time();
            return;
        }


        $owner_id = $this->Owner->GetVkId();

        $url = "https://api.vk.com/method/wall.getComment?v=5.92";
        $url .= "&access_token=" . SystemConfig::Get("main_admin_access_token") . "&";
        $url .= "&owner_id=" . $owner_id;
        $url .= "&comment_id=" . $this->Id;
        $url .= "&extended=1";
        $url .= "&fields=" . User::UserFilters;

        $result = @file_get_contents($url);

        if ($result === false)
        {
            throw new Exception("Failed to load comment with id '" . $this->Id . "'. Connection with VK API failed.");
        }
        else
        {
            $data = json_decode($result, true);
            if (isset($data["error"]))
            {
                throw new Exception("Failed to load comment with id '" . $this->Id . "': " . $data["error"]["error_msg"] . ".");
            }
            else
            {
                $comment_data = $data["response"]["items"][0];
                $this->Owner = $comment_data["owner_id"] > 0 ? User::Get($comment_data["owner_id"]) : Group::Get($comment_data["owner_id"]);
                $this->From = $comment_data["from_id"] > 0 ? User::Get($comment_data["from_id"]) : Group::Get($comment_data["from_id"]);
                $this->Date = $comment_data["date"];
                $this->Text = $comment_data["text"];
                $this->Post = Post::Factory($comment_data["post_id"], $this->Owner);
                if (isset($comment_data["reply_to_user"]))
                {
                    $this->ReplyTo = $comment_data["reply_to_user"] > 0 ? User::Get($comment_data["reply_to_user"]) : Group::Get($comment_data["reply_to_user"]);
                }
                if (isset($comment_data["reply_to_comment"]))
                {
                    $this->ReplyToComment = Comment::Factory($comment_data["reply_to_comment"], $this->Owner);
                }
                $this->Loaded = true;
            }
        }
    }

    /**
     * @ignore
     */
    public static function DeleteFromCache(string $key) : void
    {
        self::$CommentsCache[$key] = null;
        unset(self::$CommentsCache[$key]);
    }

    /**
     * Удалить комментарий
     *
     * @return void
     * @throws Exception Не поддерживается для стен пользователей
     */
    public function Delete() : bool
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
            return false;
        }

        unset(self::$CommentsCache["comment" . $owner_id . "_" . $this->Id]);
        return true;
    }

    /**
     * @ignore
     */
    private static function GetApi() : VkApiWall
    {
        return Bot::GetVkApi()->wall();
    }
}