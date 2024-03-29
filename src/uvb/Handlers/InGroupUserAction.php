<?php
declare(ticks = 1);

namespace uvb\Handlers;

use uvb\cmm;
use uvb\Events\InGroupUserAction\UserJoinGroupEvent;
use uvb\Events\InGroupUserAction\UserLeftGroupEvent;
use uvb\Main;
use uvb\Plugin\Plugin;
use \Throwable;
use uvb\System\CrashHandler;
use uvb\System\SystemConfig;

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
        {if(!$plugin instanceof Plugin)continue;
            if ($event->IsCancelled())
            {
                break;
            }

            if ($plugin->IsEnabledForGroup($event->GetGroup()))
            {
                try
                {
                    $plugin->OnUserJoinGroup($event);
                }
                catch (Throwable $e)
                {
                    cmm::c("exception.userjoingroup", [$plugin->GetPluginName(), $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()]);
                    CrashHandler::Handle($e, $plugin);
                    if (SystemConfig::Get("disable_plugin_on_exception"))
                    {
                        $plugin->DisablePlugin();
                    }
                }
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
        {if(!$plugin instanceof Plugin)continue;
            if ($event->IsCancelled())
            {
                break;
            }
            if ($plugin->IsEnabledForGroup($event->GetGroup()))
            {
                try
                {
                    $plugin->OnUserLeftGroup($event);
                }
                catch (Throwable $e)
                {
                    cmm::c("exception.userleftgroup", [$plugin->GetPluginName(), $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()]);
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