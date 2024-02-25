<?php
declare(ticks = 1);

namespace uvb\Handlers;

use uvb\cmm;
use uvb\Events\ServerRequestEvent;
use uvb\Events\UnregisteredVkEvent;
use uvb\Main;
use uvb\Plugin\Plugin;
use \Throwable;
use uvb\System\CrashHandler;
use uvb\System\SystemConfig;

/**
 * @ignore
 */

class Common
{
    private Main $main;

    public function __construct(Main $main)
    {
        $this->main = $main;
    }

    public function OnUnregistered(UnregisteredVkEvent $event) : void
    {
        $plugins = $this->main->pluginManager->GetPlugins();
        foreach ($plugins as $plugin)
        {if(!$plugin instanceof Plugin)continue;
            if ($event->IsCancelled())
            {
                break;
            }

            if ($plugin->IsEnabledForGroup($event->GetGroup()))
            {
                try
                {
                    $plugin->OnUnregistered($event);
                }
                catch (Throwable $e)
                {
                    cmm::c("exception.common", [$plugin->GetPluginName(), $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()]);
                    CrashHandler::Handle($e, $plugin);
                    if (SystemConfig::Get("disable_plugin_on_exception"))
                    {
                        $plugin->DisablePlugin();
                    }
                }
            }
        }
    }

    public function OnServerRequest(ServerRequestEvent $event) : void
    {
        $plugins = $this->main->pluginManager->GetPlugins();
        foreach ($plugins as $plugin)
        {if(!$plugin instanceof Plugin)continue;
            if ($event->IsCancelled())
            {
                break;
            }

            if ($event->GetGroup() === null || $plugin->IsEnabledForGroup($event->GetGroup()))
            {
                try
                {
                    $plugin->OnServerRequest($event);
                }
                catch (Throwable $e)
                {
                    cmm::c("exception.common", [$plugin->GetPluginName(), $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()]);
                    CrashHandler::Handle($e, $plugin);
                    if (SystemConfig::Get("disable_plugin_on_exception"))
                    {
                        $plugin->DisablePlugin();
                    }
                }
            }
        }
    }
}