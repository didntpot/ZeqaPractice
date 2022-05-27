<?php

declare(strict_types=1);

namespace zodiax\forms\display\party;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\party\duel\PartyDuelHandler;
use zodiax\party\misc\InviteHandler;
use zodiax\party\misc\PartyInvite;
use zodiax\party\PartyManager;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function array_keys;
use function count;

class PartyInboxForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && !$session->isInParty() && $data !== null && isset($extraData["invites"]) && count($extraData["invites"]) > 0){
				$invites = $extraData["invites"];
				$keys = array_keys($invites);
				if(!isset($keys[$data])){
					return;
				}
				$invite = $invites[$keys[$data]];
				if($invite instanceof PartyInvite){
					$pName = $player->getName();
					$opponent = ($pName === $invite->getTo()) ? $invite->getFrom() : $invite->getTo();
					if(($opsession = PlayerManager::getSession(PlayerManager::getPlayerExact($opponent))) !== null && $opsession->isInHub()){
						$party = PartyManager::getPartyFromName($name = $invite->getParty());
						if($party !== null){
							$blacklisted = $party->isBlackListed($player);
							$inqueue = PartyDuelHandler::isInQueue($party);
							$induel = PartyDuelHandler::getDuel($party) !== null;
							if(!$blacklisted && !$inqueue && !$induel){
								InviteHandler::acceptInvite($invite);
								$party->addPlayer($player);
							}elseif($blacklisted){
								$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You are blacklisted from " . $name);
							}elseif($inqueue){
								$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . $name . TextFormat::GRAY . " is already in queue");
							}elseif($induel){
								$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . $name . TextFormat::GRAY . " is already in duel");
							}
						}else{
							$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . $name . TextFormat::GRAY . " does not exist");
						}
					}else{
						$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You can only accept party invites while party is in the lobby");
					}
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Party " . TextFormat::WHITE . "Inbox"));
		$form->setContent("");
		$invites = InviteHandler::getInvitesOf($player);
		if(count($invites) <= 0){
			$form->addButton(TextFormat::GRAY . "None");
		}else{
			foreach($invites as $invite){
				$button = PracticeCore::COLOR . "Sent by: " . TextFormat::WHITE . PlayerManager::getPlayerExact($invite->getFrom())?->getDisplayName() . "\n";
				$button .= PracticeCore::COLOR . "Party: " . $invite->getParty();
				$form->addButton($button);
			}
		}
		$form->addExtraData("invites", $invites);
		$player->sendForm($form);
	}
}
