<?php

declare(strict_types=1);

namespace zodiax\forms\display\party\duel;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\kits\KitsManager;
use zodiax\party\duel\misc\PartyDuelRequestHandler;
use zodiax\party\PartyManager;
use zodiax\party\PracticeParty;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function count;

class PartyDuelRequestFrom{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new CustomForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && $session->isInParty() && ($party = $session->getParty()) !== null && $data !== null && isset($extraData["parties"], $extraData["parties"][$data[0]], $extraData["kits"], $extraData["kits"][$data[1]])){
				$to = PartyManager::getPartyFromName($name = $extraData["parties"][$data[0]]);
				if($to instanceof PracticeParty){
					PartyDuelRequestHandler::sendRequest($party, $to, $extraData["kits"][$data[1]]);
				}else{
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Can not find party $name");
				}
			}
		});

		$party = PartyManager::getPartyFromPlayer($player);
		if($party !== null){
			$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Request " . TextFormat::WHITE . "Duel"));
			$dropdownArr = [];
			$name = $party->getName();
			foreach(PartyManager::getParties() as $p){
				if($name !== $p->getName()){
					$dropdownArr[] = $p->getName();
				}
			}
			if(count($dropdownArr) > 0){
				$form->addDropdown("Request to:", $dropdownArr);
				$form->addDropdown("Select a kit:", $duelkits = KitsManager::getDuelKits(true));
				$form->addExtraData("parties", $dropdownArr);
				$form->addExtraData("kits", $duelkits);
			}else{
				$form->addLabel(TextFormat::RED . "No party exists");
			}
			$player->sendForm($form);
		}
	}
}
