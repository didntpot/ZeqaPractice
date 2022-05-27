<?php

declare(strict_types=1);

namespace zodiax\player\misc\cosmetic\battlepass;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\player\info\StatsInfo;
use zodiax\player\misc\cosmetic\misc\CosmeticItem;
use zodiax\player\PlayerManager;

class BattlePassItem{

	const BP_COSMETIC_ITEM = 0;
	const BP_COIN = 1;
	const BP_SHARD = 2;

	private int $type;
	private int $bp;
	private bool $isPremium;
	private CosmeticItem|int $content;

	public function __construct(int $bp, int $type, CosmeticItem|int $content, bool $isPremium = false){
		$this->type = $type;
		$this->bp = $bp;
		$this->content = $content;
		$this->isPremium = $isPremium;
	}

	public function getBp() : int{
		return $this->bp;
	}

	public function isPremium() : bool{
		return $this->isPremium;
	}

	public function giveItem(Player $player) : void{
		if(($session = PlayerManager::getSession($player)) !== null){
			switch($this->type){
				case self::BP_COSMETIC_ITEM:
					$session->getItemInfo()->alterCosmeticItem($session->getPlayer(), $this->content, false, false, false, true);
					break;
				case self::BP_COIN:
					$session->getStatsInfo()->addCurrency(StatsInfo::COIN, $this->content);
					break;
				case self::BP_SHARD:
					$session->getStatsInfo()->addCurrency(StatsInfo::SHARD, $this->content);
					break;
			}
			if($this->isPremium){
				$session->getItemInfo()->setPremiumBpProgress($this->bp);
			}else{
				$session->getItemInfo()->setFreeBpProgress($this->bp);
			}
		}
	}

	public function getText(bool $received = false) : string{
		$text = ($received ? TextFormat::WHITE . "[" . TextFormat::GREEN . $this->bp . TextFormat::WHITE . "] " . TextFormat::RESET : TextFormat::WHITE . "[" . TextFormat::GRAY . $this->bp . TextFormat::WHITE . "] " . TextFormat::RESET);
		return $text . match ($this->type) {
				self::BP_COSMETIC_ITEM => TextFormat::WHITE . $this->content->getDisplayName(true) . TextFormat::RESET,
				self::BP_COIN => TextFormat::YELLOW . $this->content . TextFormat::WHITE . " Coins" . TextFormat::RESET,
				self::BP_SHARD => TextFormat::AQUA . $this->content . TextFormat::WHITE . " Shards" . TextFormat::RESET,
			};
	}
}