<?php

namespace uvb\System;

use CliForms\MenuBox\MenuBox;
use CliForms\MenuBox\MenuBoxItem;
use CliForms\MenuBox\MenuBoxTypes;
use Data\String\BackgroundColors;
use Data\String\ForegroundColors;
use uvb\cmm;
use uvb\ConsoleMessagesManager;
use uvb\Main;

/**
 * @ignore
 */

class SystemConfigSetup
{
    private ?Main $main = null;

    private MenuBoxItem $close;

    private MenuBox $menu;

    public string $langName = "";

    public array $langs = array();

    public array $config = array();

    public function __construct($cfg_or_main)
    {
        if ($cfg_or_main instanceof Main)
        {
            $this->main = $cfg_or_main;

        }
        else if (is_array($cfg_or_main))
        {
            $this->config = $cfg_or_main;
        }
        else
        {
            var_dump($cfg_or_main);
            throw new \Exception("Something bad happened");
        }
        $this->menu = new MenuBox(cmm::g("cfgsetup.title"), $this, MenuBoxTypes::KeyPressType);

        $this->close = new MenuBoxItem((MAIN_THREAD ? cmm::g("cfgsetup.close") : cmm::g("cfgsetup.start")), function(MenuBox $menu)
        {
            $this->_close($menu);
        });

        $this->menu->SetZeroItem($this->close);
    }

    public function SetLanguage(string $langId) : void
    {
        if (MAIN_THREAD)
        {
            $this->main->consoleMessagesManager->SetLanguage($langId);
        }
        else
        {
            ConsoleMessagesManager::SetLanguage1($langId);
        }
        //Menu title update
        $this->close->Name = (MAIN_THREAD ? cmm::g("cfgsetup.close") : cmm::g("cfgsetup.start"));
    }

    public function _close(MenuBox $menu) : void
    {
        $menu->Close();
    }
}