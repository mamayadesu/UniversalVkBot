<?php
declare(ticks = 1);

namespace uvb\Events\Wall;

use uvb\Events\Event;
use uvb\Models\Group;
use uvb\Models\Wall\Post;
use Exception;

class NewPostEvent extends Event
{
    /**
     * @ignore
     */
    private Post $post;

    public function __construct(Group $group, Post $post)
    {
        $this->post = $post;
        $this->isCancellable = true;
        parent::__construct($group);
    }

    /**
     * Возвращает объект записи
     *
     * @return Post
     */
    public function GetPost() : Post
    {
        return $this->post;
    }

    /**
     * Отменяет событие новой записи на стене сообщества (удаляет запись).
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

        $this->cancelled = $this->post->Delete();
    }
}