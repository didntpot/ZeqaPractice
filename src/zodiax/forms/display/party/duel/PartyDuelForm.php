<?php

declare(strict_types=1);

namespace zodiax\forms\display\party\duel;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\kits\DefaultKit;
use zodiax\kits\KitsManager;
use zodiax\party\duel\PartyDuelHandler;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function count;

class PartyDuelForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && $session->isInParty() && ($party = $session->getParty()) !== null && $data !== null && isset($extraData["kits"]) && isset($extraData["kits"][$data])){
				$kit = $extraData["kits"][$data];
				if($kit instanceof DefaultKit){
					PartyDuelHandler::placeInQueue($party, $kit->getName());
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Party " . TextFormat::WHITE . "Duel"));
		$form->setContent("");
		$duels = KitsManager::getDuelKits();
		if(count($duels) <= 0){
			$form->addButton(TextFormat::GRAY . "None");
		}else{
			foreach($duels as $duel){
				$form->addButton(TextFormat::GRAY . $duel->getName() . "\n" . TextFormat::WHITE . PartyDuelHandler::getPartiesInQueue($duel->getName()) . PracticeCore::COLOR . " In-Queued", 0, $duel->getMiscKitInfo()->getTexture());
			}
			$form->addExtraData("kits", $duels);
		}
		$player->sendForm($form);
	}
}
