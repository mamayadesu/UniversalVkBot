<?php

namespace uvb\Models;

class NewBan
{
    /**
     * @var int|null Время и дата окончания блокировки. Укажите NULL, если хотите заблокировать пользователя навсегда
     */
    public ?int $EndDate = null;

    /**
     * @var BanReason Причина блокировки
     */
    public int $Reason = BanReason::Other;

    /**
     * @var string Комментарий к блокировке
     */
    public string $Comment = "";

    /**
     * @var bool Будет ли пользователь видеть сообщение о блокировке
     */
    public bool $CommentVisible = true;
}