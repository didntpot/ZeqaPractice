<?php

declare(strict_types=1);

namespace zodiax\forms\display\staff\rank;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\ranks\Rank;
use zodiax\ranks\RankHandler;
use function array_values;

class EditRankForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($rank = $args[0]) === null || !$rank instanceof Rank){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["rank"])){
				RankHandler::editRank($name = $extraData["rank"], $data[1], array_values(TextFormat::COLORS)[(int) $data[2]], RankHandler::PERMISSION_INDEXES[(int) $data[3]]);
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully edited rank " . TextFormat::WHITE . "$name");
			}
		});

		$name = $rank->getName();
		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Edit " . TextFormat::WHITE . "Rank"));
		$form->addLabel("Rank Name: $name");
		$form->addInput("Rank Format", "", $rank->getFormat());
		$colors = [];
		foreach(TextFormat::COLORS as $color){
			$colors[] = $color . "Color";
		}
		$form->addDropdown("Color", $colors, 0);
		$form->addDropdown("Permission", ["Owner", "Admin", "Mod", "Helper", "Builder", "Content Creator", "VIP+", "VIP", "Normal"], 8);
		$form->addExtraData("rank", $name);
		$player->sendForm($form);
	}
}