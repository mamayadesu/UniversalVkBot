<?php

namespace uvb\Handlers;

use uvb\cmm;
use uvb\Events\UnregisteredVkEvent;
use uvb\Main;
use uvb\Plugin\Plugin;
use \Throwable;
use uvb\System\CrashHandler;

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
        {if(!$plugin instanceof Plugin)continue;
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
                cmm::c("exception.unregistered", [$plugin->GetPluginName(), $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()]);
                CrashHandler::Handle($e, $plugin);
                $plugin->DisablePlugin();
            }
        }
    }
}