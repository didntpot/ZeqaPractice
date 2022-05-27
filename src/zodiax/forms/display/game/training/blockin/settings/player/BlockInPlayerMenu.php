<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\training\blockin\settings\player;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class BlockInPlayerMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($blockIn = $args[0]) === null){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInBlockIn() && $data !== null && isset($extraData["blockIn"])){
				switch($data){
					case 0:
						BlockInRolesSetting::onDisplay($player, $extraData["blockIn"]);
						break;
					case 1:
						BlockInInviteForm::onDisplay($player, $extraData["blockIn"]);
						break;
					case 2:
						BlockInKickForm::onDisplay($player, $extraData["blockIn"]);
						break;
					case 3:
						BlockInPromoteForm::onDisplay($player, $extraData["blockIn"]);
						break;
					case 4:
						BlockInBlackListedMenu::onDisplay($player, $extraData["blockIn"]);
						break;
					case 5:
						BlockInSettingsForm::onDisplay($player, $extraData["blockIn"]);
						break;
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Player " . TextFormat::WHITE . "Manager"));
		$form->setContent("");
		$form->addButton(TextFormat::YELLOW . "Roles " . TextFormat::WHITE . "Settings");
		$form->addButton(TextFormat::GREEN . "Invite " . TextFormat::WHITE . "Player");
		$form->addButton(TextFormat::RED . "Kick " . TextFormat::WHITE . "Player");
		$form->addButton(TextFormat::LIGHT_PURPLE . "Transfer " . TextFormat::WHITE . "Ownership");
		$form->addButton(TextFormat::DARK_GRAY . "Blacklist " . TextFormat::WHITE . "Settings");
		$form->addButton(PracticeCore::COLOR . "Match " . TextFormat::WHITE . "Settings");
		$form->addExtraData("blockIn", $blockIn);
		$player->sendForm($form);
	}
}
