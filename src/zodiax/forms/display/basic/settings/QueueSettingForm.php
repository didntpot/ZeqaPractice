<?php

declare(strict_types=1);

namespace zodiax\forms\display\basic\settings;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class QueueSettingForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(($session = PlayerManager::getSession($player)) === null){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null){
				$session = PlayerManager::getSession($player);
				$settingsInfo = $session->getSettingsInfo();
				$settingsInfo->setFairQueue($data[0]);
				$settingsInfo->setPingRange($data[1]);
			}
		});

		$settingsInfo = $session->getSettingsInfo();
		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Queue " . TextFormat::WHITE . "Settings"));
		$form->addToggle(TextFormat::WHITE . "Device Queue", $settingsInfo->isFairQueue());
		$form->addToggle(TextFormat::WHITE . "Similar Ping", $settingsInfo->isPingRange());
		$player->sendForm($form);
	}
}