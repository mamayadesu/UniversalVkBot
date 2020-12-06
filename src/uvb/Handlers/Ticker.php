<?php

namespace uvb\Handlers;

use Application\Application;
use \Throwable;
use uvb\cmm;
use uvb\Main;
use uvb\Plugin\PluginBase;
use uvb\Services\UserCache;

/**
 * @ignore
 */

class Ticker
{
    private Main $main;
    private UserCache $userCache;

    public function __construct(Main $main)
    {
        $this->main = $main;
        $this->userCache = UserCache::GetInstance();
    }

    public function OnTick() : void
    {
        $this->main->UpdateTitle();
        $plugins = $this->main->pluginManager->GetPlugins();
        foreach ($plugins as $plugin)
        {if(!$plugin instanceof PluginBase)continue;
            try
            {
                $plugin->OnTick();
            }
            catch (Throwable $e)
            {
                cmm::c("exception.ontick", [$plugin->GetPluginName(), $e->getMessage()]);
                $plugin->DisablePlugin();
            }
        }
    }
}