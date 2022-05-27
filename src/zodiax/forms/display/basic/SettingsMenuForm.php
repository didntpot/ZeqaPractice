<?php

declare(strict_types=1);

namespace zodiax\forms\display\basic;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\display\basic\settings\BuilderModeForm;
use zodiax\forms\display\basic\settings\GeneralSettingsForm;
use zodiax\forms\display\basic\settings\KitSettingsForm;
use zodiax\forms\display\cosmetic\CosmeticMenuForm;
use zodiax\forms\types\SimpleForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class SettingsMenuForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(($session = PlayerManager::getSession($player)) === null){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null){
				switch($data){
					case 0:
						GeneralSettingsForm::onDisplay($player);
						break;
					case 1:
						CosmeticMenuForm::onDisplay($player);
						break;
					case 2:
						KitSettingsForm::onDisplay($player);
						break;
					case 3:
						BuilderModeForm::onDisplay($player);
						break;
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Player " . TextFormat::WHITE . "Settings"));
		$form->setContent("");
		$form->addButton(PracticeCore::COLOR . "General " . TextFormat::WHITE . "Settings", 0, "zeqa/textures/ui/items/settings.png");
		$form->addButton(PracticeCore::COLOR . "Cosmetic " . TextFormat::WHITE . "Settings", 0, "zeqa/textures/ui/more/cosmetics.png");
		$form->addButton(PracticeCore::COLOR . "Kit " . TextFormat::WHITE . "Settings", 0, "zeqa/textures/ui/more/kit_settings.png");
		if($session->getRankInfo()->hasAdminPermissions()){
			$form->addButton(PracticeCore::COLOR . "Builder " . TextFormat::WHITE . "Mode", 0, "textures/ui/haste_effect.png");
		}
		$player->sendForm($form);
	}
}