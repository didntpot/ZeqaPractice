<?php

declare(strict_types=1);

namespace zodiax\forms\display\cosmetic\shop\trade;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\player\info\StatsInfo;
use zodiax\player\misc\cosmetic\CosmeticManager;
use zodiax\player\misc\cosmetic\trade\TradeHandler;
use zodiax\player\misc\cosmetic\trade\TradeItem;
use zodiax\player\misc\cosmetic\trade\TradeOffer;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function array_values;
use function count;
use function preg_match;

class SelectItem{

	public static function onDisplay(Player $player, ...$args) : void{
		if(($session = PlayerManager::getSession($player)) === null || !isset($args[0]) || !isset($args[1]) || !isset($args[2]) || !isset($args[3])){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["args"])){
				$state = $extraData["args"][0];
				$to = $extraData["args"][1];
				$type = $extraData["args"][2];
				$note = $extraData["args"][3];
				$offer = $extraData["args"][4] ?? null;
				if(($to = PlayerManager::getPlayerExact($to, true)) === null || !$to->isOnline() || ($session = PlayerManager::getSession($player)) === null){
					return;
				}
				if($type === TradeItem::COIN || $type === TradeItem::SHARD){
					if(isset($data[0]) && preg_match("/[+-]?[0-9]+/", $data[0])){
						$amount = (int) $data[0];
						if($amount < 0){
							$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Only enter positive integer");
							return;
						}
						if($amount > (($type === TradeItem::COIN) ? $session->getStatsInfo()->getCurrency(StatsInfo::COIN) : $session->getStatsInfo()->getCurrency(StatsInfo::SHARD))){
							$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Not enough " . (($type === TradeItem::COIN) ? "coin(s)" : "shard(s)"));
							return;
						}
						if($state === TradeOffer::PREPARE){
							TradeHandler::addOffer($player, $to, $type, $amount, $note);
						}elseif($state === TradeOffer::FIRST){
							$offer->offerSecond($type, $amount, $note);
						}
					}
				}else{
					if(isset($extraData["items"])){
						$item = $extraData["items"][$data[0]];
						if($state === TradeOffer::PREPARE){
							TradeHandler::addOffer($player, $to, $type, $item, $note);
						}elseif($state === TradeOffer::FIRST){
							$offer->offerSecond($type, $item, $note);
						}
					}else{
						$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You do not have any tradable item");
					}
				}
			}
		});

		$type = $args[2];
		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Select " . TextFormat::WHITE . "Item"));
		if($type === TradeItem::COIN || $type === TradeItem::SHARD){
			$typeText = match ($type) {
				TradeItem::COIN => "coin(s)",
				TradeItem::SHARD => "shard(s)"
			};
			$form->addInput("Please provide the amount of $typeText:");
		}else{
			$itemInfo = $session->getItemInfo();
			$itemIds = match ($type) {
				TradeItem::ARTIFACT => $itemInfo->getOwnedArtifact(),
				TradeItem::CAPE => $itemInfo->getOwnedCape(),
				TradeItem::PROJECTILE => $itemInfo->getOwnedProjectile(),
				TradeItem::KILLPHRASE => $itemInfo->getOwnedKillPhrase(),
				default => [],
			};
			$items = CosmeticManager::getCosmeticItemFromList($itemIds, $type);
			$itemsName = [];
			foreach($items as $key => $item){
				if(!$item->isTradable()){
					unset($items[$key]);
					continue;
				}
				$itemsName[] = $item->getDisplayName();
			}
			if(count($items) > 0){
				$form->addDropdown("Choose:", $itemsName);
				$form->addExtraData("items", array_values($items));
			}else{
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You do not have any tradable item of this type");
				return;
			}
		}
		$form->addExtraData("args", $args);
		$player->sendForm($form);
	}
}