<?php
declare(ticks = 1);

namespace uvb\Models\Attachments\Sticker;

use Exception;
use uvb\Bot;
use uvb\Models\Attachments\Attachment;
use uvb\Models\Attachments\AttachmentTypes;

class Sticker extends Attachment
{
    /**
     * @ignore
     */
    private int $productId, $stickerId;

    /**
     * @var array<StickerImage>
     * @ignore
     */
    private array $images;

    /**
     * @var array<StickerImage>
     * @ignore
     */
    private array $imagesWithBackground;

    /**
     * @ignore
     */
    private string $animationUrl = "";

    /**
     * @ignore
     */
    public function __construct(array $sourceData)
    {
        parent::__construct($sourceData, AttachmentTypes::STICKER);
        if (isset($sourceData["animation_url"]) && is_string($sourceData["animation_url"]))
        {
            $this->animationUrl = $sourceData["animation_url"];
        }
        if (!isset($sourceData["product_id"]) || !is_int($sourceData["product_id"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Sticker\\Sticker: Invalid source data. Product ID must be an integer, " . gettype($sourceData["product_id"] . " given"));
        }
        if (!isset($sourceData["sticker_id"]) || !is_int($sourceData["sticker_id"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Sticker\\Sticker: Invalid source data. Sticker ID must be an integer, " . gettype($sourceData["sticker_id"] . " given"));
        }
        if (!isset($sourceData["images"]) || !is_array($sourceData["images"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Sticker\\Sticker: Invalid source data. Images must be an array, " . gettype($sourceData["images"] . " given"));
        }
        if (!isset($sourceData["images_with_background"]) || !is_array($sourceData["images_with_background"]))
        {
            throw new Exception("\\uvb\\Models\\Attachments\\Sticker\\Sticker: Invalid source data. Images with background must be an array, " . gettype($sourceData["images_with_background"] . " given"));
        }

        $image = null;
        foreach ($sourceData["images"] as $sourceImage)
        {
            try
            {
                $image = new StickerImage($sourceImage);
            }
            catch (Exception $e)
            {
                throw $e;
            }
            $this->images[] = $image;
        }

        foreach ($sourceData["images_with_background"] as $sourceImage)
        {
            try
            {
                $image = new StickerImage($sourceImage);
            }
            catch (Exception $e)
            {
                throw $e;
            }
            $this->imagesWithBackground[] = $image;
        }
        $this->productId = $sourceData["product_id"];
        $this->stickerId = $sourceData["sticker_id"];
    }

    /**
     * @return int Идентификатор набора стикеров
     */
    public function GetProductId() : int
    {
        return $this->productId;
    }

    /**
     * @return int Идентификатор стикера
     */
    public function GetStickerId() : int
    {
        return $this->stickerId;
    }

    /**
     * Получить ссылки на файлы стикеров
     *
     * @param bool $withBackground Будут ли возвращены ссылки на изображения с фоном
     * @return array<StickerImage> Возвращает список объектов StickerImage, содержащие в себе информацию о файле изображения стикера
     */
    public function GetImages(bool $withBackground = false) : array
    {
        if ($withBackground)
        {
            return $this->imagesWithBackground;
        }
        else
        {
            return $this->images;
        }
    }

    /**
     * Получить ссылку на код анимации стикера
     *
     * @return string Ссылка на код анимации стикера
     */
    public function GetAnimationUrl() : string
    {
        return $this->animationUrl;
    }

    public function GetFormatted() : string
    {
        return "";
    }
}