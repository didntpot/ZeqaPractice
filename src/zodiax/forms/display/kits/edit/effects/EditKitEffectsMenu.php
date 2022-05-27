<?php

declare(strict_types=1);

namespace zodiax\forms\display\kits\edit\effects;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\kits\DefaultKit;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class EditKitEffectsMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($kit = $args[0]) === null || !$kit instanceof DefaultKit){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null){
				switch($data){
					case 0:
						KitEffectSelectorMenu::onDisplay($player, $extraData["kit"], "add");
						break;
					case 1:
						KitEffectSelectorMenu::onDisplay($player, $extraData["kit"], "edit");
						break;
					case 2:
						KitEffectSelectorMenu::onDisplay($player, $extraData["kit"], "remove");
						break;
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Effects " . TextFormat::WHITE . "Menu"));
		$form->setContent("This menu allows you to edit the {$kit->getName()}'s effects");
		$form->addButton(TextFormat::BOLD . "Add an Effect", 0, "textures/ui/confirm.png");
		$form->addButton(TextFormat::BOLD . "Edit an Effect", 0, "textures/ui/debug_glyph_color.png");
		$form->addButton(TextFormat::BOLD . "Remove an Effect", 0, "textures/ui/cancel.png");
		$form->addExtraData("kit", $kit);
		$player->sendForm($form);
	}
}