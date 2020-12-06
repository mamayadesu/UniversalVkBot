<?php

namespace uvb\Handlers;

use uvb\cmm;
use uvb\Events\UnregisteredVkEvent;
use uvb\Main;
use uvb\Plugin\PluginBase;
use \Throwable;

/**
 * @ignore
 */

class Unregistered
{
    private Main $main;

    public function __construct(Main $main)
    {
        $this->main = $main;
    }

    public function OnUnregistered(UnregisteredVkEvent $event)
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
                $plugin->OnUnregistered($event);
            }
            catch (Throwable $e)
            {
                cmm::c("exception.unregistered", [$plugin->GetPluginName(), $e->getMessage()]);
            }
        }
    }
}