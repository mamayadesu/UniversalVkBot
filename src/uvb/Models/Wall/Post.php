<?php

namespace uvb\Models\Wall;

use uvb\Bot;
use uvb\Models\Entity;
use uvb\Models\Group;
use uvb\Models\User;
use uvb\Models\Attachments\Attachment;
use \Exception;
use uvb\System\SystemConfig;
use uvb\Utils\AttachmentParser;
use \VK\Actions\Wall as VkApiWall;

final class Post
{
    private int $Id, $Date;
    private string $Text;
    private bool $Ads, $Favorite;

    private ?Entity $From = null, $Owner = null;
    private ?User $CreatedBy = null;

    /**
     * @var array<Attachment>
     * @ignore
     */
    private array/*<Attachment>*/ $Attachments;

    public function __construct(int $id, int $date, string $text, bool $ads, bool $favorite, int $fromId, int $ownerId, int $createdBy, array $attachments)
    {
        $this->Id = $id;
        $this->Date = $date;
        $this->Text = $text;
        $this->Ads = $ads;
        $this->Favorite = $favorite;
        if ($fromId < 0)
        {
            try
            {
                $this->From = Group::Get($fromId);
            }
            catch (Exception $e)
            {
            }
        }
        else
        {
            try
            {
                $this->From = User::Get($fromId);
            }
            catch (Exception $e)
            {
            }
        }
        if ($ownerId < 0)
        {
            try
            {
                $this->Owner = Group::Get($ownerId);
            }
            catch (Exception $e)
            {
            }
        }
        else
        {
            try
            {
                $this->Owner = User::Get($ownerId);
            }
            catch (Exception $e)
            {
            }
        }

        try
        {
            $this->CreatedBy = User::Get($createdBy);
        }
        catch (Exception $e)
        {
        }

        $a = null;
        foreach ($attachments as $a)
        {
            $attachment = AttachmentParser::Parse($a);
            if ($attachment != null && $attachment->GetMediaType() != "unknown")
            {
                $this->Attachments[] = $attachment;
            }
        }
    }

    public function GetId() : int
    {
        return $this->Id;
    }

    public function GetDate() : int
    {
        return $this->Date;
    }

    public function GetText() : string
    {
        return $this->Text;
    }

    public function IsAdvertisement() : bool
    {
        return $this->Ads;
    }

    public function IsFavorite() : bool
    {
        return $this->Favorite;
    }

    public function GetPublisher() : ?Entity
    {
        return $this->From;
    }

    public function GetWallOwner() : ?Entity
    {
        return $this->Owner;
    }

    public function GetCreator() : ?User
    {
        return $this->CreatedBy;
    }

    public function GetComments() : array/*<Comment>*/
    {
        /** @var array<Comment> $result */$result = [];
        $wall = self::GetApi();

        if ($this->Owner instanceof User)
            throw new Exception("Not supported for users");

        $owner_id = -(abs($this->Owner->GetVkId()));
        $wall_getCommentsParams = array(
            "owner_id" => $owner_id,
            "post_id" => $this->Id,
            "offset" => 0,
            "count" => 100,
            "preview_length" => 0,
            "extended" => true,
            "fields" => User::UserFilters
        );
        try
        {
            $response = $wall->getComments(SystemConfig::Get("access_token"), $wall_getCommentsParams);
        }
        catch (Exception $e)
        {
            Bot::GetInstance()->GetLogger()->Error("(wall" . $owner_id . "_" . $this->Id .")->GetComments(): " . $e->getMessage());
            return $result;
        }


        return $result;
    }

    private static function GetApi() : VkApiWall
    {
        return Bot::GetVkApi()->wall();
    }

    /**
     * Добавить стас.метод добавления поста
     * Добавить метод удаления поста
     * Добавить метод редактирования поста
     */
}