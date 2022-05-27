<?php

declare(strict_types=1);

namespace zodiax\forms\display\staff\rank;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\ranks\Rank;
use function implode;

class ViewRankForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($rank = $args[0]) === null || !$rank instanceof Rank){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){

		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Rank " . TextFormat::WHITE . "Info"));
		$content = ["Rank Name: " . $rank->getName(), "", "Rank Format: " . $rank->getColor() . $rank->getName(), "", TextFormat::RESET . "Permission: " . $rank->getPermission()];

		$form->setContent(implode("\n", $content));
		$form->addButton(TextFormat::BOLD . "Submit");
		$player->sendForm($form);
	}
}