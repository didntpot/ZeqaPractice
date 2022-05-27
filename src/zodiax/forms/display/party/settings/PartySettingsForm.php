<?php

declare(strict_types=1);

namespace zodiax\forms\display\party\settings;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class PartySettingsForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new CustomForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && $session->isInParty() && $data !== null){
				$session->getParty()->setOpen(!$data[1]);
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully edited party's settings");
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(TextFormat::YELLOW . "Party " . TextFormat::WHITE . "Settings"));
		if(($session = PlayerManager::getSession($player)) !== null){
			$form->addLabel(PracticeCore::COLOR . "Party Name: " . TextFormat::RESET . $session->getParty()->getName());
			$form->addToggle("Invite Only", !$session->getParty()->isOpen());
		}
		$player->sendForm($form);
	}
}
