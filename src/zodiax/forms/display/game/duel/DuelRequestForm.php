<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\duel;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\duel\misc\RequestHandler;
use zodiax\forms\types\CustomForm;
use zodiax\kits\KitsManager;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function count;

class DuelRequestForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$to = $args[0] ?? null;
		$form = new CustomForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && $data !== null){
				if(isset($extraData["players"]) && isset($extraData["kits"]) && isset($extraData["players"][$data[0]]) && isset($extraData["kits"][$data[1]])){
					$name = $extraData["players"][$data[0]];
					$kit = $extraData["kits"][$data[1]];
					$to = PlayerManager::getPlayerExact($name, true);
					if($to instanceof Player){
						RequestHandler::sendRequest($player, $to, $kit, false);
					}else{
						$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Can not find player $name");
					}
				}elseif(isset($extraData["player"]) && isset($extraData["kits"]) && isset($extraData["kits"][$data[1]])){
					$to = $extraData["player"];
					$kit = $extraData["kits"][$data[1]];
					if($to->isOnline()){
						RequestHandler::sendRequest($player, $to, $kit, false);
					}else{
						$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Player is not online");
					}
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Request " . TextFormat::WHITE . "Duel"));
		if($to === null){
			if(count($dropdownArr = PlayerManager::getListDisplayNames($player->getDisplayName())) > 0){
				$form->addDropdown("Request to:", $dropdownArr);
				$form->addDropdown("Select a kit:", $duelkits = KitsManager::getDuelKits(true));
				$form->addExtraData("players", $dropdownArr);
				$form->addExtraData("kits", $duelkits);
			}else{
				$form->addLabel(TextFormat::RED . "Nobody online");
			}
		}else{
			$form->addLabel("Request to: {$to->getDisplayName()}");
			$form->addDropdown("Select a kit:", $duelkits = KitsManager::getDuelKits(true));
			$form->addExtraData("player", $to);
			$form->addExtraData("kits", $duelkits);
		}
		$player->sendForm($form);
	}
}