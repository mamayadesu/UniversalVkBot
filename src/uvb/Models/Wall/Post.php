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

/**
 * Модель, описывающая запись на стене сообщества
 * Для уменьшения количества нагрузки на сеть, запись поста кэшируется на один час
 * Если в течение одного часа к внутреннему объекту записи на стене не было обращений, этот объект удаляется из кэша
 * Учтите, что если объект поста был удалён из кэша, он становится неактуальным. Это может повлиять на работу ваших плагинов
 * Проверить актуальность объекта можно через метод этого класса `IsObjectActual`
 */

final class Post
{
    /**
     * @ignore
     */
    private bool $Loaded = false;

    /**
     * @ignore
     */
    private int $LoadedDate = 0;

    /**
     * @ignore
     * @var array<string, Post>
     */
    private static array $PostsCache = array();

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
    private bool $Ads;

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
    private array $Attachments = [];

    /**
     * @ignore
     */
    public function __construct(int $id, ?Entity $owner, ?int $date = null, ?string $text = null, ?bool $ads = null, ?Entity $from = null, ?User $createdBy = null, ?array $attachments = null)
    {
        $this->Id = $id;
        $this->Owner = $owner;

        if ($date !== null) $this->Date = $date;

        if ($text !== null) $this->Text = $text;

        if ($ads !== null) $this->Ads = $ads;

        if ($from !== null) $this->From = $from;

        if ($createdBy !== null) $this->CreatedBy = $createdBy;

        if ($attachments !== null)
        {
            foreach ($attachments as $a)
            {
                $attachment = AttachmentParser::Parse($a);
                if ($attachment != null && $attachment->GetMediaType() != "unknown")
                {
                    $this->Attachments[] = $attachment;
                }
            }
        }
    }

    /**
     * @ignore
     */
    public static function Factory(int $id, ?Entity $owner, ?int $date = null, ?string $text = null, ?bool $ads = null, ?Entity $from = null, ?User $createdBy = null, ?array $attachments = null) : ?Post
    {
        if ($date === null &&
            $text === null &&
            $ads === null &&
            $from === null &&
            $createdBy === null &&
            $attachments === null
        )
        {
            return self::factory2($id, $owner);
        }
        else
        {
            return self::factory1($id, $owner, $date, $text, $ads, $from, $createdBy, $attachments);
        }
    }

    /**
     * @ignore
     */
    private static function factory1(int $id, ?Entity $owner, int $date, string $text, bool $ads, ?Entity $from, ?User $createdBy, array $attachments) : ?Post
    {
        if (isset(self::$PostsCache["wall" . $owner->GetVkId() . "_" . $id]))
        {
            $post = self::$PostsCache["wall" . $owner->GetVkId() . "_" . $id];
            $post->Text = $text;
            $post->Ads = $ads;
            $post->Attachments = [];
            $post->Loaded = true;
            $post->LoadedDate = time();
            foreach ($attachments as $a)
            {
                $attachment = AttachmentParser::Parse($a);
                if ($attachment != null && $attachment->GetMediaType() != "unknown")
                {
                    $post->Attachments[] = $attachment;
                }
            }
        }
        else
        {
            $post = new Post($id, $owner, $date, $text, $ads, $from, $createdBy, $attachments);
            $post->Loaded = true;
            $post->LoadedDate = time();
            if (isset(SystemConfig::Get("groups_to_access_tokens")["club" . (-$owner->GetVkId())]) && SystemConfig::Get("enable_wall_cache"))
            {
                self::$PostsCache["wall" . $owner->GetVkId() . "_" . $id] = $post;
            }
        }
        return $post;
    }

    /**
     * @ignore
     */
    private static function factory2(int $id, ?Entity $owner) : ?Post
    {
        if (isset(self::$PostsCache["wall" . $owner->GetVkId() . "_" . $id]))
        {
            return self::$PostsCache["wall" . $owner->GetVkId() . "_" . $id];
        }

        $post = new Post($id, $owner);
        $post->Loaded = false;
        if (isset(SystemConfig::Get("groups_to_access_tokens")["club" . (-$owner->GetVkId())]) && SystemConfig::Get("enable_wall_cache"))
            self::$PostsCache["wall" . $owner->GetVkId() . "_" . $id] = $post;
        return $post;
    }

    /**
     * Получить кэш записей со стены.
     *
     * @return Post[]
     */
    public static function GetPostsCache() : array
    {
        return self::$PostsCache;
    }

    /**
     * @return int Идентификатор записи
     */
    public function GetId() : int
    {
        $this->LoadPost();
        return $this->Id;
    }

    /**
     * @return int Дата публикации в формате Timestamp
     */
    public function GetDate() : int
    {
        $this->LoadPost();
        return $this->Date;
    }

    /**
     * @return string Текст записи
     */
    public function GetText() : string
    {
        $this->LoadPost();
        return $this->Text;
    }

