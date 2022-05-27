<?php

declare(strict_types=1);

namespace zodiax\player\info;

use Closure;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlThread;
use zodiax\data\database\DatabaseManager;
use zodiax\player\misc\PlayerTrait;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\ranks\RankHandler;
use function round;

class StatsInfo{
	use PlayerTrait;

	const COIN = 0;
	const SHARD = 1;
	const BP = 2;

	const MIN_FFA_KILLER_COIN = 10;
	const MAX_FFA_KILLER_COIN = 15;

	const MIN_FFA_VICTIM_COIN = 1;
	const MAX_FFA_VICTIM_COIN = 10;

	const MIN_DUEL_WINNER_COIN = 15;
	const MAX_DUEL_WINNER_COIN = 20;

	const MIN_DUEL_LOSER_COIN = 1;
	const MAX_DUEL_LOSER_COIN = 10;

	const EVENT_WINNER_COIN = 500;
	const EVENT_WINNER_SHARD = 50;

	private int $kills = 0;
	private int $deaths = 0;
	private int $killstreaks = 0;
	private int $coins = 0;
	private int $shards = 0;
	private int $bp = 0;

	public function __construct(Player $player){
		$this->player = $player->getName();
	}

	public function init(array $data) : void{
		$this->kills = (int) ($data["kills"] ?? 0);
		$this->deaths = (int) ($data["deaths"] ?? 0);
		$this->coins = (int) ($data["coins"] ?? 0);
		$this->shards = (int) ($data["shards"] ?? 0);
		$this->bp = (int) ($data["bp"] ?? 0);
	}

	public function getKills() : int{
		return $this->kills;
	}

	public function addKill() : void{
		$this->kills += 1;
		$this->killstreaks += 1;
		if(($player = $this->getPlayer()) !== null){
			if($this->killstreaks % 10 === 0){
				$msg = PracticeCore::PREFIX . RankHandler::formatRanksForTag($player) . PracticeCore::COLOR . " has gotten a " . TextFormat::RED . $this->killstreaks . PracticeCore::COLOR . " killstreak";
				foreach(PlayerManager::getOnlinePlayers() as $p){
					$p->sendMessage($msg);
				}
			}
		}
	}

	public function getDeaths() : int{
		return $this->deaths;
	}

	public function addDeath() : void{
		$this->deaths += 1;
		$this->killstreaks = 0;
	}

	public function getKDR() : float{
		return round($this->kills / (($this->deaths === 0) ? 1 : $this->deaths), 2);
	}

	public function getCurrency(int $type) : int{
		return match ($type) {
			self::COIN => $this->getCoin(),
			self::SHARD => $this->getShard(),
			self::BP => $this->getBp(),
		};
	}

	public function addCurrency(int $type, int $amount) : bool{
		return match ($type) {
			self::COIN => $this->addCoin($amount),
			self::SHARD => $this->addShard($amount),
		};
	}

	public function addCoin(int $amount) : bool{
		if($this->coins + $amount < 0){
			return false;
		}
		$this->coins += $amount;
		return true;
	}

	public function getCoin() : int{
		return $this->coins;
	}

	public function addShard(int $amount) : bool{
		if($this->shards + $amount < 0){
			return false;
		}
		$this->shards += $amount;
		return true;
	}

	public function getShard() : int{
		return $this->shards;
	}

	public function addBp(int $amount) : bool{
		if($this->bp + $amount < 0){
			return false;
		}
		if(($session = $this->getSession()) !== null){
			if($amount > 0 && $session->getItemInfo()->getPremiumBp()){
				$this->bp += 2 * $amount;
			}else{
				$this->bp += $amount;
			}
			return true;
		}
		return false;
	}

	public function getBp() : int{
		return $this->bp;
	}

	public function save(string $xuid, string $name, Closure $closure) : void{
		DatabaseManager::getMainDatabase()->executeImplRaw([0 => "INSERT INTO PlayerStats (xuid, name, kills, deaths, coins, shards, bp) VALUES ('$xuid', '$name', '$this->kills', '$this->deaths', '$this->coins', '$this->shards', '$this->bp') ON DUPLICATE KEY UPDATE name = '$name', kills = '$this->kills', deaths = '$this->deaths', coins = '$this->coins', shards = '$this->shards', bp = '$this->bp'"], [0 => []], [0 => SqlThread::MODE_GENERIC], $closure, $closure);
	}
}
