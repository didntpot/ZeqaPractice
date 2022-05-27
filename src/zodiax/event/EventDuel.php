<?php

declare(strict_types=1);

namespace zodiax\event;

use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\sound\ClickSound;
use pocketmine\world\sound\XpCollectSound;
use pocketmine\world\World;
use zodiax\arena\ArenaManager;
use zodiax\arena\types\EventArena;
use zodiax\game\items\ItemHandler;
use zodiax\player\info\scoreboard\ScoreboardInfo;
use zodiax\player\misc\VanishHandler;
use zodiax\player\PlayerManager;
use zodiax\PracticeUtil;
use function str_replace;

class EventDuel{

	const STATUS_STARTING = 0;
	const STATUS_IN_PROGRESS = 1;
	const STATUS_ENDING = 2;
	const STATUS_ENDED = 3;

	const MAX_DURATION_SECONDS = 120;

	private string $player1Name;
	private string $player2Name;
	private ?Player $winner;
	private ?Player $loser;
	private string $kit;
	private string $arena;
	private ?World $world;
	private int $status;
	private int $countdownSeconds;
	private int $durationSeconds;

	public function __construct(Player $p1, Player $p2, PracticeEvent $event){
		$this->player1Name = $p1->getName();
		$this->player2Name = $p2->getName();
		$this->winner = null;
		$this->loser = null;
		/** @var EventArena $arena */
		$arena = ArenaManager::getArena($event->getArena());
		$this->kit = $arena->getKit()->getName();
		$this->arena = $event->getArena();
		$this->world = $arena->getWorld();
		$this->countdownSeconds = 5;
		$this->durationSeconds = 0;
		$this->status = self::STATUS_STARTING;
	}

	public function update(int $currentTick){
		if($this->status === self::STATUS_ENDED){
			return;
		}
		$player1 = PlayerManager::getPlayerExact($this->player1Name);
		$player2 = PlayerManager::getPlayerExact($this->player2Name);
		if($player1 === null){
			$this->setEnded($player2);
		}elseif($player2 === null){
			$this->setEnded($player1);
		}
		switch($this->status){
			case self::STATUS_STARTING:
				if($currentTick % 20 === 0){
					if($this->countdownSeconds === 5){
						$this->setInDuel();
						$msg = TextFormat::RED . "Starting duel in 5";
						$player1->sendTitle($msg, "", 5, 20, 5);
						$player2->sendTitle($msg, "", 5, 20, 5);
						$clickSound = new ClickSound();
						$player1->broadcastSound($clickSound, [$player1]);
						$player2->broadcastSound($clickSound, [$player2]);
					}elseif($this->countdownSeconds < 5 && $this->countdownSeconds > 0){
						$msg = TextFormat::RED . $this->countdownSeconds . "...";
						$player1->sendTitle($msg, "", 5, 20, 5);
						$player2->sendTitle($msg, "", 5, 20, 5);
						$clickSound = new ClickSound();
						$player1->broadcastSound($clickSound, [$player1]);
						$player2->broadcastSound($clickSound, [$player2]);
					}elseif($this->countdownSeconds === 0){
						$msg = TextFormat::RED . "Fight!";
						$player1->sendTitle($msg, "", 5, 20, 5);
						$player2->sendTitle($msg, "", 5, 20, 5);
						$xpSound = new XpCollectSound();
						$player1->broadcastSound($xpSound, [$player1]);
						$player2->broadcastSound($xpSound, [$player2]);
						if(!PlayerManager::getSession($player1)->isFrozen()){
							$player1->setImmobile(false);
						}
						if(!PlayerManager::getSession($player2)->isFrozen()){
							$player2->setImmobile(false);
						}
						$this->status = self::STATUS_IN_PROGRESS;
						$this->countdownSeconds = 3;
					}
					$this->countdownSeconds--;
				}
				break;
			case self::STATUS_IN_PROGRESS:
				$p1Session = PlayerManager::getSession($player1);
				$p1Session->getClicksInfo()->update();
				$p2Session = PlayerManager::getSession($player2);
				$p2Session->getClicksInfo()->update();
				if($currentTick % 20 === 0){
					if($this->kit === "Sumo"){
						/** @var EventArena $arena */
						$arena = ArenaManager::getArena($this->arena);
						$minY = $arena->getP1Spawn()->getFloorY() - 2;
						if($player1->getPosition()->getFloorY() < $minY){
							$p1Session->onDeath();
						}elseif($player2->getPosition()->getFloorY() < $minY){
							$p2Session->onDeath();
						}
					}
					$p1Session->getScoreboardInfo()->updateDuration($this->getDuration());
					$p2Session->getScoreboardInfo()->updateDuration($this->getDuration());
					if(++$this->durationSeconds >= ($this->kit !== "Nodebuff" ? self::MAX_DURATION_SECONDS : 600)){
						$this->setEnded();
						return;
					}
				}
				break;
			case self::STATUS_ENDING:
				if($currentTick % 20 === 0 && --$this->countdownSeconds === 0){
					$this->status = self::STATUS_ENDED;
					return;
				}
				break;
		}
	}

