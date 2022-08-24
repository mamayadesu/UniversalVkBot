<?php
declare(ticks = 1);

namespace uvb\Models\Buttons;

use uvb\Models\Buttons\Colors\Color;

/**
 * Кнопка VK Apps
 * @package uvb\Models\Buttons
 *
 *
 */

class VKApps extends Button
{
    /**
     * @ignore
     */
    private int $app_id, $owner_id;

    /**
     * @ignore
     */
    private string $hash;

    /**
     * @ignore
     */
    public function __construct(string $label, string $payload, int $app_id, int $owner_id, string $hash)
    {
        parent::__construct($label, $payload, Color::POSITIVE);
        $this->type = "open_app";
        $this->app_id = $app_id;
        $this->owner_id = $owner_id;
        $this->hash = $hash;
    }

    /**
     * Получить идентификатор приложения
     *
     * @return int Идентификатор приложения
     */
    public function GetAppId() : int
    {
        return $this->app_id;
    }

    /**
     * Получить идентификатор владельца приложения
     *
     * @return int Идентификатор владельца приложения
     */
    public function GetOwnerId() : int
    {
        return $this->owner_id;
    }

    public function GetButtonData(): array
    {
        $arr = array
        (
            "action" => array
            (
                "type" => $this->type,
                "app_id" => $this->app_id,
                "owner_id" => $this->owner_id,
                "payload" => $this->payload,
                "label" => $this->label,
                "hash" => $this->hash
            )
        );
        return $arr;
    }
}