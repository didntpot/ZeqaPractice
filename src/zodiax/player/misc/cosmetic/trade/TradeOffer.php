<?php

declare(strict_types=1);

namespace zodiax\player\misc\cosmetic\trade;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\XpCollectSound;
use pocketmine\world\sound\XpLevelUpSound;
use zodiax\data\log\LogMonitor;
use zodiax\player\info\StatsInfo;
use zodiax\player\misc\cosmetic\misc\CosmeticItem;
use zodiax\player\PlayerManager;
use zodiax\player\PracticePlayer;
use zodiax\PracticeCore;

class TradeOffer{

	const PREPARE = 0;
	const FIRST = 1;
	const SECOND = 2;

	private int $id;
	private string $from;
	private string $to;
	private TradeItem $itemFrom;
	private ?TradeItem $itemTo;
	private int $state;

	public function __construct(int $id, Player $from, Player $to, int $type, int|CosmeticItem $item, string $note){
		$this->id = $id;
		$this->from = $from->getName();
		$this->to = $to->getName();
		$this->itemFrom = new TradeItem($this->from, $type, $item, $note);
		$this->itemTo = null;
		$this->state = TradeOffer::FIRST;
	}

	public function getId() : int{
		return $this->id;
	}

	public function getFrom() : string{
		return $this->from;
	}

	public function getTo() : string{
		return $this->to;
	}

	public function getState() : int{
		return $this->state;
	}

	public function getTitle(Player $player) : string{
		$status = TextFormat::YELLOW . "PREPARE...";
		if($this->getState() === self::FIRST){
			if($player->getName() === $this->getFrom()){
				$status = TextFormat::GOLD . "PENDING OFFER";
			}else{
				$status = TextFormat::GREEN . "CONFIRM OFFER";
			}
		}elseif($this->getState() === self::SECOND){
			if($player->getName() === $this->getFrom()){
				$status = TextFormat::GREEN . "CONFIRM OFFER";
			}else{
				$status = TextFormat::GOLD . "PENDING OFFER";
			}
		}
		return TextFormat::WHITE . PlayerManager::getPlayerExact($this->getFrom())?->getDisplayName() . " & " . PlayerManager::getPlayerExact($this->getTo())?->getDisplayName() . "\n" . TextFormat::YELLOW . "(" . $this->getId() . ") " . $status;
	}

	public function getDetail($player) : string{
		$status = TextFormat::YELLOW . "PREPARE...";
		if($this->getState() === self::FIRST){
			if($player->getName() === $this->getFrom()){
				$status = TextFormat::GOLD . "PENDING OFFER";
			}else{
				$status = TextFormat::GREEN . "CONFIRM OFFER";
			}
		}elseif($this->getState() === self::SECOND){
			if($player->getName() === $this->getFrom()){
				$status = TextFormat::GREEN . "CONFIRM OFFER";
			}else{
				$status = TextFormat::GOLD . "PENDING OFFER";
			}
		}
		$msg = "From : " . PlayerManager::getPlayerExact($this->getFrom())?->getDisplayName() . "\n";
		$msg .= "To : " . PlayerManager::getPlayerExact($this->getTo())?->getDisplayName() . "\n";
		$msg .= "Item From : " . $this->itemFrom->getDetail() . "\n";
		$msg .= "Note From : " . $this->itemFrom->getNote() . "\n";
		$msg .= "Item To : " . $this->itemTo?->getDetail() . "\n";
		$msg .= "Note To : " . $this->itemTo?->getNote() . "\n";
		$msg .= "Status : " . $status . "\n";
		return $msg;
	}

	public function getDetailOneLine() : string{
		$from = PlayerManager::getPlayerExact($this->getFrom())?->getDisplayName();
		$to = PlayerManager::getPlayerExact($this->getTo())?->getDisplayName();
		$itemFrom = $this->itemFrom->getDetail();
		$itemTo = $this->itemTo?->getDetail();
		return "Trade ({$this->getId()}) : $from - $itemFrom <=> $to - $itemTo";
	}

