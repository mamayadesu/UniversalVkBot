<?php

namespace uvb\Models;

use uvb\Plugin\PluginBase;

/**
 * Данный класс описывает зарегистрированную команду
 * @package uvb\Models
 *
 *
 */

class CommandInfo
{
    /**
     * @ignore
     */
    private string $CommandName, $Description;

    /**
     * @ignore
     */
    private bool $AllowedForUsers;

    /**
     * @ignore
     */
    private ?PluginBase $Owner;

    /**
     * @ignore
     */
    public function __construct(string $CommandName, string $Description, bool $AllowedForUsers, ?PluginBase $Owner)
    {
        $this->CommandName = $CommandName;
        $this->Description = $Description;
        $this->AllowedForUsers = $AllowedForUsers;
        $this->Owner = $Owner;
    }

    /**
     * @return string Имя команды
     */
    public function GetCommandName() : string
    {
        return $this->CommandName;
    }

    /**
     * @return string Описание команды
     */
    public function GetDescription() : string
    {
        return $this->Description;
    }

    /**
     * @return bool Разрешена ли команда обычным пользователям
     */
    public function IsAllowedForUsers() : bool
    {
        return $this->AllowedForUsers;
    }

    /**
     * Получить объект плагина, к которому команда принадлежит
     *
     * @return PluginBase|null Объект плагина, к которому принадлежит команда. Если плагин был выключен, то значение будет NULL
     */
    public function GetOwner() : ?PluginBase
    {
        return $this->Owner;
    }

    /**
     * @ignore
     */
    public function Dispose() : void
    {
        $this->Owner = null;
    }
}