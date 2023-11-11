<?php

namespace Terpz710\RenameUI\Command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\item\Armor;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\item\Tool;

use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;
use davidglitch04\libEco\libEco;

class ToolsCommand extends Command {
    private $settings;
    private $libEco;

    public function __construct(Config $config, libEco $libEco) {
        parent::__construct("tools", "rename or repair an item", "/tools");
        $this->setPermission("toolsui.cmd");
        $this->settings = $config->getAll();
        $this->libEco = $libEco;
    }

    public function execute(CommandSender $sender, string $label, array $args) {
        if ($sender instanceof Player) {
            $this->sendRenameForm($sender);
        }
    }

    public function sendRenameForm(Player $player) {
        $form = new SimpleForm(function (Player $player, ?int $data = null) {
            if ($data === null) return;
            if ($data === 0) {
                $this->sendCustomRenameForm($player);
            } elseif ($data === 1) {
                $this->sendRepairConfirmationForm($player);
            }
        });

        $form->setTitle($this->settings["rename_form_title"]);
        $form->setContent($this->settings["rename_form_content"]);
        $form->addButton($this->settings["rename_button_text"], 0, "textures/items/name_tag");
        $form->addButton($this->settings["repair_button_text"], 1, "textures/items/experience_bottle");
        $form->sendToPlayer($player);
    }

    public function sendCustomRenameForm(Player $player) {
        $form = new CustomForm(function (Player $player, ?array $data = null) {
            if ($data === null) return;
            $newName = $data["new_name"];
            if (!empty($newName)) {
                $this->renameItem($player, $newName);
            } else {
                $player->sendMessage($this->settings["invalid_name_message"]);
            }
        });

        $form->setTitle($this->settings["custom_rename_form_title"]);
        $form->addInput($this->settings["new_name_input_label"], $this->settings["new_name_input_placeholder"], "", "new_name");
        $form->sendToPlayer($player);
    }

    public function sendRepairConfirmationForm(Player $player) {
        $form = new SimpleForm(function (Player $player, ?int $data = null) {
            if ($data === null) return;
            if ($data === 0) {
                $this->repairItem($player);
            }
        });

        $repairFee = (float)$this->settings["repair_fee"];
        $form->setTitle($this->settings["repair_confirmation_form_title"]);
        $form->setContent(str_replace("{Price}", $repairFee, $this->settings["repair_confirmation_form_content"]));
        $form->addButton($this->settings["repair_confirmation_button_yes"]);
        $form->sendToPlayer($player);
    }

    public function renameItem(Player $player, string $newName) {
        $inventory = $player->getInventory();
        $heldItem = $inventory->getItemInHand();

        if (!$heldItem->isNull()) {
            $renameFee = (float)$this->settings["rename_fee"];
            $this->libEco->myMoney($player, function ($balance) use ($player, $newName, $heldItem, $inventory, $renameFee) {
                if ($balance >= $renameFee) {
                    $oldName = $heldItem->getCustomName();
                    $heldItem->setCustomName($newName);
                    $inventory->setItemInHand($heldItem);
                    $player->sendMessage(str_replace("{itemName}", $newName, $this->settings["item_renamed_message"]));

                    $this->libEco->reduceMoney($player, $renameFee, function ($success) use ($player) {
                        if (!$success) {
                            $player->sendMessage($this->settings["not_enough_money_message"]);
                        }
                    });
                } else {
                    $player->sendMessage($this->settings["not_enough_money_message"]);
                }
            });
        } else {
            $player->sendMessage($this->settings["not_repairable_item_message"]);
        }
    }

    public function repairItem(Player $player) {
        $inventory = $player->getInventory();
        $heldItem = $inventory->getItemInHand();

        if (
            !$heldItem->isNull() &&
            $heldItem instanceof Item &&
            $heldItem instanceof Durable &&
            ($heldItem instanceof Tool || $heldItem instanceof Armor)
        ) {
            $repairFee = (float)$this->settings["repair_fee"];

            $this->libEco->myMoney($player, function ($balance) use ($player, $repairFee, $inventory, $heldItem) {
                if ($balance >= $repairFee) {
                    $heldItem->setDamage(0);
                    $inventory->setItemInHand($heldItem);
                    $player->sendMessage($this->settings["repair_success_message"]);

                    $this->libEco->reduceMoney($player, $repairFee, function ($success) use ($player) {
                        if (!$success) {
                            $player->sendMessage($this->settings["not_enough_money_repair_message"]);
                        }
                    });
                } else {
                    $player->sendMessage($this->settings["not_enough_money_repair_message"]);
                }
            });
        } else {
            $player->sendMessage($this->settings["not_repairable_item_message"]);
        }
    }
}
