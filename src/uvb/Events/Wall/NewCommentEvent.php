<?php
declare(ticks = 1);

namespace uvb\Events\Wall;

use Exception;
use uvb\Events\Event;
use uvb\Models\Group;
use uvb\Models\Wall\Comment;

class NewCommentEvent extends Event
{
    /**
     * @ignore
     */
    private Comment $comment;

    public function __construct(Group $group, Comment $comment)
    {
        $this->comment = $comment;
        $this->isCancellable = true;
        parent::__construct($group);
    }

    /**
     * Возвращает объект комментария
     *
     * Появилось в API: 1.0
     *
     * @return Comment
     */
    public function GetComment() : Comment
    {
        return $this->comment;
    }

    /**
     * Отменяет событие новой записи на стене сообщества (удаляет запись).
     *
     * Появилось в API: 1.0
     *
     * @return void
     * @throws Exception
     */
    public function SetCancelled() : void
    {
        if ($this->cancelled)
        {
            return;
        }

        $this->cancelled = $this->comment->Delete();
    }
}