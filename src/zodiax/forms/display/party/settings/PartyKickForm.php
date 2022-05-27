<?php

declare(strict_types=1);

namespace zodiax\forms\display\party\settings;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function count;

class PartyKickForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new CustomForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && $session->isInParty() && $data !== null && isset($extraData["members"])){
				$party = $session->getParty();
				$name = $extraData["members"][$data[0]];
				$reason = (string) $data[1];
				$blackList = (bool) $data[2];
				$member = PlayerManager::getPlayerExact($name, true);
				if($member instanceof Player){
					if($party->isPlayer($member)){
						$party->removePlayer($member, $reason, $blackList);
					}else{
						$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . $name . TextFormat::GRAY . " is no longer in your party");
					}
				}else{
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Can not find player $name");
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(TextFormat::RED . "Kick " . TextFormat::WHITE . "Member"));
		$members = [];
		if(($session = PlayerManager::getSession($player)) !== null){
			$members = $session->getParty()->getPlayers();
		}
		$name = $player->getName();
		$dropdownArr = [];
		foreach($members as $member){
			if($member !== $name){
				$dropdownArr[] = PlayerManager::getPlayerExact($member)?->getDisplayName();
			}
		}
		if(count($dropdownArr) > 0){
			$form->addDropdown(PracticeCore::COLOR . "Members:", $dropdownArr);
			$form->addInput(TextFormat::RED . "Reason:");
			$form->addToggle(TextFormat::RED . "Add to Blacklist:", false);
			$form->addExtraData("members", $dropdownArr);
		}else{
			$form->addLabel(TextFormat::RED . "Nobody in party");
		}
		$player->sendForm($form);
	}
}