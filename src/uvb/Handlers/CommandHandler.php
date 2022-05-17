<?php

namespace uvb\Handlers;

use uvb\cmm;
use uvb\Main;
use uvb\Models\Command;
use uvb\Models\CommandInfo;
use uvb\Models\Message;
use \Throwable;
use uvb\System\CrashHandler;

/**
 * @ignore
 */

class CommandHandler
{
    private Main $main;

    public function __construct(Main $main)
    {
        $this->main = $main;
    }

    public function OnCommand(Command $cmd) : void
    {
        $commands = $this->main->commandManager->GetRegisteredPrivateCommands();
        $executor = null;
        $user = $cmd->GetUser();
        $cmdi = null;
        foreach ($commands as $c)
        {if (!$c instanceof CommandInfo) continue;
            if ($c->GetCommandName() == $cmd->GetName())
            {
                $cmdi = $c;
                $executor = $c->GetOwner();
            }
        }
        if ($executor == null)
        {
            $user->Send(cmm::g("command.unknown", [$cmd->GetName()]));
        }
        else if (!$cmdi->IsAllowedForUsers() && !$user->IsAdmin())
        {
            $user->Send(cmm::g("command.nopermission"));
        }
        else
        {
            try
            {
                $executor->OnCommand($cmd);
            }
            catch (Throwable $e)
            {
                cmm::c("exception.command", [$executor->GetPluginName(), $cmd->GetName(), $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()]);
                CrashHandler::Handle($e, $executor, $cmd);
                $executor->DisablePlugin();
            }
        }
    }

    public function OnConversationCommand(Command $cmd, int $conversationId) : void
    {
        $commands = $this->main->commandManager->GetRegisteredConversationCommands();
        $executor = null;
        $user = $cmd->GetUser();
        $cmdi = null;
        foreach ($commands as $c)
        {if (!$c instanceof CommandInfo) continue;
            if ($c->GetCommandName() == $cmd->GetName())
            {
                $cmdi = $c;
                $executor = $c->GetOwner();
            }
        }
        if ($executor == null)
        {
            Message::SendToConversation(cmm::g("command.convunknown", [$user->GetMention(), $cmd->GetName()]), $conversationId, []);
        }
        else if (!$cmdi->IsAllowedForUsers() && !$user->IsAdmin())
        {
            Message::SendToConversation(cmm::g("command.convnopermission", [$user->GetMention(), $cmd->GetName()]), $conversationId, []);
        }
        else
        {
            try
            {
                $executor->OnConversationCommand($cmd, $conversationId);
            }
            catch (Throwable $e)
            {
                cmm::c("exception.conversationcommand", [$executor->GetPluginName(), $cmd->GetName(), $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()]);
                CrashHandler::Handle($e, $executor, $cmd);
                $executor->DisablePlugin();
            }
        }
    }
}