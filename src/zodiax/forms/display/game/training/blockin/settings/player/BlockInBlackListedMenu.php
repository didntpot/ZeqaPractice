<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\training\blockin\settings\player;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeUtil;

class BlockInBlackListedMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($blockIn = $args[0]) === null){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInBlockIn() && $data !== null && isset($extraData["blockIn"])){
				BlockInBlackListedForm::onDisplay($player, $extraData["blockIn"], $data === 0 ? "add" : "remove");
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(TextFormat::DARK_GRAY . "Blacklist " . TextFormat::WHITE . "Settings"));
		$form->setContent("");
		$form->addButton(TextFormat::GREEN . "Add " . TextFormat::WHITE . "Blacklist");
		$form->addButton(TextFormat::RED . "Remove " . TextFormat::WHITE . "Blacklist");
		$form->addExtraData("blockIn", $blockIn);
		$player->sendForm($form);
	}
}
