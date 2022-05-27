<?php

declare(strict_types=1);

namespace zodiax\forms\display\party\duel;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class PartyDuelMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && $session->isInParty() && ($party = $session->getParty()) !== null && $data !== null){
				switch($data){
					case 0:
						PartyDuelRequestFrom::onDisplay($player);
						break;
					case 1:
						PartyDuelInBoxFrom::onDisplay($player);
						break;
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Party " . TextFormat::WHITE . "Inbox"));
		$form->setContent("");
		$form->addButton(PracticeCore::COLOR . "Request " . TextFormat::WHITE . "Duel", 0, "textures/items/paper.png");
		$form->addButton(PracticeCore::COLOR . "Incoming " . TextFormat::WHITE . "Invites", 0, "zeqa/textures/ui/more/duelinbox.png");
		$player->sendForm($form);
	}
}