    /**
     * @return bool Является ли рекламой
     */
    public function IsAdvertisement() : bool
    {
        $this->LoadPost();
        return $this->Ads;
    }

    /**
     * @return Entity|null От чьего имени опубликована запись
     */
    public function GetPublisher() : ?Entity
    {
        $this->LoadPost();
        return $this->From;
    }

    /**
     * @return Entity|null Владелец стены
     */
    public function GetWallOwner() : ?Entity
    {
        $this->LoadPost();
        return $this->Owner;
    }

    /**
     * @return User|null Автор поста
     */
    public function GetCreator() : ?User
    {
        $this->LoadPost();
        return $this->CreatedBy;
    }

    /**
     * Гибрид GetCreator() и GetFrom(). Если GetCreator() возвращает null, данный метод вернёт то же самое, что и GetFrom()
     *
     * @return User|null Автор поста
     */
    public function GetAuthor() : ?User
    {
        $this->LoadPost();
        if ($this->CreatedBy !== null)
        {
            return $this->CreatedBy;
        }
        else if ($this->From instanceof User)
        {
            return $this->From;
        }
        else
        {
            return null;
        }
    }

    /**
     * @ignore
     */
    public function GetLoadedDate() : int
    {
        return $this->LoadedDate;
    }

    /**
     * @ignore
     */
    public static function DeleteFromCache(string $key) : void
    {
        unset(self::$PostsCache[$key]);
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
    public function GetComments(int $threadItemsCount = 1, int $offset = 0, int $count = 100, bool $allComments = true) : array
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
        $replyToComment = null;
        $owner = $item["owner_id"] > 0 ? User::Get($item["owner_id"]) : Group::Get($item["owner_id"]);
        if (isset($item["reply_to_comment"]))
        {
            $replyToComment = Comment::Factory($item["reply_to_comment"], $owner);
        }
        return Comment::Factory(
            $item["id"],
            $owner,
            self::Factory($item["post_id"], ($item["owner_id"] > 0 ? User::Get($item["owner_id"]) : Group::Get($item["owner_id"]))),
            $item["date"],
            $tempEntities[$item["from_id"]] ?? null,
            $item["text"],
            $item["attachments"] ?? [],
            $replyTo,
            $replyToComment,
            $childComments
        );
    }

    /**
     * @return Attachment[] Вложения
     */
    public function GetAttachments() : array
    {
        $this->LoadPost();
        return $this->Attachments;
    }

    /**
     * Возвращает true/false в зависимости от того, является ли данный объект актуальным для UniversalVkBot
     *
     * @return bool
     */
    public function IsObjectActual() : bool
    {
        if (!isset(self::$PostsCache["wall" . $this->Owner->GetVkId() . "_" . $this->Id]))
            return false;

        return $this === self::$PostsCache["wall" . $this->Owner->GetVkId() . "_" . $this->Id];
    }

    /**
     * @ignore
     */
    private function LoadPost() : void
    {
        if ($this->Loaded)
        {
            $this->LoadedDate = time();
            return;
        }

        $wall = self::GetApi();

        $owner_id = $this->Owner->GetVkId();

        $wall_getByIdParams = array(
            "posts" => $owner_id . "_" . $this->Id,
            "extended" => true,
            "copy_history_depth" => 1,
            "fields" => User::UserFilters
        );

        try
        {
            $response = $wall->getById(SystemConfig::Get("main_admin_access_token"), $wall_getByIdParams);
        }
        catch (Exception $e)
        {
            throw new Exception("(wall" . $owner_id . "_" . $this->Id .")->LoadPost(): " . $e->getMessage());
        }

        if (!isset($response["items"][0]))
        {
            throw new Exception("(wall" . $owner_id . "_" . $this->Id .")->LoadPost(): Post doesn't exist");
        }

        $tempEntities = EntitiesParser::Parse($response);
        $this->Date = $response["items"][0]["date"];
        $this->Text = $response["items"][0]["text"];
        $this->Ads = (bool)$response["items"][0]["marked_as_ads"];
        $this->From = $tempEntities[$response["items"][0]["from_id"]] ?? null;
        $this->CreatedBy = isset($response["items"][0]["created_by"]) ? $tempEntities[$response["items"][0]["created_by"]] : null;

        foreach ($response["items"][0]["attachments"] as $a)
        {
            $attachment = AttachmentParser::Parse($a);
            if ($attachment != null && $attachment->GetMediaType() != "unknown")
            {
                $this->Attachments[] = $attachment;
            }
        }

        $this->Loaded = true;
        $this->LoadedDate = time();
    }

    /**
     * Удалить запись
     *
     * @return bool
     * @throws Exception
     */
    public function Delete() : bool
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
            return false;
        }
        unset(self::$PostsCache["wall" . $owner_id . "_" . $this->Id]);
        return true;
    }
}