<?php

declare(strict_types=1);

namespace zodiax\forms\display\kits\edit;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\display\kits\edit\effects\EditKitEffectsMenu;
use zodiax\forms\types\SimpleForm;
use zodiax\kits\DefaultKit;
use zodiax\kits\KitsManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class EditKitMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($kit = $args[0]) === null || !$kit instanceof DefaultKit){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null){
				$kit = $extraData["kit"];
				switch($data){
					case 0:
						$kit->setItems($player->getInventory()->getContents(true));
						$kit->setArmor($player->getArmorInventory()->getContents(true));
						KitsManager::saveKit($kit);
						$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully edited {$kit->getName()}'s items " . TextFormat::GRAY . $kit->getName());
						break;
					case 1:
						EditKitKnockback::onDisplay($player, $kit);
						break;
					case 2:
						EditKitEffectsMenu::onDisplay($player, $kit);
						break;
					case 3:
						EditKitMisc::onDisplay($player, $kit);
						break;
					case 4:
						EditKitIcon::onDisplay($player, $kit);
						break;
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Edit " . TextFormat::WHITE . "Kit"));
		$form->setContent("Choose whether to edit the {$kit->getName()}'s items, knockback, effects, or misc");
		$form->addButton(TextFormat::BOLD . "Edit Items \n(Depends on inv)", 0, "textures/ui/inventory_icon.png");
		$form->addButton(TextFormat::BOLD . "Edit Knockback", 0, "textures/ui/strength_effect.png");
		$form->addButton(TextFormat::BOLD . "Edit Effects", 0, "textures/ui/absorption_effect.png");
		$form->addButton(TextFormat::BOLD . "Edit Misc", 0, "textures/ui/settings_glyph_color_2x.png");
		$form->addButton(TextFormat::BOLD . "Edit Icon", 0, "textures/ui/color_picker.png");
		$form->addExtraData("kit", $kit);
		$player->sendForm($form);
	}
}