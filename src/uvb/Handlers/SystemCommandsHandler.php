<?php

namespace uvb\Handlers;

use uvb\cmm;
use uvb\Config;
use uvb\ConversationIdsResource;
use uvb\Main;
use uvb\Models\Command;
use uvb\Models\CommandInfo;
use uvb\Models\User;
use uvb\Plugin\CommandManager;
use uvb\Plugin\PluginBase;
use uvb\Plugin\PluginManager;
use uvb\Repositories\MessageRepository;

/**
 * @ignore
 */

class SystemCommandsHandler extends PluginBase
{
    private bool $commandsRegistered = false;
    private CommandManager $cmdmgr;
    private PluginManager $plgmgr;
    private Main $main;

    public function SetCmdMgr(CommandManager $cmdmgr) : void
    {
        $this->cmdmgr = $cmdmgr;
    }

    public function SetPlgMgr(PluginManager $plgmgr) : void
    {
        $this->plgmgr = $plgmgr;
    }

    public function SetMain(Main $main)
    {
        $this->main = $main;
    }

    public function RegisterSystemCommands() : void
    {
        if ($this->commandsRegistered)
        {
            return;
        }
        $commands = [];
        $commands[] = new CommandInfo("help", cmm::g("command.commands.private.help"), true, $this);
        $commands[] = new CommandInfo("stop", cmm::g("command.commands.private.stop"), false, $this);
        $commands[] = new CommandInfo("restart", cmm::g("command.commands.private.restart"), false, $this);
        $commands[] = new CommandInfo("update", cmm::g("command.commands.private.update"), false, $this);
        $commands[] = new CommandInfo("plugins", cmm::g("command.commands.private.plugins"), false, $this);
        $commands[] = new CommandInfo("statistic", cmm::g("command.commands.private.statistic"), false, $this);
        $commands[] = new CommandInfo("setlanguage", cmm::g("command.commands.private.setlanguage"), false, $this);
        $commands[] = new CommandInfo("banip", cmm::g("command.commands.private.banip"), false, $this);
        $commands[] = new CommandInfo("unbanip", cmm::g("command.commands.private.unbanip"), false, $this);
        foreach ($commands as $cmd)
        {
            $this->cmdmgr->RegisterPrivateCommand($cmd);
        }

        $commands = [];
        $commands[] = new CommandInfo("help", cmm::g("command.commands.conversation.help"), true, $this);
        $commands[] = new CommandInfo("conversationid", cmm::g("command.commands.conversation.conversationid"), false, $this);
        $commands[] = new CommandInfo("addconversationid", cmm::g("command.commands.conversation.addconversationid"), false, $this);
        foreach ($commands as $cmd)
        {
            $this->cmdmgr->RegisterConversationCommand($cmd);
        }
        $this->commandsRegistered = true;
    }

    private function PrivateOutput(User $user) : string
    {
        $output = cmm::g("command.commandslistprivate");
        $nameToDesc = array();
        foreach ($this->cmdmgr->GetRegisteredPrivateCommands() as $cmdinfo)
        {
            if (!$cmdinfo->IsAllowedForUsers() && !$user->IsAdmin())
            {
                continue;
            }
            $nameToDesc[$cmdinfo->GetCommandName()] = $cmdinfo->GetDescription();
        }

        $output1 = [];
        foreach ($nameToDesc as $name => $desc)
        {
            $output1[] = "/" . $name . " - " . $desc;
        }
        $output .= implode("\n", $output1);
        return $output;
    }

    private function ConversationOutput(User $user) : string
    {
        $output = cmm::g("command.commandslistconversation");
        $nameToDesc = array();
        foreach ($this->cmdmgr->GetRegisteredConversationCommands() as $cmdinfo)
        {
            if (!$cmdinfo->IsAllowedForUsers() && !$user->IsAdmin())
            {
                continue;
            }
            $nameToDesc[$cmdinfo->GetCommandName()] = $cmdinfo->GetDescription();
        }
        $output1 = [];
        foreach ($nameToDesc as $name => $desc)
        {
            $output1[] = "/" . $name . " - " . $desc;
        }
        $output .= implode("\n", $output1);
        return $output;
    }

