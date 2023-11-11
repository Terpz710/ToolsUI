<?php

namespace Terpz710\ToolsUI;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

use Terpz710\ToolsUI\Command\ToolsCommand;
use davidglitch04\libEco\libEco;

class Main extends PluginBase {
    public function onEnable(): void {
        $settingsFile = $this->getDataFolder() . "Settings.yml";
        $settings = $this->loadSettings($settingsFile);
        
        $libEco = new libEco();

        $this->getServer()->getCommandMap()->register("tools", new ToolsCommand(new Config($settingsFile, Config::YAML), $libEco));
    }

    public function loadSettings(string $settingsFile): array {
        if (!file_exists($settingsFile)) {
            $this->saveResource("Settings.yml");
        }
        $settings = yaml_parse_file($settingsFile);
        return $settings;
    }
}
