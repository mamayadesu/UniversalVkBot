<?php

namespace uvb\Models\Wall;

use uvb\Models\Entity;
use uvb\Models\Group;
use uvb\Models\User;
use uvb\Models\Attachments\Attachment;
use \Exception;
use uvb\Utils\AttachmentParser;

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

//    public function GetComments() : array/*<Comment>*/
//    {
//
//    }

    /**
     * Добавить стас.метод добавления поста
     * Добавить метод удаления поста
     * Добавить метод редактирования поста
     */
}