	private function setInDuel() : void{
		/** @var EventArena $arena */
		$arena = ArenaManager::getArena($this->arena);
		$spawnPos1 = $arena->getP1Spawn();
		$spawnPos2 = $arena->getP2Spawn();
		$player1 = PlayerManager::getPlayerExact($this->player1Name);
		$p1Session = PlayerManager::getSession($player1);
		$player2 = PlayerManager::getPlayerExact($this->player2Name);
		$p2Session = PlayerManager::getSession($player2);
		$player1->setImmobile();
		$player2->setImmobile();
		PracticeUtil::onChunkGenerated($this->world, $spawnPos1->getFloorX() >> 4, $spawnPos1->getFloorZ() >> 4, function() use ($player1, $p1Session, $spawnPos1, $spawnPos2){
			PracticeUtil::teleport($player1, Position::fromObject($spawnPos1, $this->world), $spawnPos2);
			$p1Session->getKitHolder()->setKit($this->kit);
			$p1Session->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_DUEL);
			if($this->kit === "Nodebuff"){
				$this->adaptKitItems($player1);
			}
			$p1Session->setInHub(false);
		});
		PracticeUtil::onChunkGenerated($this->world, $spawnPos2->getFloorX() >> 4, $spawnPos2->getFloorZ() >> 4, function() use ($player2, $p2Session, $spawnPos1, $spawnPos2){
			PracticeUtil::teleport($player2, Position::fromObject($spawnPos2, $this->world), $spawnPos1);
			$p2Session->getKitHolder()->setKit($this->kit);
			$p2Session->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_DUEL);
			if($this->kit === "Nodebuff"){
				$this->adaptKitItems($player2);
			}
			$p2Session->setInHub(false);
		});
	}

	public function setEnded(mixed $winner = null) : void{
		if($this->status !== self::STATUS_ENDING && $this->status !== self::STATUS_ENDED){
			if(($p1Session = PlayerManager::getSession(PlayerManager::getPlayerExact($this->player1Name))) !== null){
				$p1Session->getKitHolder()->clearKit();
				$p1Session->updateNameTag();
			}
			if(($p2Session = PlayerManager::getSession(PlayerManager::getPlayerExact($this->player2Name))) !== null){
				$p2Session->getKitHolder()->clearKit();
				$p2Session->updateNameTag();
			}
			if($winner !== null && $this->isPlayer($winner)){
				$this->winner = $winner;
				$this->loser = $this->getOpponent($winner->getName());
				if($this->loser instanceof Player){
					VanishHandler::addToVanish($this->loser);
				}
			}else{
				/** @var EventArena $arena */
				$arena = ArenaManager::getArena($this->arena);
				$pos = Position::fromObject($arena->getSpecSpawn(), $this->world);
				PracticeUtil::onChunkGenerated($pos->getWorld(), $pos->getFloorX() >> 4, $pos->getFloorZ() >> 4, function() use ($pos, $p1Session, $p2Session){
					if($p1Session !== null){
						PracticeUtil::teleport($p1Session->getPlayer(), $pos);
						ItemHandler::giveLeaveItem($p1Session->getPlayer());
						$p1Session->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_EVENT);
						$p1Session->updateNameTag();
					}
					if($p2Session !== null){
						PracticeUtil::teleport($p2Session->getPlayer(), $pos);
						ItemHandler::giveLeaveItem($p2Session->getPlayer());
						$p2Session->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_EVENT);
						$p2Session->updateNameTag();
					}
				});
			}
			$this->countdownSeconds = 3;
			$this->status = self::STATUS_ENDING;
		}
	}

	public function adaptKitItems(Player $player) : void{
		$inv = $player->getInventory();
		$items = $inv->getContents();
		$flag = 0;
		$air = VanillaItems::AIR();
		for($i = 9; $i < 36; $i++){
			if($items[$i]->getId() === ItemIds::SPLASH_POTION){
				$inv->setItem($i, $air);
				if(++$flag === 18){
					break;
				}
			}
		}
	}

	public function getOpponent(string|Player $player) : ?Player{
		if($this->isPlayer($player)){
			return ($player instanceof Player ? $player->getName() : $player) === $this->player1Name ? PlayerManager::getPlayerExact($this->player2Name) : PlayerManager::getPlayerExact($this->player1Name);
		}
		return null;
	}

	public function isPlayer(string|Player $player) : bool{
		$name = $player instanceof Player ? $player->getName() : $player;
		return $this->player1Name === $name || $this->player2Name === $name;
	}

	public function getKit() : string{
		return str_replace("Event", "", $this->kit);
	}

	public function getStatus() : int{
		return $this->status;
	}

	public function getResult() : array{
		return ["winner" => $this->winner, "loser" => $this->loser];
	}

	public function getDuration() : string{
		$seconds = $this->durationSeconds % 60;
		$minutes = (int) ($this->durationSeconds / 60);
		return ($minutes < 10 ? "0" . $minutes : $minutes) . ":" . ($seconds < 10 ? "0" . $seconds : $seconds);
	}
}
