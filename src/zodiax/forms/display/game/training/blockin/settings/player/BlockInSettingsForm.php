<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\training\blockin\settings\player;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class BlockInSettingsForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($blockIn = $args[0]) === null){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInBlockIn() && $data !== null && isset($extraData["blockIn"])){
				$extraData["blockIn"]->setOpen(!$data[0]);
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully edited block-in match settings");
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(TextFormat::YELLOW . "Match " . TextFormat::WHITE . "Settings"));
		$form->addToggle("Invite Only", !$blockIn->isOpen());
		$form->addExtraData("blockIn", $blockIn);
		$player->sendForm($form);
	}
}
