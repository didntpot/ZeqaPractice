<?php

declare(strict_types=1);

namespace zodiax\player\misc\cosmetic\trade;

use pocketmine\utils\TextFormat;
use zodiax\player\misc\cosmetic\misc\CosmeticItem;

class TradeItem{

	const ARTIFACT = 0;
	const CAPE = 1;
	const PROJECTILE = 2;
	const KILLPHRASE = 3;
	const COIN = 4;
	const SHARD = 5;

	private string $owner;
	private int $type;
	private int|CosmeticItem $item;
	private string $note;

	public function __construct(string $owner, int $type, int|CosmeticItem $item, string $note){
		$this->owner = $owner;
		$this->type = $type;
		$this->item = $item;
		$this->note = $note;
	}

	public function getOwner() : string{
		return $this->owner;
	}

	public function getType() : int{
		return $this->type;
	}

	public function getItem() : int|CosmeticItem{
		return $this->item;
	}

	public function getNote() : string{
		return $this->note;
	}

	public function getDetail() : string{
		return match ($this->getType()) {
			self::ARTIFACT, self::CAPE, self::KILLPHRASE, self::PROJECTILE => $this->getItem()->getDisplayName(true),
			self::COIN => $this->getItem() . TextFormat::YELLOW . " coin(s)" . TextFormat::RESET,
			self::SHARD => $this->getItem() . TextFormat::AQUA . " shard(s)" . TextFormat::RESET,
			default => "",
		};
	}
}
