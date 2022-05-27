<?php

declare(strict_types=1);

namespace zodiax\forms\display\arena;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class ArenaManagerMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null){
				switch((int) $data){
					case 0:
						CreateArenaForm::onDisplay($player);
						break;
					case 1:
						ArenaTypeMenu::onDisplay($player, "edit");
						break;
					case 2:
						ArenaTypeMenu::onDisplay($player, "delete");
						break;
					case 3:
						ArenaTypeMenu::onDisplay($player, "view");
						break;
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Manage " . TextFormat::WHITE . "Arenas"));
		$form->setContent("Manage the arenas in the server");
		$form->addButton(TextFormat::BOLD . "Create Arena", 0, "textures/ui/confirm.png");
		$form->addButton(TextFormat::BOLD . "Edit Arena", 0, "textures/ui/debug_glyph_color.png");
		$form->addButton(TextFormat::BOLD . "Delete Arena", 0, "textures/ui/realms_red_x.png");
		$form->addButton(TextFormat::BOLD . "View Arena", 0, "textures/ui/magnifyingGlass.png");
		$player->sendForm($form);
	}
}