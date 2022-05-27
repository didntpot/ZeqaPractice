<?php

declare(strict_types=1);

namespace zodiax\forms\display\basic\settings;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\player\info\scoreboard\ScoreboardInfo;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class GameplaySettingForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(($session = PlayerManager::getSession($player)) === null){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null){
				$session = PlayerManager::getSession($player);
				$settingsInfo = $session->getSettingsInfo();
				if($settingsInfo->isScoreboard() !== $data[0]){
					if($data[0]){
						$session->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_SPAWN);
					}else{
						$session->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_NONE);
					}
				}
				$settingsInfo->setScoreboard($data[0]);
				$settingsInfo->setCpsPopup($data[1]);
				$settingsInfo->setAutoSprint($data[2]);
				$settingsInfo->setMoreCritical($data[3]);
				$settingsInfo->setSmoothPearl($data[4]);
				$settingsInfo->setBlood($data[5]);
				$settingsInfo->setLightning($data[6]);
			}
		});

		$settingsInfo = $session->getSettingsInfo();
		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Gameplay " . TextFormat::WHITE . "Settings"));
		$form->addToggle(TextFormat::WHITE . "Scoreboard", $settingsInfo->isScoreboard());
		$form->addToggle(TextFormat::WHITE . "CPS Popup", $settingsInfo->isCpsPopup());
		$form->addToggle(TextFormat::WHITE . "Auto Sprint", $settingsInfo->isAutoSprint());
		$form->addToggle(TextFormat::WHITE . "Critical Particles", $settingsInfo->isMoreCritical());
		$form->addToggle(TextFormat::WHITE . "Smooth Pearl", $settingsInfo->isSmoothPearl());
		$form->addToggle(TextFormat::WHITE . "Blood Kill", $settingsInfo->isBlood());
		$form->addToggle(TextFormat::WHITE . "Lightning Kill", $settingsInfo->isLightning());
		$player->sendForm($form);
	}
}