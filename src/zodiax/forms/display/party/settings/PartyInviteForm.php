<?php

declare(strict_types=1);

namespace zodiax\forms\display\party\settings;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\party\misc\InviteHandler;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function count;

class PartyInviteForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new CustomForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && $session->isInParty() && $data !== null && isset($extraData["players"])){
				$party = $session->getParty();
				$name = $extraData["players"][$data[0]];
				$to = PlayerManager::getPlayerExact($name, true);
				if($to instanceof Player){
					InviteHandler::sendInvite($player, $to, $party);
				}else{
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Can not find player $name");
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(TextFormat::GREEN . "Invite " . TextFormat::WHITE . "Member"));
		if(count($dropdownArr = PlayerManager::getListDisplayNames($player->getDisplayName())) > 0){
			$form->addDropdown("Invite to:", $dropdownArr);
			$form->addExtraData("players", $dropdownArr);
		}else{
			$form->addLabel(TextFormat::RED . "Nobody online");
		}
		$player->sendForm($form);
	}
}