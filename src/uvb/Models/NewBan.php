<?php
declare(ticks = 1);

namespace uvb\Models;

use Exception;
use Throwable;
use uvb\Bot;
use uvb\System\SystemConfig;

class NewBan
{
    /**
     * @var int|null Время и дата окончания блокировки. Укажите NULL, если хотите заблокировать пользователя навсегда. Появилось в API: 1.0
     */
    public ?int $EndDate = null;

    /**
     * @var BanReason Причина блокировки
     */
    public int $Reason = BanReason::Other;

    /**
     * @var string Комментарий к блокировке. Появилось в API: 1.0
     */
    public string $Comment = "";

    /**
     * @var bool Будет ли пользователь видеть сообщение о блокировке. Появилось в API: 1.0
     */
    public bool $CommentVisible = true;

    /**
     * @var Group|null Группа, в которой пользователь будет заблокирован. Появилось в API: 1.0
     */
    public ?Group $Group = null;

    /**
     * @var Entity|null Группа или пользователь, который будет заблокирован. Появилось в API: 1.0
     */
    public ?Entity $Entity = null;

    /**
     * Заблокировать пользователя
     *
     * Появилось в API: 1.0
     *
     * @return void
     * @throws Exception
     */
    public function Execute() : void
    {
        if ($this->Entity === null)
        {
            throw new Exception("Entity is not specified");
        }

        if ($this->Group === null)
        {
            throw new Exception("Group is not specified");
        }

        $groups = Group::GetApi();

        $groups_banParams = array(
            "group_id" => abs($this->Group->GetVkId()),
            "owner_id" => $this->Entity->GetVkId(),
            "reason" => $this->Reason,
            "comment" => $this->Comment,
            "comment_visible" => intval($this->CommentVisible)
        );

        if ($this->EndDate !== null)
        {
            $groups_banParams["end_date"] = $this->EndDate;
        }

        try
        {
            $groups->ban(SystemConfig::Get("main_admin_access_token"), $groups_banParams);
        }
        catch (Throwable $e)
        {
            Bot::GetInstance()->GetLogger()->Critical("Failed to ban '" . $this->Entity->GetVkId() . "' in group '" . $this->Group->GetVkId() . "'. " . $e->getMessage());
        }
    }
}