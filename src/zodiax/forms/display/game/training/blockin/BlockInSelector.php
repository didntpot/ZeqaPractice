<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\training\blockin;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\training\TrainingHandler;
use function count;

class BlockInSelector{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && $data !== null && isset($extraData["blockIns"], $extraData["blockIns"][$data])){
				$blockIn = $extraData["blockIns"][$data];
				$name = PlayerManager::getPlayerExact($blockIn->getOwner())?->getDisplayName() . "'s Block-In";
				$isavailable = $blockIn->isAvailable();
				$isopen = $blockIn->isOpen();
				$blacklisted = $blockIn->isBlackListed($player);
				if($isavailable && $isopen && !$blacklisted){
					$blockIn->addPlayer($player);
				}elseif(!$isopen){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . $name . TextFormat::GRAY . " is invite only");
				}elseif($blacklisted){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You are blacklisted from " . $name);
				}elseif($isavailable){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . $name . TextFormat::GRAY . " is not available anymore");
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Match " . TextFormat::WHITE . "Selector"));
		$form->setContent("");
		if(count($blockIns = TrainingHandler::getAvailableBlockIns()) === 0){
			$form->addButton(TextFormat::GRAY . "None");
		}else{
			foreach($blockIns as $blockIn){
				$name = TextFormat::BOLD . PracticeCore::COLOR . PlayerManager::getPlayerExact($blockIn->getOwner())?->getDisplayName() . TextFormat::WHITE . "'s Block-In" . TextFormat::RESET;
				$blacklisted = $blockIn->isBlackListed($player) ? TextFormat::WHITE . "[" . TextFormat::DARK_GRAY . "Blacklisted" . TextFormat::WHITE . "] " : "";
				$open = $blockIn->isOpen() ? TextFormat::GREEN . "Open" : TextFormat::RED . "Closed";
				$form->addButton($name . "\n" . $blacklisted . $open);
			}
			$form->addExtraData("blockIns", $blockIns);
		}
		$player->sendForm($form);
	}
}
