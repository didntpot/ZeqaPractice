<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\training;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\display\game\training\blockin\BlockInMenu;
use zodiax\forms\types\SimpleForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\training\TrainingHandler;

class TrainingMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && $data !== null){
				switch($data){
					case 0:
						BlockInMenu::onDisplay($player);
						break;
					case 1:
						TrainingHandler::placeInClutch($player);
						break;
					case 2:
						TrainingHandler::placeInReduce($player);
						break;
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Play " . TextFormat::WHITE . "Training"));
		$form->setContent("");
		$form->addButton(PracticeCore::COLOR . "Block-In " . TextFormat::WHITE . "Practice", 0, "textures/items/shears.png");
		$form->addButton(PracticeCore::COLOR . "Clutch " . TextFormat::WHITE . "Practice", 0, "zeqa/textures/ui/gm/build.png");
		$form->addButton(PracticeCore::COLOR . "Reduce " . TextFormat::WHITE . "Practice", 0, "zeqa/textures/ui/gm/stickfight.png");
		$player->sendForm($form);
	}
}