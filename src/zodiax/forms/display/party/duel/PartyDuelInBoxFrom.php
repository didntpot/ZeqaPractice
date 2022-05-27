<?php

declare(strict_types=1);

namespace zodiax\forms\display\party\duel;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\kits\KitsManager;
use zodiax\party\duel\misc\PartyDuelRequest;
use zodiax\party\duel\misc\PartyDuelRequestHandler;
use zodiax\party\duel\PartyDuelHandler;
use zodiax\party\PartyManager;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function array_keys;
use function count;

class PartyDuelInBoxFrom{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && $session->isInParty() && ($party = $session->getParty()) !== null && $data !== null && isset($extraData["requests"]) && count($extraData["requests"]) > 0){
				$requests = $extraData["requests"];
				$keys = array_keys($requests);
				if(!isset($keys[$data])){
					return;
				}
				$request = $requests[$keys[$data]];
				if($request instanceof PartyDuelRequest){
					$opponent = ($party->getName() === $request->getTo()) ? $request->getFrom() : $request->getTo();
					if(($opparty = PartyManager::getPartyFromName($opponent)) !== null && ($opsession = PlayerManager::getSession(PlayerManager::getPlayerExact($opparty->getOwner()))) !== null){
						if($opsession->isInHub() && $opsession->isInParty() && !$opparty->isInQueue()){
							PartyDuelRequestHandler::acceptRequest($request);
							PartyDuelHandler::placeInDuel($party, $opparty, $request->getKit());
						}else{
							$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You can only accept duel requests while opponent is in the lobby");
						}
					}
				}
			}
		});

		if(($party = PartyManager::getPartyFromPlayer($player)) !== null){
			$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Incoming " . TextFormat::WHITE . "Invites"));
			$form->setContent("");
			if(count($requests = PartyDuelRequestHandler::getRequestsOf($party)) <= 0){
				$form->addButton(TextFormat::GRAY . "None");
			}else{
				foreach($requests as $request){
					$button = PracticeCore::COLOR . "Sent by: " . TextFormat::WHITE . $request->getFrom() . "\n";
					$button .= PracticeCore::COLOR . "Queue: " . TextFormat::WHITE . $request->getKit();
					$form->addButton($button, 0, KitsManager::getKit($request->getKit())?->getMiscKitInfo()->getTexture() ?? "");
				}
			}
			$form->addExtraData("requests", $requests);
			$player->sendForm($form);
		}
	}
}
