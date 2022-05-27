<?php

declare(strict_types=1);

namespace zodiax\forms\display\party;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeUtil;

class LeavePartyForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && $session->isInParty() && ($party = $session->getParty()) !== null && !$session->isInPartyDuel() && $data === 0){
				$party->removePlayer($player);
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(TextFormat::RED . "Leave " . TextFormat::WHITE . "Party"));
		$leave = "";
		if(($session = PlayerManager::getSession($player)) !== null){
			$leave = $session->getParty()->isOwner($player) ? TextFormat::RED . "Are you sure you want to leave the party? leaving the party will cause a disband" : TextFormat::RED . "Are you sure you want to leave the party?";
		}
		$form->setContent($leave);
		$form->addButton(TextFormat::GREEN . "Yes");
		$form->addButton(TextFormat::RED . "No");
		$player->sendForm($form);
	}
}
