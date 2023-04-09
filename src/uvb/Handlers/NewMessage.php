<?php
declare(ticks = 1);

namespace uvb\Handlers;

use uvb\Bot;
use uvb\cmm;
use uvb\Events\CommandPreProcessEvent;
use uvb\Events\Messages\BotJoinEvent;
use uvb\Events\Messages\BotLeftEvent;
use uvb\Events\Messages\UserAddEvent;
use uvb\Events\Messages\UserJoinEvent;
use uvb\Events\Messages\UserKickEvent;
use uvb\Events\Messages\UserLeftEvent;
use uvb\Main;
use uvb\Events\Messages\NewConversationMessageEvent;
use uvb\Events\Messages\NewPrivateMessageEvent;
use uvb\Plugin\Plugin;
use uvb\System\CrashHandler;
use \VK\Client\VKApiClient;
use \VK\Actions\Messages;
use \Throwable;

/**
 * @ignore
 */

class NewMessage
{
    private Main $main;

    public function __construct(Main $main)
    {
        $this->main = $main;
    }

    public function OnNewPrivateMessage(NewPrivateMessageEvent $event) : void
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
                    $plugin->OnNewPrivateMessage($event);
                }
                catch (Throwable $e)
                {
                    cmm::c("exception.newmessage", [$plugin->GetPluginName(), $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()]);
                    CrashHandler::Handle($e, $plugin);
                    $plugin->DisablePlugin();
                }
            }
        }
    }

    public function OnNewConversationMessage(NewConversationMessageEvent $event) : void
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
                    $plugin->OnNewConversationMessage($event);
                }
                catch (Throwable $e)
                {
                    cmm::c("exception.newconversationmessage", [$plugin->GetPluginName(), $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()]);
                    CrashHandler::Handle($e, $plugin);
                    $plugin->DisablePlugin();
                }
            }
        }
    }

    public function OnUserAdd(UserAddEvent $event) : void
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
                    $plugin->OnUserAdd($event);
                }
                catch (Throwable $e)
                {
                    cmm::c("exception.useradd", [$plugin->GetPluginName(), $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()]);
                    CrashHandler::Handle($e, $plugin);
                    $plugin->DisablePlugin();
                }
            }
        }
    }

    public function OnUserJoin(UserJoinEvent $event)
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
                    $plugin->OnUserJoin($event);
                }
                catch (Throwable $e)
                {
                    cmm::c("exception.userjoin", [$plugin->GetPluginName(), $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()]);CrashHandler::Handle($e, $plugin);
                    $plugin->DisablePlugin();
                }
            }
        }
    }

    public function OnUserLeft(UserLeftEvent $event) : void
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
                    $plugin->OnUserLeft($event);
                }
                catch (Throwable $e)
                {
                    cmm::c("exception.userleft", [$plugin->GetPluginName(), $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()]);
                    CrashHandler::Handle($e, $plugin);
                    $plugin->DisablePlugin();
                }
            }
        }
    }

    public function OnUserKick(UserKickEvent $event) : void
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
                    $plugin->OnUserKick($event);
                }
                catch (Throwable $e)
                {
                    cmm::c("exception.userkick", [$plugin->GetPluginName(), $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()]);
                    CrashHandler::Handle($e, $plugin);
                    $plugin->DisablePlugin();
                }
            }
        }
    }

    public function OnBotJoin(BotJoinEvent $event) : void
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
                    $plugin->OnBotJoin($event);
                }
                catch (Throwable $e)
                {
                    cmm::c("exception.botjoin", [$plugin->GetPluginName(), $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()]);
                    CrashHandler::Handle($e, $plugin);
                    $plugin->DisablePlugin();
                }
            }
        }
    }

    public function OnBotLeft(BotLeftEvent $event) : void
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
                    $plugin->OnBotLeft($event);
                }
                catch (Throwable $e)
                {
                    cmm::c("exception.botleft", [$plugin->GetPluginName(), $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()]);
                    CrashHandler::Handle($e, $plugin);
                    $plugin->DisablePlugin();
                }
            }
        }
    }

    public function OnCommandPreProcess(CommandPreProcessEvent $event) : void
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
                    $plugin->OnCommandPreProcess($event);
                }
                catch (Throwable $e)
                {
                    cmm::c("exception.commandpreprocess", [$plugin->GetPluginName(), $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()]);
                    CrashHandler::Handle($e, $plugin);
                    $plugin->DisablePlugin();
                }
            }
        }
    }
}