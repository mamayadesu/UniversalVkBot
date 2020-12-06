<?php

namespace uvb\Handlers;

use uvb\cmm;
use uvb\Events\CommandPreProcessEvent;
use uvb\Events\Messages\BotJoinEvent;
use uvb\Events\Messages\UserAddEvent;
use uvb\Events\Messages\UserJoinEvent;
use uvb\Events\Messages\UserKickEvent;
use uvb\Events\Messages\UserLeftEvent;
use uvb\Main;
use uvb\Events\Messages\NewConversationMessageEvent;
use uvb\Events\Messages\NewPrivateMessageEvent;
use uvb\Plugin\PluginBase;
use \VK\Client\VKApiClient;
use \VK\Actions\Messages;
use \Throwable;

/**
 * @ignore
 */

class NewMessage
{
    private Messages $msgapi;
    private Main $main;
    public bool $waitForMailing = false;

    public function __construct(Main $main)
    {
        $this->msgapi = (new VKApiClient())->messages();
        $this->main = $main;
    }

    public function OnNewPrivateMessage(NewPrivateMessageEvent $event) : void
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
                $plugin->OnNewPrivateMessage($event);
            }
            catch (Throwable $e)
            {
                cmm::c("exception.newmessage", [$plugin->GetPluginName(), $e->getMessage()]);
            }
        }
    }

    public function OnNewConversationMessage(NewConversationMessageEvent $event) : void
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
                $plugin->OnNewConversationMessage($event);
            }
            catch (Throwable $e)
            {
                cmm::c("exception.newconversationmessage", [$plugin->GetPluginName(), $e->getMessage()]);
            }
        }
    }

    public function OnUserAdd(UserAddEvent $event) : void
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
                $plugin->OnUserAdd($event);
            }
            catch (Throwable $e)
            {
                cmm::c("exception.useradd", [$plugin->GetPluginName(), $e->getMessage()]);
            }
        }
    }

    public function OnUserJoin(UserJoinEvent $event)
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
                $plugin->OnUserJoin($event);
            }
            catch (Throwable $e)
            {
                cmm::c("exception.userjoin", [$plugin->GetPluginName(), $e->getMessage()]);
            }
        }
    }

    public function OnUserLeft(UserLeftEvent $event) : void
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
                $plugin->OnUserLeft($event);
            }
            catch (Throwable $e)
            {
                cmm::c("exception.userleft", [$plugin->GetPluginName(), $e->getMessage()]);
            }
        }
    }

    public function OnUserKick(UserKickEvent $event) : void
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
                $plugin->OnUserKick($event);
            }
            catch (Throwable $e)
            {
                cmm::c("exception.userkick", [$plugin->GetPluginName(), $e->getMessage()]);
            }
        }
    }

    public function OnBotJoin(BotJoinEvent $event) : void
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
                $plugin->OnBotJoin($event);
            }
            catch (Throwable $e)
            {
                cmm::c("exception.botjoin", [$plugin->GetPluginName(), $e->getMessage()]);
            }
        }
    }

    public function OnCommandPreProcess(CommandPreProcessEvent $event) : void
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
                $plugin->OnCommandPreProcess($event);
            }
            catch (Throwable $e)
            {
                cmm::c("exception.commandpreprocess", [$plugin->GetPluginName(), $e->getMessage()]);
            }
        }
    }
}