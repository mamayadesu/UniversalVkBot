<?php
declare(ticks = 1);

namespace uvb\Models;

use uvb\Plugin\Plugin;

/**
 * Данный класс описывает зарегистрированную команду
 * @package uvb\Models
 *
 *
 */

final class CommandInfo
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
    private ?Plugin $Owner;

    /**
     * @ignore
     */
    public function __construct(string $CommandName, string $Description, bool $AllowedForUsers, ?Plugin $Owner)
    {
        $this->CommandName = $CommandName;
        $this->Description = $Description;
        $this->AllowedForUsers = $AllowedForUsers;
        $this->Owner = $Owner;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return string Имя команды
     */
    public function GetCommandName() : string
    {
        return $this->CommandName;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return string Описание команды
     */
    public function GetDescription() : string
    {
        return $this->Description;
    }

    /**
     * Появилось в API: 1.0
     *
     * @return bool Разрешена ли команда обычным пользователям
     */
    public function IsAllowedForUsers() : bool
    {
        return $this->AllowedForUsers;
    }

    /**
     * Получить объект плагина, к которому команда принадлежит
     *
     * Появилось в API: 1.0
     *
     * @return Plugin|null Объект плагина, к которому принадлежит команда. Если плагин был выключен, то значение будет NULL
     */
    public function GetOwner() : ?Plugin
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