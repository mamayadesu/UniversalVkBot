<?php

namespace uvb\Plugin;

use IO\Console;
use uvb\Handlers\SystemCommandsHandler;
use uvb\Main;
use uvb\Models\CommandInfo;

/**
 * Менеджер команд
 * @package uvb\Plugin
 *
 *
 */

class CommandManager
{
    /**
     * @ignore
     */
    private Main $main;

    /**
     * @ignore
     */
    private array $registeredPrivateCommands = [], $registeredConversationCommands = [];

    /**
     * @ignore
     */
    private static ?CommandManager $instance = null;

    /**
     * @ignore
     */
    public function __construct(Main $main)
    {
        if (self::$instance != null)
        {
            throw new \Exception("CommandManager is already initialized");
        }
        $this->main = $main;
        self::$instance = $this;
    }

    /**
     * Зарегистрировать новую команду для личных сообщений
     *
     * @param CommandInfo $commandInfo Объект, описывающий зарегистрированную команду
     */
    public function RegisterPrivateCommand(CommandInfo $commandInfo) : void
    {
        $name = $commandInfo->GetCommandName();
        $i = count($this->registeredPrivateCommands);
        $this->registeredPrivateCommands[] = $commandInfo;
    }

    /**
     * Удалить зарегистрированную команду для личных сообщений
     *
     * @param string $commandName Имя команды
     * @param PluginBase $owner Плагин, к которому команда принадлежит
     */
    public function UnregisterPrivateCommand(string $commandName, PluginBase $owner) : void
    {
        $k = -1;
        for ($i = 0; $i < count($this->registeredPrivateCommands); $i++)
        {if (!$this->registeredPrivateCommands[$i] instanceof CommandInfo) continue;
            if ($this->registeredPrivateCommands[$i]->GetCommandName() == $commandName && !($this->registeredPrivateCommands[$i]->GetOwner() instanceof SystemCommandsHandler) && $owner->GetPluginName() == $this->registeredPrivateCommands[$i]->GetOwner()->GetPluginName())
            {
                $k = $i;
            }
        }
        if ($k == -1)
        {
            return;
        }
        $this->registeredPrivateCommands[$k]->Dispose();
        $this->registeredPrivateCommands[$k] = null;
        unset($this->registeredPrivateCommands[$k]);
        $newArr = [];
        $c = -1;
        foreach ($this->registeredPrivateCommands as $cmd)
        {
            $c++;
            $newArr[$c] = $cmd;
        }
        $this->registeredPrivateCommands = $newArr;
    }

    /**
     * Получить список зарегистрированных команд для личных сообщений
     *
     * @return array<CommandInfo> Список объектов CommandInfo, описывающие зарегистрированные команды
     */
    public function GetRegisteredPrivateCommands() : array
    {
        $newArr = [];
        $c = -1;
        foreach ($this->registeredPrivateCommands as $cmd)
        {
            $c++;
            $newArr[$c] = $cmd;
        }
        return $newArr;
    }

    /**
     * Зарегистрировать команду для бесед
     *
     * @param CommandInfo $commandInfo Объект, описывающий зарегистрированную команду
     */
    public function RegisterConversationCommand(CommandInfo $commandInfo) : void
    {
        $name = $commandInfo->GetCommandName();
        $i = count($this->registeredConversationCommands);
        $this->registeredConversationCommands[] = $commandInfo;
    }

    /**
     * Удалить зарегистрированную команду для бесед
     *
     * @param string $commandName Имя команды
     * @param PluginBase $owner Плагин, к которому принадлежит команда
     */
    public function UnregisterConversationCommand(string $commandName, PluginBase $owner) : void
    {
        $k = -1;
        for ($i = 0; $i < count($this->registeredConversationCommands); $i++)
        {if (!$this->registeredConversationCommands[$i] instanceof CommandInfo) continue;
            if ($this->registeredConversationCommands[$i]->GetCommandName() == $commandName && !($this->registeredConversationCommands[$i]->GetOwner() instanceof SystemCommandsHandler) && $owner->GetPluginName() == $this->registeredConversationCommands[$i]->GetOwner()->GetPluginName())
            {
                $k = $i;
            }
        }
        if ($k == -1)
        {
            return;
        }
        $this->registeredConversationCommands[$k]->Dispose();
        $this->registeredConversationCommands[$k] = null;
        unset($this->registeredConversationCommands[$k]);
        $newArr = [];
        $c = -1;
        foreach ($this->registeredConversationCommands as $cmd)
        {
            $c++;
            $newArr[$c] = $cmd;
        }
        $this->registeredConversationCommands = $newArr;
    }

    /**
     * Получить список зарегистрированных команд для бесед
     *
     * @return array<CommandInfo> Список объектов CommandInfo, описывающие зарегистрированные команды
     */
    public function GetRegisteredConversationCommands() : array
    {
        $newArr = [];
        $c = -1;
        foreach ($this->registeredConversationCommands as $cmd)
        {
            $c++;
            $newArr[$c] = $cmd;
        }
        return $newArr;
    }
}