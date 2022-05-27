<?php

declare(strict_types=1);

namespace zodiax\forms\display\cosmetic\shop\battlepass;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\player\misc\cosmetic\battlepass\BattlePass;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class BattlePassDetail{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){

		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Battle " . TextFormat::WHITE . "Pass"));
		$form->setContent(BattlePass::claimReward($player));
		$form->addButton(TextFormat::BOLD . "Done");
		$player->sendForm($form);
	}
}