<?php
declare(ticks = 1);

namespace uvb\Handlers;

use uvb\cmm;
use uvb\Models\Conversation;
use uvb\Models\Group;
use uvb\System\SystemConfig;
use uvb\ConversationIdsResource;
use uvb\Main;
use uvb\Models\Command;
use uvb\Models\CommandInfo;
use uvb\Models\User;
use uvb\Plugin\CommandManager;
use uvb\Plugin\Plugin;
use uvb\Plugin\PluginManager;
use uvb\Models\Message;

/**
 * @ignore
 */

class SystemCommandsHandler extends Plugin
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

    public function SetMain(Main $main) : void
    {
        $this->main = $main;
    }

    public function RegisterSystemCommands() : void
    {
        if ($this->commandsRegistered)
        {
            return;
        }
        $commands = [
            new CommandInfo("help", cmm::g("command.commands.private.help"), true, $this),
            new CommandInfo("stop", cmm::g("command.commands.private.stop"), false, $this),
            new CommandInfo("admin", cmm::g("command.commands.private.admin"), false, $this),
            new CommandInfo("unadmin", cmm::g("command.commands.private.unadmin"), false, $this),
            new CommandInfo("restart", cmm::g("command.commands.private.restart"), false, $this),
            new CommandInfo("update", cmm::g("command.commands.private.update"), false, $this),
            new CommandInfo("plugins", cmm::g("command.commands.private.plugins"), false, $this),
            new CommandInfo("status", cmm::g("command.commands.private.status"), false, $this),
            new CommandInfo("setlanguage", cmm::g("command.commands.private.setlanguage"), false, $this),
            new CommandInfo("confirmresponse", cmm::g("command.commands.private.confirmresponse"), false, $this)
        ];

        foreach ($commands as $cmd)
        {
            $this->cmdmgr->RegisterPrivateCommand($cmd);
        }

        $commands = [
            new CommandInfo("help", cmm::g("command.commands.conversation.help"), true, $this),
            new CommandInfo("conversationid", cmm::g("command.commands.conversation.conversationid"), false, $this),
            new CommandInfo("addconversationid", cmm::g("command.commands.conversation.addconversationid"), false, $this)
        ];

        foreach ($commands as $cmd)
        {
            $this->cmdmgr->RegisterConversationCommand($cmd);
        }
        $this->commandsRegistered = true;
    }

    private function PrivateOutput(User $user, Group $group) : string
    {
        $output = cmm::g("command.commandslistprivate");
        $nameToDesc = array();
        foreach ($this->cmdmgr->GetRegisteredPrivateCommands() as $cmdinfo)
        {
            if ((!$cmdinfo->IsAllowedForUsers() && !$user->IsAdmin()) || (!$cmdinfo->GetOwner()->IsEnabledForGroup($group) && $user->IsHuman()))
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

    private function ConversationOutput(User $user, Group $group) : string
    {
        $output = cmm::g("command.commandslistconversation");
        $nameToDesc = array();
        foreach ($this->cmdmgr->GetRegisteredConversationCommands() as $cmdinfo)
        {
            if ((!$cmdinfo->IsAllowedForUsers() && !$user->IsAdmin()) || (!$cmdinfo->GetOwner()->IsEnabledForGroup($group) && $user->IsHuman()))
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

    public function OnCommand(Command $cmd, Group $group) : void
    {
        $name = $cmd->GetName();
        $user = $cmd->GetUser();
        $args = $cmd->GetArguments();
        switch ($name)
        {
            case "help":
                $user->Send($this->PrivateOutput($user, $group), $group);
                break;

            case "admin":
                $vk_id = isset($args[0]) ? intval($args[0]) : 0;

                if ($vk_id < 1)
                {
                    $user->Send(cmm::g("command.admin.invalid"));
                    break;
                }

                $user_to_admin = User::Get($vk_id);
                if ($user_to_admin === null)
                {
                    $user->Send(cmm::g("command.admin.invalid_user"));
                    break;
                }

                $user_to_admin->SetAdmin(true);
                $user->Send(cmm::g("command.admin.success", [$user_to_admin->GetFirstName() . " " . $user_to_admin->GetLastName()]));
                break;

            case "unadmin":
                $vk_id = isset($args[0]) ? intval($args[0]) : 0;

                if ($vk_id < 1)
                {
                    $user->Send(cmm::g("command.admin.invalid"));
                    break;
                }

                $user_to_admin = User::Get($vk_id);
                if ($user_to_admin === null)
                {
                    $user->Send(cmm::g("command.admin.invalid_user"));
                    break;
                }

                $user_to_admin->SetAdmin(false);
                $user->Send(cmm::g("command.unadmin.success", [$user_to_admin->GetFirstName() . " " . $user_to_admin->GetLastName()]));
                break;

            case "stop":
                $user->Send(cmm::g("main.stopping"), $group);
                $this->GetBot()->Shutdown();
                break;

            case "restart":
                if (!$this->GetBot()->IsRestartSupported())
                {
                    $user->Send(cmm::g("main.restart_is_not_supported"));
                    return;
                }
                $user->Send(cmm::g("main.restarting"), $group);
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
                $user->Send(cmm::g("main.pluginsloadedcommand", [count($plugins), implode(", ", $pluginsName)]), $group);
                break;

            case "status":
                $user->Send($this->main->GetStatusAsString(), $group);
                break;

            case "setlanguage":
                if (!isset($args[0]))
                {
                    $user->Send(cmm::g("command.setlanguage.noarg"), $group);
                    return;
                }
                if ($this->main->consoleMessagesManager->SetLanguage($args[0]))
                {
                    $user->Send(cmm::g("command.setlanguage.ok"), $group);
                }
                else
                {
                    $user->Send(cmm::g("command.setlanguage.error"), $group);
                }
                break;

            case "confirmresponse":
                if (!isset($args[0]))
                {
                    $confirmResponse = $this->main->bot->GetConfirmResponse();
                    if ($confirmResponse == "")
                        $user->Send(cmm::g("command.confirmresponse.get.empty"), $group);
                    else
                        $user->Send(cmm::g("command.confirmresponse.get", [$confirmResponse]), $group);

                    return;
                }

                $this->main->bot->SetConfirmResponse($args[0]);
                $user->Send(cmm::g("command.confirmresponse.set", [$args[0]]), $group);
        }
    }

    public function OnConversationCommand(Command $cmd, Conversation $conversation, Group $group) : void
    {
        $name = $cmd->GetName();
        $user = $cmd->GetUser();
        $args = $cmd->GetArguments();
        $cids = ConversationIdsResource::$conversationIds;
        switch ($name)
        {
            case "help":
                if (!SystemConfig::Get("enable_help_command_for_conversations"))
                {
                    return;
                }
                Message::SendToConversation($this->ConversationOutput($user, $group), $conversation, [], $group);
                break;

            case "conversationid":
                $output = cmm::g("command.conversationid", [$conversation->GetName(), $conversation->GetId(false), $conversation->GetId(true)]);
                $cid = $cids->Get($conversation->GetId(false));
                if ($cids->Get($conversation->GetId(false)) > 0)
                {
                    $output .= cmm::g("command.adminconvidequivalent", [$cid]);
                }
                else
                {
                    $output .= cmm::g("command.conversationidwarning");
                }
                $user->Send($output, $group);
                break;

            case "addconversationid":
                if (!isset($args[0]))
                {
                    $user->Send(cmm::g("command.addconversationid"), $group);
                    return;
                }
                $cid = intval($args[0]);
                if ($cid <= 2000000000)
                {
                    $user->Send(cmm::g("command.conversationidtoosmall"), $group);
                    return;
                }
                $cids->Set($conversation->GetId(false), $cid);
                $cids->Save();
                $user->Send(cmm::g("command.addconversationiddone"), $group);
                break;
        }
    }

    public function IsEnabledForGroup(Group $group): bool
    {
        return true;
    }
}