	public function offerSecond(int $type, int|CosmeticItem $item, string $note) : void{
		$to = PlayerManager::getPlayerExact($this->getTo());
		$from = PlayerManager::getPlayerExact($this->getFrom());
		if(TradeHandler::getTradeOfferById($this->getId()) === null){
			$msg = PracticeCore::PREFIX . TextFormat::RED . "Trade offer ({$this->getId()}) has been cancelled";
			$to?->sendMessage($msg);
			$from?->sendMessage($msg);
			return;
		}
		$this->itemTo = new TradeItem($this->to, $type, $item, $note);
		$this->state = TradeOffer::SECOND;
		$from?->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "{$to?->getDisplayName()} sent back a trade offer");
		$from?->broadcastSound(new XpCollectSound(), [$from]);
		$to?->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "You sent back a trade offer");
	}

	public function offerConfirm() : void{
		$to = PlayerManager::getPlayerExact($this->getTo());
		$from = PlayerManager::getPlayerExact($this->getFrom());
		if(TradeHandler::getTradeOfferById($this->getId()) === null){
			$msg = PracticeCore::PREFIX . TextFormat::RED . "Trade offer ({$this->getId()}) has been cancelled";
			$to?->sendMessage($msg);
			$from?->sendMessage($msg);
			return;
		}
		if(($fromSession = PlayerManager::getSession($from)) !== null && ($toSession = PlayerManager::getSession($to)) !== null){
			if($this->isOwningItem($fromSession, $this->itemFrom) && $this->isOwningItem($toSession, $this->itemTo)){
				$this->transferItem($fromSession, $toSession, $this->itemFrom);
				$this->transferItem($toSession, $fromSession, $this->itemTo);
				$to->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "You have completed your trade from " . $from->getDisplayName());
				$from->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "You have completed your trade from " . $to->getDisplayName());
				LogMonitor::cosmeticLog($this->getDetailOneLine());
				if($this->itemTo->getType() === TradeItem::COIN || $this->itemTo->getType() === TradeItem::SHARD){
					$to->broadcastSound(new XpLevelUpSound(10), [$to]);
				}
				if($this->itemFrom->getType() === TradeItem::COIN || $this->itemFrom->getType() === TradeItem::SHARD){
					$from->broadcastSound(new XpLevelUpSound(10), [$from]);
				}
			}else{
				$msg = PracticeCore::PREFIX . TextFormat::RED . "Trade offer failed, item invalid";
				$to->sendMessage($msg);
				$from->sendMessage($msg);
			}
		}else{
			$msg = PracticeCore::PREFIX . TextFormat::RED . "Trade offer failed, can not find player";
			$to?->sendMessage($msg);
			$from?->sendMessage($msg);
		}
	}

	public function isOwningItem(PracticePlayer $player, TradeItem $item) : bool{
		return match ($item->getType()) {
			TradeItem::ARTIFACT, TradeItem::CAPE, TradeItem::PROJECTILE, TradeItem::KILLPHRASE => $player->getItemInfo()->isOwningCosmetics($item->getItem()),
			TradeItem::COIN => $player->getStatsInfo()->getCoin() >= $item->getItem(),
			TradeItem::SHARD => $player->getStatsInfo()->getShard() >= $item->getItem(),
			default => false,
		};
	}

	public function transferItem(PracticePlayer $from, PracticePlayer $to, TradeItem $item) : void{
		switch($item->getType()){
			case TradeItem::ARTIFACT:
			case TradeItem::CAPE:
			case TradeItem::PROJECTILE:
			case TradeItem::KILLPHRASE:
				$from->getItemInfo()->alterCosmeticItem($from->getPlayer(), $item->getItem(), true, true, false);
				$to->getItemInfo()->alterCosmeticItem($to->getPlayer(), $item->getItem(), false, true, false);
				break;
			case TradeItem::COIN:
				$from->getStatsInfo()->addCurrency(StatsInfo::COIN, -$item->getItem());
				$to->getStatsInfo()->addCurrency(StatsInfo::COIN, $item->getItem());
				$msg = PracticeCore::PREFIX . TextFormat::GREEN . $item->getItem() . " coin(s) from " . $from->getPlayer()->getDisplayName() . " has been tranfered to " . $to->getPlayer()->getDisplayName();
				$to->getPlayer()->sendMessage($msg);
				$from->getPlayer()->sendMessage($msg);
				break;
			case TradeItem::SHARD:
				$from->getStatsInfo()->addCurrency(StatsInfo::SHARD, -$item->getItem());
				$to->getStatsInfo()->addCurrency(StatsInfo::SHARD, $item->getItem());
				$msg = PracticeCore::PREFIX . TextFormat::GREEN . $item->getItem() . " shard(s) from " . $from->getPlayer()->getDisplayName() . " has been tranfered to " . $to->getPlayer()->getDisplayName();
				$to->getPlayer()->sendMessage($msg);
				$from->getPlayer()->sendMessage($msg);
				break;
		}
	}
}