    public function OnCommand(Command $cmd) : void
    {
        $name = $cmd->GetName();
        $user = $cmd->GetUser();
        $args = $cmd->GetArguments();
        $ab = $this->GetBot()->GetAddressBlocker();
        switch ($name)
        {
            case "help":
                $user->Send($this->PrivateOutput($user));
                break;

            case "stop":
                $user->Send(cmm::g("main.stopping"));
                $this->GetBot()->Shutdown();
                break;

            case "restart":
                $user->Send(cmm::g("main.restarting"));
                $this->GetBot()->Reboot();
                break;

            case "update":
                $this->main->updater->UpdateCommand();
                break;

            case "plugins":
                $plugins = $this->plgmgr->GetPlugins();
                $pluginsName = [];
                foreach ($plugins as $plugin)
                {
                    $pluginsName[] = $plugin->GetPluginName();
                }
                //$user->Send("Загружено плагинов [" . count($plugins) . "]: " . implode(", ", $pluginsName));
                $user->Send(cmm::g("main.pluginsloadedcommand", [count($plugins), implode(", ", $pluginsName)]));
                break;

            case "statistic":
                $user->Send($this->main->GetStatisticAsString());
                break;

            case "setlanguage":
                if (!isset($args[0]))
                {
                    $user->Send(cmm::g("command.setlanguage.noarg"));
                    return;
                }
                if ($this->main->consoleMessagesManager->SetLanguage($args[0]))
                {
                    $user->Send(cmm::g("command.setlanguage.ok"));
                }
                else
                {
                    $user->Send(cmm::g("command.setlanguage.error"));
                }
                break;

            case "banip":
                if (!isset($args[0]))
                {
                    $user->Send(cmm::g("command.banip.noargs"));
                    return;
                }
                if ($ab->IsBanned($args[0], true))
                {
                    $user->Send(cmm::g("command.banip.alreadybanned"));
                    return;
                }
                if (!$ab->Ban($args[0]))
                {
                    $user->Send(cmm::g("command.banip.invalidip"));
                    return;
                }
                $user->Send(cmm::g("command.banip.ok"));
                break;

            case "unbanip":
                if (!isset($args[0]))
                {
                    $user->Send(cmm::g("command.unbanip.noargs"));
                    return;
                }
                if (!$ab->IsBanned($args[0], true))
                {
                    $user->Send(cmm::g("command.unbanip.notbanned"));
                    return;
                }
                $ab->Unban($args[0]);
                $user->Send(cmm::g("command.unbanip.ok"));
                break;
        }
    }

    public function OnConversationCommand(Command $cmd, int $conversationId) : void
    {
        $name = $cmd->GetName();
        $user = $cmd->GetUser();
        $args = $cmd->GetArguments();
        $cids = ConversationIdsResource::$conversationIds;
        switch ($name)
        {
            case "help":
                MessageRepository::SendToConversation($this->ConversationOutput($user), $conversationId, []);
                break;

            case "conversationid":
                //$output = "Идентификатор беседы: " . $conversationId ." или " . ($conversationId - 2000000000) . "\n";
                $output = cmm::g("command.conversationid", [$conversationId, ($conversationId - 2000000000)]);
                $cid = $cids->Get($conversationId);
                if ($cids->Get($conversationId) > 0)
                {
                    $output .= cmm::g("command.adminconvidequivalent", [$cid]);
                }
                else
                {
                    $output .= cmm::g("command.conversationidwarning");
                }
                $user->Send($output);
                break;

            case "addconversationid":
                if (!isset($args[0]))
                {
                    $user->Send(cmm::g("command.addconversationid"));
                    return;
                }
                $cid = intval($args[0]);
                if ($cid <= 2000000000)
                {
                    $user->Send(cmm::g("command.conversationidtoosmall"));
                    return;
                }
                $cids->Set($conversationId, $cid);
                $cids->Save();
                $user->Send(cmm::g("command.addconversationiddone"));
                break;
        }
    }
}