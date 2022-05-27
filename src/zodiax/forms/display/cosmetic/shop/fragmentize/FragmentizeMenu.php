<?php

declare(strict_types=1);

namespace zodiax\forms\display\cosmetic\shop\fragmentize;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\player\misc\cosmetic\CosmeticManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class FragmentizeMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null){
				switch($data){
					case 0:
						FragmentizeDetail::onDisplay($player, CosmeticManager::CAPE);
						break;
					case 1:
						FragmentizeDetail::onDisplay($player, CosmeticManager::ARTIFACT);
						break;
					case 2:
						FragmentizeDetail::onDisplay($player, CosmeticManager::PROJECTILE);
						break;
					case 3:
						FragmentizeDetail::onDisplay($player, CosmeticManager::KILLPHRASE);
						break;
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Recycle " . TextFormat::WHITE . "Menu"));
		$form->setContent("");
		$form->addButton(PracticeCore::COLOR . "Owned " . TextFormat::WHITE . "Cape", 0, "textures/ui/dressing_room_capes.png");
		$form->addButton(PracticeCore::COLOR . "Owned " . TextFormat::WHITE . "Artifact", 0, "textures/ui/dressing_room_skins.png");
		$form->addButton(PracticeCore::COLOR . "Owned " . TextFormat::WHITE . "Projectile", 0, "textures/ui/particles.png");
		$form->addButton(PracticeCore::COLOR . "Owned " . TextFormat::WHITE . "KillPhrase", 0, "textures/ui/bad_omen_effect.png");
		$player->sendForm($form);
	}
}