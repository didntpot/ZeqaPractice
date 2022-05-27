<?php

declare(strict_types=1);

namespace zodiax\forms\display\staff\rank;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class RankManagerMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null){
				switch($data){
					case 0:
						CreateRankForm::onDisplay($player);
						break;
					case 1:
						RankSelectorMenu::onDisplay($player, "edit");
						break;
					case 2:
						RankSelectorMenu::onDisplay($player, "delete");
						break;
					case 3:
						RankSelectorMenu::onDisplay($player, "view");
						break;
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Manage " . TextFormat::WHITE . "Ranks"));
		$form->setContent("Manage the ranks in the server");
		$form->addButton(TextFormat::BOLD . "Create Rank", 0, "textures/ui/confirm.png");
		$form->addButton(TextFormat::BOLD . "Edit Rank", 0, "textures/ui/debug_glyph_color.png");
		$form->addButton(TextFormat::BOLD . "Delete Rank", 0, "textures/ui/realms_red_x.png");
		$form->addButton(TextFormat::BOLD . "View Rank", 0, "textures/ui/magnifyingGlass.png");
		$player->sendForm($form);
	}
}
