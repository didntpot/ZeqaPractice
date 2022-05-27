<?php

declare(strict_types=1);

namespace zodiax\forms\display\kits;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class KitManagerMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null){
				switch($data){
					case 0:
						CreateKitForm::onDisplay($player);
						break;
					case 1:
						KitSelectorMenu::onDisplay($player, "edit");
						break;
					case 2:
						KitSelectorMenu::onDisplay($player, "delete");
						break;
					case 3:
						KitSelectorMenu::onDisplay($player, "view");
						break;
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Manage " . TextFormat::WHITE . "Kits"));
		$form->setContent("Manage the kits in the server");
		$form->addButton(TextFormat::BOLD . "Create Kit", 0, "textures/ui/confirm.png");
		$form->addButton(TextFormat::BOLD . "Edit Kit", 0, "textures/ui/debug_glyph_color.png");
		$form->addButton(TextFormat::BOLD . "Delete Kit", 0, "textures/ui/realms_red_x.png");
		$form->addButton(TextFormat::BOLD . "View Kit", 0, "textures/ui/magnifyingGlass.png");
		$player->sendForm($form);
	}
}