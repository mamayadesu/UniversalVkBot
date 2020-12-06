<?php

namespace uvb\Models\Attachments;

use uvb\Models\User;

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
    public function GetOwner() : ?User;

    /*
     * Алиас для GetOwner
     */
    public function GetUser() : ?User;

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