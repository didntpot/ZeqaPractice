<?php

declare(strict_types=1);

namespace zodiax\forms\display\cosmetic\shop\trade;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\player\misc\cosmetic\trade\TradeItem;
use zodiax\player\misc\cosmetic\trade\TradeOffer;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function count;

class CreateOffer{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($state = $args[0]) === null){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["players"], $extraData["state"])){
				$state = $extraData["state"];
				$type = match ($data[1]) {
					0 => TradeItem::ARTIFACT,
					1 => TradeItem::CAPE,
					2 => TradeItem::PROJECTILE,
					3 => TradeItem::KILLPHRASE,
					4 => TradeItem::COIN,
					5 => TradeItem::SHARD
				};
				if($state === TradeOffer::PREPARE){
					SelectItem::onDisplay($player, $state, $extraData["players"][$data[0]], $type, $data[2]);
				}elseif($state === TradeOffer::FIRST && isset($extraData["offer"])){
					SelectItem::onDisplay($player, $state, $extraData["players"][$data[0]], $type, $data[2], $extraData["offer"]);
				}
			}
		});

		if(count($players = PlayerManager::getListDisplayNames($player->getDisplayName())) > 0){
			if($state === TradeOffer::PREPARE){
				$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Create " . TextFormat::WHITE . "Offer"));
				$form->addDropdown("Offer to :", $players);
			}elseif($state === TradeOffer::FIRST){
				$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Response " . TextFormat::WHITE . "Offer"));
				$form->addDropdown("Offer to :", $players = [$args[1]->getFrom()]);
				$form->addExtraData("offer", $args[1]);
			}
			$form->addDropdown("Your item :", ["Artifact", "Cape", "Projectile", "KillPhrase", "Coin", "Shard"]);
			$form->addInput("Note:");
			$form->addExtraData("state", $state);
			$form->addExtraData("players", $players);
		}else{
			$form->addLabel(TextFormat::RED . "Nobody online");
		}
		$player->sendForm($form);
	}
}