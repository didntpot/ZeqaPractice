<?php

declare(strict_types=1);

namespace zodiax\forms\display\basic\settings;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class OthersSettingForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(($session = PlayerManager::getSession($player)) === null){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null){
				$session = PlayerManager::getSession($player);
				$settingsInfo = $session->getSettingsInfo();
				$settingsInfo->setAutoRecycle($data[0]);
				$settingsInfo->setSilentStaff($data[1] ?? false);
			}
		});

		$settingsInfo = $session->getSettingsInfo();
		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Others " . TextFormat::WHITE . "Settings"));
		$form->addToggle(TextFormat::WHITE . "Auto Recycle duplicate(s)", $settingsInfo->isAutoRecycle());
		if($session->getRankInfo()->hasHelperPermissions()){
			$form->addToggle(TextFormat::WHITE . " Silent Join and Leave", $settingsInfo->isSilentStaff());
		}
		$player->sendForm($form);
	}
}