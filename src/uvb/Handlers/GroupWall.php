<?php
declare(ticks = 1);

namespace uvb\Handlers;

use Throwable;
use uvb\cmm;
use uvb\Events\Wall\NewCommentEvent;
use uvb\Events\Wall\NewPostEvent;
use uvb\Main;
use uvb\Plugin\Plugin;
use uvb\System\CrashHandler;

/**
 * @ignore
 */
class GroupWall
{
    private Main $main;

    public function __construct(Main $main)
    {
        $this->main = $main;
    }

    public function OnNewPost(NewPostEvent $event) : void
    {
        $plugins = $this->main->pluginManager->GetPlugins();
        foreach ($plugins as $plugin)
        {if (!$plugin instanceof Plugin) continue;
            if ($event->IsCancelled())
            {
                break;
            }

            if ($plugin->IsEnabledForGroup($event->GetGroup()))
            {
                try
                {
                    $plugin->OnNewPost($event);
                }
                catch (Throwable $e)
                {
                    cmm::c("exception.newpost", [$plugin->GetPluginName(), $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()]);
                    CrashHandler::Handle($e, $plugin);
                    $plugin->DisablePlugin();
                }
            }
        }
    }

    public function OnNewComment(NewCommentEvent $event) : void
    {
        $plugins = $this->main->pluginManager->GetPlugins();
        foreach ($plugins as $plugin)
        {if (!$plugin instanceof Plugin) continue;
            if ($event->IsCancelled())
            {
                break;
            }

            if ($plugin->IsEnabledForGroup($event->GetGroup()))
            {
                try
                {
                    $plugin->OnNewComment($event);
                }
                catch (Throwable $e)
                {
                    cmm::c("exception.newcomment", [$plugin->GetPluginName(), $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()]);
                    CrashHandler::Handle($e, $plugin);
                    $plugin->DisablePlugin();
                }
            }
        }
    }
}