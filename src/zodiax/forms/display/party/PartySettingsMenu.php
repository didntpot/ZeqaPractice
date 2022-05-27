<?php

declare(strict_types=1);

namespace zodiax\forms\display\party;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\display\party\settings\PartyBlackListedMenu;
use zodiax\forms\display\party\settings\PartyInviteForm;
use zodiax\forms\display\party\settings\PartyKickForm;
use zodiax\forms\display\party\settings\PartyPromoteForm;
use zodiax\forms\display\party\settings\PartySettingsForm;
use zodiax\forms\types\SimpleForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeUtil;

class PartySettingsMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && $session->isInParty() && $data !== null){
				switch($data){
					case 0:
						PartyInviteForm::onDisplay($player);
						break;
					case 1:
						PartyKickForm::onDisplay($player);
						break;
					case 2:
						PartyPromoteForm::onDisplay($player);
						break;
					case 3:
						PartyBlackListedMenu::onDisplay($player);
						break;
					case 4:
						PartySettingsForm::onDisplay($player);
						break;
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(TextFormat::YELLOW . "Party " . TextFormat::WHITE . "Settings"));
		$form->setContent("");
		$form->addButton(TextFormat::GREEN . "Invite " . TextFormat::WHITE . "Member");
		$form->addButton(TextFormat::RED . "Kick " . TextFormat::WHITE . "Member");
		$form->addButton(TextFormat::LIGHT_PURPLE . "Promote " . TextFormat::WHITE . "Member");
		$form->addButton(TextFormat::DARK_GRAY . "Blacklist " . TextFormat::WHITE . "Settings");
		$form->addButton(TextFormat::YELLOW . "Party " . TextFormat::WHITE . "Settings");
		$player->sendForm($form);
	}
}
