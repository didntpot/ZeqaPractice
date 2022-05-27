<?php

declare(strict_types=1);

namespace zodiax\forms\display\party\settings;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeUtil;

class PartyBlackListedMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && $session->isInParty() && $data !== null){
				PartyBlackListedForm::onDisplay($player, $data === 0 ? "add" : "remove");
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(TextFormat::DARK_GRAY . "Blacklist " . TextFormat::WHITE . "Settings"));
		$form->setContent("");
		$form->addButton(TextFormat::GREEN . "Add " . TextFormat::WHITE . "Blacklist");
		$form->addButton(TextFormat::RED . "Remove " . TextFormat::WHITE . "Blacklist");
		$player->sendForm($form);
	}
}
