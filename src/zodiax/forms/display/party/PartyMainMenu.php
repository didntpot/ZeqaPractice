<?php

declare(strict_types=1);

namespace zodiax\forms\display\party;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class PartyMainMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && !$session->isInParty() && $data !== null){
				switch($data){
					case 0:
						CreatePartyForm::onDisplay($player);
						break;
					case 1:
						JoinPartyForm::onDisplay($player);
						break;
					case 2:
						PartyInboxForm::onDisplay($player);
						break;
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Party " . TextFormat::WHITE . "Menu"));
		$form->setContent("");
		$form->addButton(PracticeCore::COLOR . "Create " . TextFormat::WHITE . "Party", 0, "zeqa/textures/ui/items/event.png");
		$form->addButton(PracticeCore::COLOR . "Join " . TextFormat::WHITE . "Party", 0, "textures/ui/FriendsDiversity.png");
		$form->addButton(PracticeCore::COLOR . "Incoming " . TextFormat::WHITE . "Invites", 0, "zeqa/textures/ui/more/partyinbox.png");
		$player->sendForm($form);
	}
}
