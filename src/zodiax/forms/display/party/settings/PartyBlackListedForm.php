<?php

declare(strict_types=1);

namespace zodiax\forms\display\party\settings;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeUtil;
use function count;

class PartyBlackListedForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($type = $args[0]) === null){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && $session->isInParty() && $data !== null && isset($extraData["players"]) && isset($extraData["type"])){
				$party = $session->getParty();
				$name = $extraData["players"][$data[0]];
				if($extraData["type"] === "add"){
					$party->addToBlacklist(PlayerManager::getPlayerExact($name, true)?->getName() ?? "");
				}else{
					$party->removeFromBlacklist($name);
				}
			}
		});

		$title = $type === "add" ? TextFormat::GREEN . "Add " : TextFormat::RED . "Remove ";
		$form->setTitle(PracticeUtil::formatTitle($title . TextFormat::WHITE . "Settings"));
		if(($session = PlayerManager::getSession($player)) !== null){
			$party = $session->getParty();
			if($type === "add"){
				$dropdownArr = [];
				$name = $player->getName();
				foreach(PlayerManager::getOnlinePlayers() as $pName => $p){
					if($pName !== $name && !$party->isBlackListed($p) && !$party->isPlayer($p)){
						$dropdownArr[] = $p->getDisplayName();
					}
				}
			}else{
				$dropdownArr = $party->getBlacklisted();
			}
			if(count($dropdownArr) === 0){
				$label = $type === "add" ? TextFormat::RED . "There are not any players to add to the blacklist" : TextFormat::RED . "There is not anyone blacklisted to your party";
				$form->addLabel($label);
			}else{
				$form->addDropdown("Players:", $dropdownArr);
				$form->addExtraData("players", $dropdownArr);
				$form->addExtraData("type", $type);
			}
		}
		$player->sendForm($form);
	}
}