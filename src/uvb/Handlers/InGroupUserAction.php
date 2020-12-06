<?php

namespace uvb\Handlers;

use uvb\cmm;
use uvb\Events\InGroupUserAction\UserJoinGroupEvent;
use uvb\Events\InGroupUserAction\UserLeftGroupEvent;
use uvb\Main;
use uvb\Plugin\PluginBase;
use \Throwable;

/**
 * @ignore
 */

class InGroupUserAction
{
    private Main $main;
    private int $ignoreKickForUser = 0;

    public function __construct(Main $main)
    {
        $this->main = $main;
    }

    public function OnUserJoinGroup(UserJoinGroupEvent $event) : void
    {
        $plugins = $this->main->pluginManager->GetPlugins();
        foreach ($plugins as $plugin)
        {if(!$plugin instanceof PluginBase)continue;
            if ($event->IsCancelled())
            {
                break;
            }
            try
            {
                $plugin->OnUserJoinGroup($event);
            }
            catch (Throwable $e)
            {
                cmm::c("exception.conversationcommand", [$plugin->GetPluginName(), $e->getMessage()]);
            }
        }
        if ($event->IsCancelled())
        {
            $this->ignoreKickForUser = $event->GetUser()->GetVkId();
        }
    }

    public function OnUserLeftGroup(UserLeftGroupEvent $event) : void
    {
        if ($this->ignoreKickForUser == $event->GetUser()->GetVkId())
        {
            $this->ignoreKickForUser = 0;
            return;
        }
        $plugins = $this->main->pluginManager->GetPlugins();
        foreach ($plugins as $plugin)
        {if(!$plugin instanceof PluginBase)continue;
            if ($event->IsCancelled())
            {
                break;
            }
            try
            {
                $plugin->OnUserLeftGroup($event);
            }
            catch (Throwable $e)
            {
                cmm::c("exception.conversationcommand", [$plugin->GetPluginName(), $e->getMessage()]);
            }
        }
    }
}