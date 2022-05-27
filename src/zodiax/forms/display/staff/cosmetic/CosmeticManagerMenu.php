<?php

declare(strict_types=1);

namespace zodiax\forms\display\staff\cosmetic;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class CosmeticManagerMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null){
				switch($data){
					case 0:
						CosmeticForm::onDisplay($player, "coins");
						break;
					case 1:
						CosmeticForm::onDisplay($player, "shards");
						break;
					case 2:
						CosmeticForm::onDisplay($player, "cape");
						break;
					case 3:
						CosmeticForm::onDisplay($player, "artifact");
						break;
					case 4:
						CosmeticForm::onDisplay($player, "projectile");
						break;
					case 5:
						CosmeticForm::onDisplay($player, "killphrase");
						break;
					case 6:
						CosmeticForm::onDisplay($player, "tag");
						break;
					case 7:
						CosmeticForm::onDisplay($player, "bp");
						break;
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Manage " . TextFormat::WHITE . "Cosmetic"));
		$form->setContent("Manage the cosmetic in the server");
		$form->addButton(TextFormat::BOLD . "Coins", 0, "textures/ui/icon_minecoin_9x9.png");
		$form->addButton(TextFormat::BOLD . "Shards", 0, "textures/ui/icon_new_item.png");
		$form->addButton(TextFormat::BOLD . "Cape", 0, "textures/ui/dressing_room_capes.png");
		$form->addButton(TextFormat::BOLD . "Artifact", 0, "textures/ui/dressing_room_skins.png");
		$form->addButton(TextFormat::BOLD . "Projectile", 0, "textures/ui/particles.png");
		$form->addButton(TextFormat::BOLD . "KillPhrase", 0, "textures/ui/bad_omen_effect.png");
		$form->addButton(TextFormat::BOLD . "CustomTag", 0, "textures/ui/magnifyingGlass.png");
		$form->addButton(TextFormat::BOLD . "Battle Pass", 0, "textures/ui/icon_best3.png");
		$player->sendForm($form);
	}
}
