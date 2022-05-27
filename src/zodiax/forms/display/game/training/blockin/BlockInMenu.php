<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\training\blockin;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\training\TrainingHandler;
use function count;

class BlockInMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && $data !== null){
				switch($data){
					case 0:
						TrainingHandler::placeInBlockIn($player);
						break;
					case 1:
						BlockInSelector::onDisplay($player);
						break;
					case 2:
						BlockInInboxForm::onDisplay($player);
						break;
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Block-In " . TextFormat::WHITE . "Practice"));
		$form->setContent("");
		$form->addButton(PracticeCore::COLOR . "Create " . TextFormat::WHITE . "Match", 0, "textures/ui/icon_multiplayer.png");
		$form->addButton(PracticeCore::COLOR . "Running " . TextFormat::WHITE . "Matches (" . count(TrainingHandler::getAvailableBlockIns()) . ")", 0, "textures/items/shears.png");
		$form->addButton(PracticeCore::COLOR . "Incoming " . TextFormat::WHITE . "Invites", 0, "zeqa/textures/ui/more/duelinbox.png");
		$player->sendForm($form);
	}
}
