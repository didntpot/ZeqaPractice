<?php

declare(strict_types=1);

namespace zodiax\forms\display\staff\rank;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\ranks\RankHandler;
use function array_values;

class CreateRankForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null){
				if(($rank = RankHandler::getRank($name = TextFormat::clean($data[0]))) !== null){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::WHITE . $rank->getName() . TextFormat::RED . " already exists");
					return;
				}
				if(RankHandler::createRank($name, $data[1], array_values(TextFormat::COLORS)[(int) $data[2]], RankHandler::PERMISSION_INDEXES[(int) $data[3]])){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully created new rank " . TextFormat::WHITE . $name);
				}else{
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Failed to create new rank " . TextFormat::WHITE . $name);
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Create " . TextFormat::WHITE . "Rank"));
		$form->addInput("Rank Name");
		$form->addInput("Rank Format");
		$colors = [];
		foreach(TextFormat::COLORS as $color){
			$colors[] = $color . "Color";
		}
		$form->addDropdown("Color", $colors, 0);
		$form->addDropdown("Permission", ["Owner", "Admin", "Mod", "Helper", "Builder", "Content Creator", "VIP+", "VIP", "Normal"], 8);
		$player->sendForm($form);
	}
}