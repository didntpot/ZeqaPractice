<?php

declare(strict_types=1);

namespace zodiax\forms\display\basic\settings;

use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\player\misc\SettingsHandler;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class ArenaSettingForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(($session = PlayerManager::getSession($player)) === null){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null){
				$session = PlayerManager::getSession($player);
				$settingsInfo = $session->getSettingsInfo();
				$settingsInfo->setArenaRespawn($data[0]);
				if($settingsInfo->isHideNonOpponents() !== $data[1] && ($arena = $session->getArena()) !== null && !$arena->canInterrupt()){
					if($data[1]){
						if(($target = $session->getTarget()) !== null){
							$name = $target->getName();
							foreach($arena->getPlayers() as $p){
								if(($opponent = PlayerManager::getPlayerExact($p)) !== null && $opponent->getName() !== $name){
									$player->hidePlayer($opponent);
								}
							}
						}
					}else{
						foreach($arena->getPlayers() as $p){
							if(($opponent = PlayerManager::getPlayerExact($p)) !== null){
								$player->showPlayer($opponent);
							}
						}
					}
				}
				$settingsInfo->setHideNonOpponents($data[1]);
				if($settingsInfo->isDeviceDisplay() !== $data[2]){
					if($data[2]){
						SettingsHandler::addOrRemoveFromCache($player, SettingsHandler::NO_DEVICE, false);
						SettingsHandler::addOrRemoveFromCache($player, SettingsHandler::DEVICE, true);
						foreach(PlayerManager::getAllSessions() as $osession){
							if($osession->isDefaultTag()){
								$clientInfo = $osession->getClientInfo();
								$osession->getPlayer()->sendData([$player], [EntityMetadataProperties::SCORE_TAG => new StringMetadataProperty(PracticeCore::COLOR . $clientInfo->getDeviceOS(true, PracticeCore::isPackEnable()) . TextFormat::GRAY . ' | ' . TextFormat::WHITE . $clientInfo->getInputAtLogin(true))]);
							}
						}
					}else{
						SettingsHandler::addOrRemoveFromCache($player, SettingsHandler::DEVICE, false);
						SettingsHandler::addOrRemoveFromCache($player, SettingsHandler::NO_DEVICE, true);
						foreach(PlayerManager::getAllSessions() as $osession){
							if($osession->isDefaultTag()){
								$osession->getPlayer()->sendData([$player], [EntityMetadataProperties::SCORE_TAG => new StringMetadataProperty('')]);
							}
						}
					}
				}
				$settingsInfo->setDeviceDisplay($data[2]);
				if($data[3] && $data[4]){
					SettingsHandler::addOrRemoveFromCache($player, SettingsHandler::CPS, false);
					SettingsHandler::addOrRemoveFromCache($player, SettingsHandler::PING, false);
					SettingsHandler::addOrRemoveFromCache($player, SettingsHandler::CPS_PING, true);
				}else{
					SettingsHandler::addOrRemoveFromCache($player, SettingsHandler::CPS_PING, false);
					SettingsHandler::addOrRemoveFromCache($player, SettingsHandler::CPS, $data[3]);
					SettingsHandler::addOrRemoveFromCache($player, SettingsHandler::PING, $data[4]);
				}
				$settingsInfo->setCpsDisplay($data[3]);
				$settingsInfo->setPingDisplay($data[4]);
			}
		});

		$settingsInfo = $session->getSettingsInfo();
		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Arena " . TextFormat::WHITE . "Settings"));
		$form->addToggle(TextFormat::WHITE . "Arena Respawn", $settingsInfo->isArenaRespawn());
		$form->addToggle(TextFormat::WHITE . "Hide Non-Opponents", $settingsInfo->isHideNonOpponents());
		$form->addToggle(TextFormat::WHITE . "Device Nametag", $settingsInfo->isDeviceDisplay());
		$form->addToggle(TextFormat::WHITE . "Cps Nametag", $settingsInfo->isCpsDisplay());
		$form->addToggle(TextFormat::WHITE . "Ping Nametag", $settingsInfo->isPingDisplay());
		$player->sendForm($form);
	}
}