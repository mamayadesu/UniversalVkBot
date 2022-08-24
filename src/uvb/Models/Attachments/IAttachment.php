<?php
declare(ticks = 1);

namespace uvb\Models\Attachments;

use uvb\Models\Entity;

interface IAttachment
{

    /*
     * Дата и время в Unixtime
     */
    public function GetDate() : int;

    /*
     * ID вложения
     */
    public function GetId() : int;

    /*
     * ID владельца вложения
     */
    public function GetOwnerId() : int;

    /*
     * Владелец
     */
    public function GetOwner() : ?Entity;

    /*
     * Ключ доступа ко вложению
     */
    public function GetAccessKey() : string;

    /*
     * Тип вложения
     */
    public function GetMediaType() : string;

    /*
     * Ссылка на вложение в формате <type><owner_id>_<id>_<?access_key>
     * Например photo1234567_890_qwertyas
     */
    public function GetFormatted() : string;
}