<?php

declare(strict_types=1);

namespace zodiax\forms\display\party;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\party\duel\PartyDuelHandler;
use zodiax\party\PartyManager;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function array_values;
use function count;

class JoinPartyForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && !$session->isInParty() && $data !== null && isset($extraData["parties"]) && isset($extraData["parties"][$data])){
				$party = $extraData["parties"][$data];
				$name = $party->getName();
				$isopen = $party->isOpen();
				$blacklisted = $party->isBlackListed($player);
				$inqueue = PartyDuelHandler::isInQueue($party);
				$induel = PartyDuelHandler::getDuel($party) !== null;
				if($isopen && !$blacklisted && !$inqueue && !$induel){
					$party->addPlayer($player);
				}elseif(!$isopen){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . $name . TextFormat::GRAY . " is invite only");
				}elseif($blacklisted){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You are blacklisted from " . $name);
				}elseif($inqueue){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . $name . TextFormat::GRAY . " is already in queue");
				}elseif($induel){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . $name . TextFormat::GRAY . " is already in duel");
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Join " . TextFormat::WHITE . "Party"));
		$form->setContent("");
		$parties = PartyManager::getParties();
		$size = count($parties);
		if($size <= 0){
			$form->addButton(TextFormat::GRAY . "None");
		}else{
			foreach($parties as $party){
				$name = TextFormat::BOLD . PracticeCore::COLOR . $party->getName();
				$numPlayers = $party->getPlayers(true);
				$isBlacklisted = $party->isBlackListed($player);
				$blacklisted = $isBlacklisted ? TextFormat::WHITE . "[" . TextFormat::DARK_GRAY . "Blacklisted" . TextFormat::WHITE . "] " : "";
				$open = $party->isOpen() ? TextFormat::GREEN . "Open" : TextFormat::RED . "Closed";
				$text = $blacklisted . $name . "\n" . TextFormat::RESET . TextFormat::GREEN . $numPlayers . TextFormat::WHITE . " Members" . TextFormat::GRAY . " | " . $open;
				$form->addButton($text);
			}
		}
		$form->addExtraData("parties", array_values($parties));
		$player->sendForm($form);
	}
}
