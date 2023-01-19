<?php
declare(ticks = 1);

namespace uvb\Models\Wall;

use uvb\Bot;
use uvb\Models\Entity;
use uvb\Models\Group;
use uvb\Models\User;
use uvb\Models\Attachments\Attachment;
use \Exception;
use uvb\System\SystemConfig;
use uvb\Utils\AttachmentParser;
use uvb\Utils\EntitiesParser;
use \VK\Actions\Wall as VkApiWall;

final class Post
{
    /**
     * @ignore
     */
    private int $Id, $Date;

    /**
     * @ignore
     */
    private string $Text;

    /**
     * @ignore
     */
    private bool $Ads, $Favorite;

    /**
     * @ignore
     */
    private ?Entity $From = null, $Owner = null;

    /**
     * @ignore
     */
    private ?User $CreatedBy = null;

    /**
     * @var array<Attachment>
     * @ignore
     */
    private array/*<Attachment>*/ $Attachments = [];

    /**
     * @ignore
     */
    public function __construct(int $id, int $date, string $text, bool $ads, bool $favorite, ?Entity $from, ?Entity $owner, ?User $createdBy, array $attachments)
    {
        $this->Id = $id;
        $this->Date = $date;
        $this->Text = $text;
        $this->Ads = $ads;
        $this->Favorite = $favorite;
        $this->From = $from;
        $this->Owner = $owner;
        $this->CreatedBy = $createdBy;

        foreach ($attachments as $a)
        {
            $attachment = AttachmentParser::Parse($a);
            if ($attachment != null && $attachment->GetMediaType() != "unknown")
            {
                $this->Attachments[] = $attachment;
            }
        }
    }

    /**
     * @return int Идентификатор записи
     */
    public function GetId() : int
    {
        return $this->Id;
    }

    /**
     * @return int Дата публикации в формате Timestamp
     */
    public function GetDate() : int
    {
        return $this->Date;
    }

    /**
     * @return string Текст записи
     */
    public function GetText() : string
    {
        return $this->Text;
    }

    /**
     * @return bool Является ли рекламой
     */
    public function IsAdvertisement() : bool
    {
        return $this->Ads;
    }

    public function IsFavorite() : bool
    {
        return $this->Favorite;
    }

    /**
     * @return Entity|null От чьего имени опубликована запись
     */
    public function GetPublisher() : ?Entity
    {
        return $this->From;
    }

    /**
     * @return Entity|null Владелец стены
     */
    public function GetWallOwner() : ?Entity
    {
        return $this->Owner;
    }

    /**
     * @return User|null Автор поста
     */
    public function GetCreator() : ?User
    {
        return $this->CreatedBy;
    }

    /**
     * Загружает комментарии к посту
     *
     * @param int $threadItemsCount Максимальное количество дочерних комментариев
     * @param int $offset Сдвиг для загрузки комментариев
     * @param int $count Максимальное количество комментариев за итерацию
     * @param bool $allComments Загрузить ВСЕ комментарии
     * @return Comment[] Комментарии к посту
     */
    public function GetComments(int $threadItemsCount = 1, int $offset = 0, int $count = 100, bool $allComments = true) : array/*<Comment>*/
    {
        /** @var array<Comment> $result */$result = [];
        $wall = self::GetApi();

        if ($this->Owner instanceof User)
            throw new Exception("Not supported for users");

        $owner_id = -(abs($this->Owner->GetVkId()));
        $wall_getCommentsParams = array(
            "owner_id" => $owner_id,
            "post_id" => $this->Id,
            "offset" => $offset,
            "count" => $count,
            "preview_length" => 0,
            "extended" => true,
            "fields" => User::UserFilters,
            "thread_items_count" => $threadItemsCount
        );
        $tempEntities = [];
        $oid = $this->Owner !== null ? $this->Owner->GetVkId() . "" : "NULL";
        do
        {
            try
            {
                $comments = $wall->getComments(SystemConfig::Get("main_admin_access_token"), $wall_getCommentsParams);
            }
            catch (Exception $e)
            {
                Bot::GetInstance()->GetLogger()->Error("Post::GetComments: Failed to get comments of post " . $this->Id . " of " . ($this->Owner instanceof Group ? "group " : "user ") . $oid . ". " . $e->getMessage());
                throw $e;
            }

            $tempEntities = EntitiesParser::Parse($comments, $tempEntities);
            foreach ($comments["items"] as $item)
            {
                $result[] = self::ParseComment($tempEntities, $item);
            }
            if ($count < 0)
            {
                $wall_getCommentsParams["offset"] += 100;
            }
            else
            {
                break;
            }
        }
        while ($allComments && count($comments["items"]) > 0);
        return $result;
    }

    /**
     * @ignore
     */
    private static function GetApi() : VkApiWall
    {
        return Bot::GetVkApi()->wall();
    }

    /**
     * @ignore
     */
    private static function ParseComment(array $tempEntities, array $item) : Comment
    {
        $childComments = [];
        if (isset($item["thread"]) && isset($item["thread"]["items"]))
        {
            foreach ($item["thread"]["items"] as $subitem)
            {
                $childComments[] = self::ParseComment($tempEntities, $subitem);
            }
        }
        $replyTo = null;
        if (isset($item["reply_to_user"]))
        {
            $replyTo = $item["reply_to_user"] < 0 ? Group::Get($item["reply_to_user"]) : User::Get($item["reply_to_user"]);
        }
        return new Comment(
            $item["id"],
            $item["owner_id"],
            $item["date"],
            $tempEntities[$item["from_id"]] ?? null,
            $item["text"],
            $item["attachments"] ?? [],
            $replyTo,
            $childComments
        );
    }

    /**
     * @return Attachment[] Вложения
     */
    public function GetAttachments() : array/*<Attachment>*/
    {
        return $this->Attachments;
    }

    /**
     * Удалить запись
     *
     * @return void
     * @throws Exception
     */
    public function Delete() : void
    {
        $wall = self::GetApi();

        if ($this->Owner instanceof User)
            throw new Exception("Not supported for users");

        $owner_id = -(abs($this->Owner->GetVkId()));

        $wall_deleteParams = array(
            "owner_id" => $owner_id,
            "post_id" => $this->Id
        );

        try
        {
            $wall->delete(SystemConfig::Get("main_admin_access_token"), $wall_deleteParams);
        }
        catch (Exception $e)
        {
            Bot::GetInstance()->GetLogger()->Error("(wall" . $owner_id . "_" . $this->Id .")->Delete(): " . $e->getMessage());
        }
    }
}