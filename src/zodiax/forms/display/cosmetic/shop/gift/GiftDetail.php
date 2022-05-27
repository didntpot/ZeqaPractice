<?php

declare(strict_types=1);

namespace zodiax\forms\display\cosmetic\shop\gift;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\player\info\StatsInfo;
use zodiax\player\misc\cosmetic\gacha\Gacha;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class GiftDetail{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($gacha = $args[0]) === null || !$gacha instanceof Gacha){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["gacha"]) && isset($extraData["available_currency"])){
				GiftSelect::onDisplay($player, $extraData["gacha"], $extraData["available_currency"][$data]);
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Gift " . TextFormat::WHITE . "Detail"));
		$content = PracticeCore::COLOR . TextFormat::BOLD . "CRATE DROPRATE\n\n" . TextFormat::RESET;
		$content .= $gacha->getGachaDetailText();
		$content .= "\n";
		$form->setContent($content);
		$currency = $gacha->getCurrency();
		$available_currency = [];
		foreach($currency as $curren => $amount){
			$available_currency[] = $curren;
			switch($curren){
				case StatsInfo::COIN:
					$form->addButton(TextFormat::WHITE . "Buy " . PracticeCore::COLOR . $amount . TextFormat::WHITE . " Coins");
					break;
				case StatsInfo::SHARD:
					$form->addButton(TextFormat::WHITE . "Buy " . TextFormat::AQUA . $amount . TextFormat::WHITE . " Shards");
					break;
			}
		}
		$form->addExtraData("gacha", $gacha);
		$form->addExtraData("available_currency", $available_currency);
		$player->sendForm($form);
	}
}