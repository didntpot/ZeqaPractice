<?php

declare(strict_types=1);

namespace zodiax\player\misc\cosmetic\trade;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\XpCollectSound;
use zodiax\player\misc\cosmetic\misc\CosmeticItem;
use zodiax\PracticeCore;

class TradeHandler{

	private static array $tradeOffer = [];
	private static int $count = 0;

	public static function addOffer(Player $from, Player $to, int $type, int|CosmeticItem $item, string $note) : void{
		self::$tradeOffer[self::$count] = new TradeOffer(self::$count++, $from, $to, $type, $item, $note);
		$from->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "You sent a trade offer to {$to->getDisplayName()}");
		$to->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "{$from->getDisplayName()} sent you a trade offer");
		$to->broadcastSound(new XpCollectSound(), [$to]);
	}

	public static function removeOffer(TradeOffer $offer) : void{
		unset(self::$tradeOffer[$offer->getId()]);
	}

	public static function getTradeOfferById(int $id) : ?TradeOffer{
		return self::$tradeOffer[$id] ?? null;
	}

	public static function getTrade() : array{
		return self::$tradeOffer;
	}

	public static function removeOfferOf(Player $player) : void{
		$name = $player->getName();
		foreach(self::$tradeOffer as $key => $offer){
			if($offer->getTo() === $name || $offer->getFrom() === $name){
				unset(self::$tradeOffer[$key]);
			}
		}
	}
}