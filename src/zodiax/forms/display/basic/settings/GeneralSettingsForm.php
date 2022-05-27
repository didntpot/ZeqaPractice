<?php

declare(strict_types=1);

namespace zodiax\forms\display\basic\settings;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class GeneralSettingsForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null){
				switch($data){
					case 0:
						GameplaySettingForm::onDisplay($player);
						break;
					case 1:
						QueueSettingForm::onDisplay($player);
						break;
					case 2:
						ArenaSettingForm::onDisplay($player);
						break;
					case 3:
						OthersSettingForm::onDisplay($player);
						break;
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "General " . TextFormat::WHITE . "Settings"));
		$form->addButton(PracticeCore::COLOR . "Gameplay " . TextFormat::WHITE . "Settings");
		$form->addButton(PracticeCore::COLOR . "Queue " . TextFormat::WHITE . "Settings");
		$form->addButton(PracticeCore::COLOR . "Arena " . TextFormat::WHITE . "Settings");
		$form->addButton(PracticeCore::COLOR . "Others " . TextFormat::WHITE . "Settings");
		$player->sendForm($form);
	}
}