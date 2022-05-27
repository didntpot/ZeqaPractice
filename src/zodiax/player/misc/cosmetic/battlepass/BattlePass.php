<?php

declare(strict_types=1);

namespace zodiax\player\misc\cosmetic\battlepass;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\player\misc\cosmetic\CosmeticManager;
use zodiax\player\misc\cosmetic\misc\CosmeticItem;
use zodiax\player\PlayerManager;

class BattlePass{

	private static array $bpItems = [];

	public static function initialize() : void{
		self::addItem(2000, BattlePassItem::BP_COIN, 1000);
		self::addItem(4000, BattlePassItem::BP_SHARD, 200);
		self::addItem(6000, BattlePassItem::BP_COIN, 1200);
		self::addItem(8000, BattlePassItem::BP_SHARD, 400);
		self::addItem(10000, BattlePassItem::BP_COSMETIC_ITEM, CosmeticManager::getCosmeticFromId(CosmeticManager::KILLPHRASE, "31"));

		self::addItem(14000, BattlePassItem::BP_COIN, 3000);
		self::addItem(18000, BattlePassItem::BP_SHARD, 600);
		self::addItem(22000, BattlePassItem::BP_COIN, 3500);
		self::addItem(26000, BattlePassItem::BP_SHARD, 800);
		self::addItem(30000, BattlePassItem::BP_COSMETIC_ITEM, CosmeticManager::getCosmeticFromId(CosmeticManager::CAPE, "88"));

		self::addItem(45000, BattlePassItem::BP_COIN, 5000);
		self::addItem(60000, BattlePassItem::BP_SHARD, 1000);
		self::addItem(75000, BattlePassItem::BP_COIN, 7000);
		self::addItem(90000, BattlePassItem::BP_SHARD, 1500);
		self::addItem(105000, BattlePassItem::BP_COSMETIC_ITEM, CosmeticManager::getCosmeticFromId(CosmeticManager::CAPE, "89"));

		self::addItem(120000, BattlePassItem::BP_COIN, 10000);
		self::addItem(140000, BattlePassItem::BP_SHARD, 3000);
		self::addItem(160000, BattlePassItem::BP_COIN, 15000);
		self::addItem(180000, BattlePassItem::BP_SHARD, 6000);
		self::addItem(200000, BattlePassItem::BP_COSMETIC_ITEM, CosmeticManager::getCosmeticFromId(CosmeticManager::ARTIFACT, "90"));

		self::addItem(2000, BattlePassItem::BP_COIN, 3000, true);
		self::addItem(4000, BattlePassItem::BP_SHARD, 600, true);
		self::addItem(6000, BattlePassItem::BP_COIN, 4500, true);
		self::addItem(8000, BattlePassItem::BP_SHARD, 1200, true);
		self::addItem(10000, BattlePassItem::BP_COSMETIC_ITEM, CosmeticManager::getCosmeticFromId(CosmeticManager::KILLPHRASE, "32"), true);

		self::addItem(14000, BattlePassItem::BP_COIN, 6000, true);
		self::addItem(18000, BattlePassItem::BP_COSMETIC_ITEM, CosmeticManager::getCosmeticFromId(CosmeticManager::ARTIFACT, "8"), true);
		self::addItem(22000, BattlePassItem::BP_COIN, 9000, true);
		self::addItem(26000, BattlePassItem::BP_SHARD, 3000, true);
		self::addItem(30000, BattlePassItem::BP_COSMETIC_ITEM, CosmeticManager::getCosmeticFromId(CosmeticManager::CAPE, "87"), true);

		self::addItem(45000, BattlePassItem::BP_COIN, 12000, true);
		self::addItem(60000, BattlePassItem::BP_COSMETIC_ITEM, CosmeticManager::getCosmeticFromId(CosmeticManager::ARTIFACT, "19"), true);
		self::addItem(75000, BattlePassItem::BP_COIN, 15000, true);
		self::addItem(90000, BattlePassItem::BP_SHARD, 4500, true);
		self::addItem(105000, BattlePassItem::BP_COSMETIC_ITEM, CosmeticManager::getCosmeticFromId(CosmeticManager::CAPE, "90"), true);

		self::addItem(120000, BattlePassItem::BP_COIN, 25000, true);
		self::addItem(140000, BattlePassItem::BP_SHARD, 6000, true);
		self::addItem(160000, BattlePassItem::BP_COIN, 45000, true);
		self::addItem(180000, BattlePassItem::BP_SHARD, 15000, true);
		self::addItem(200000, BattlePassItem::BP_COSMETIC_ITEM, CosmeticManager::getCosmeticFromId(CosmeticManager::ARTIFACT, "C16"), true);
	}

	public static function addItem(int $bp, int $type, CosmeticItem|int $content, bool $isPremium = false) : void{
		self::$bpItems[] = new BattlePassItem($bp, $type, $content, $isPremium);
	}

	public static function claimReward(Player $player) : string{
		if(($session = PlayerManager::getSession($player)) !== null){
			$currentBp = $session->getStatsInfo()->getBp();
			$havePremiumBp = $session->getItemInfo()->getPremiumBp();
			$text = TextFormat::GREEN . TextFormat::BOLD . "PROGRESSION " . TextFormat::RESET . TextFormat::RED . "(ENDS ON 25/07/2022)\n";
			$text .= TextFormat::RESET . "- " . TextFormat::GREEN . "BP" . TextFormat::WHITE . ": " . $currentBp . "\n\n" . TextFormat::RESET;
			$free_items_text = TextFormat::GRAY . "FREE PASS\n\n" . TextFormat::RESET;
			if($havePremiumBp){
				$premium_items_text = TextFormat::GOLD . "ZEQA PASS [" . TextFormat::GREEN . "OWNED" . TextFormat::GOLD . "]\n\n" . TextFormat::RESET;
			}else{
				$premium_items_text = TextFormat::GOLD . "ZEQA PASS [" . TextFormat::GRAY . "NOT OWNED" . TextFormat::GOLD . "]\n\n" . TextFormat::RESET;
			}
			foreach(self::$bpItems as $item){
				$isEnoughBp = $currentBp > $item->getBp();
				if($item->isPremium()){
					$premium_items_text .= $item->getText($havePremiumBp && $isEnoughBp) . "\n";
				}else{
					$free_items_text .= $item->getText($isEnoughBp) . "\n";
				}
				if(!$isEnoughBp){
					continue;
				}
				if($item->isPremium() && (!$havePremiumBp || $session->getItemInfo()->getPremiumBpProgress() >= $item->getBp())){
					continue;
				}
				if(!$item->isPremium() && $session->getItemInfo()->getFreeBpProgress() >= $item->getBp()){
					continue;
				}
				$item->giveItem($player);
			}
			return $text . $free_items_text . "\n" . $premium_items_text . "\n";
		}
		return "";
	}
}
