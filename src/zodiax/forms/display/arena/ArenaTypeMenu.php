<?php

declare(strict_types=1);

namespace zodiax\forms\display\arena;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\arena\ArenaManager;
use zodiax\forms\types\SimpleForm;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function array_values;

class ArenaTypeMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["formType"])){
				$type = $extraData["formType"];
				switch($data){
					case 0:
						ArenaSelectorMenu::onDisplay($player, $type, array_values(ArenaManager::getFFAArenas()));
						break;
					case 1:
						ArenaSelectorMenu::onDisplay($player, $type, array_values(ArenaManager::getDuelArenas()));
						break;
					case 2:
						ArenaSelectorMenu::onDisplay($player, $type, array_values(ArenaManager::getTrainingArenas()));
						break;
					case 3:
						ArenaSelectorMenu::onDisplay($player, $type, array_values(ArenaManager::getBlockInArenas()));
						break;
					case 4:
						ArenaSelectorMenu::onDisplay($player, $type, array_values(ArenaManager::getEventArenas()));
						break;
				}
			}
		});

		$formType = $args[0] ?? "view";
		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Manage " . TextFormat::WHITE . "Arenas"));
		$form->setContent("Manage the arenas in the server");
		$form->addButton(TextFormat::BOLD . "FFA Arenas", 0, "textures/items/diamond_sword.png");
		$form->addButton(TextFormat::BOLD . "Duel Arena", 0, "textures/items/iron_sword.png");
		$form->addButton(TextFormat::BOLD . "Training Arena", 0, "textures/items/gold_sword.png");
		$form->addButton(TextFormat::BOLD . "Block-In Arena", 0, "textures/items/shears.png");
		$form->addButton(TextFormat::BOLD . "Event Arena", 0, "textures/items/nether_star.png");
		$form->addExtraData("formType", $formType);
		$player->sendForm($form);
	}
}