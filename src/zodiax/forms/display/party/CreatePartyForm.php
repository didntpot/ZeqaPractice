<?php

declare(strict_types=1);

namespace zodiax\forms\display\party;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\party\PartyManager;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function preg_match;
use function strlen;

class CreatePartyForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new CustomForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && !$session->isInParty() && $data !== null){
				$partyName = TextFormat::clean((string) $data[0]);
				$inviteOnly = (bool) $data[1];
				if(!preg_match("/[^A-Za-z0-9' ]/", $partyName)){
					if(strlen($partyName) > 0 && strlen($partyName) <= 20){
						$party = PartyManager::getPartyFromName($partyName);
						if($party === null){
							PartyManager::createParty($player, $partyName, !$inviteOnly);
						}else{
							$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . $data[0] . TextFormat::GRAY . "  was taken");
						}
					}else{
						$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "No longer than 20 alphabets");
					}
				}else{
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Only english letters");
				}
			}
		});

		$name = "{$player->getDisplayName()}'s Party";
		$form->setTitle(PracticeUtil::formatTitle(TextFormat::YELLOW . "Create " . TextFormat::WHITE . "Party"));
		$form->addInput(TextFormat::WHITE . "Party Name:", $name, $name);
		$form->addToggle("Invite Only", false);
		$player->sendForm($form);
	}
}