<?php

declare(strict_types=1);

namespace zodiax\event;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\sound\ClickSound;
use pocketmine\world\sound\XpCollectSound;
use zodiax\arena\ArenaManager;
use zodiax\arena\types\EventArena;
use zodiax\game\items\ItemHandler;
use zodiax\party\duel\PartyDuel;
use zodiax\party\duel\PartyDuelHandler;
use zodiax\party\PracticeParty;
use zodiax\player\info\scoreboard\ScoreboardInfo;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function count;
use function str_replace;

class WarEvent{

	const STATUS_PREPARING = 0;
	const STATUS_WAITING = 1;
	const STATUS_IN_PROGRESS = 2;
	const STATUS_ENDING = 3;

	private array $players;
	private array $eliminated;
	private array $spectators;
	private PartyDuel $party1;
	private PartyDuel $party2;
	private string $kit;
	private ?PartyDuel $duel;
	private ?PracticeParty $winner;
	private int $currentTick;
	private int $countdownSeconds;
	private int $status;

	public function __construct(string $kit){
		$this->kit = $kit;
		$this->init();
	}

	public function init(){
		$this->players = [];
		$this->eliminated = [];
		$this->spectators = [];
		$this->party1 = null;
		$this->party2 = null;
		$this->duel = null;
		$this->winner = null;
		$this->currentTick = 0;
		$this->countdownSeconds = 90;
		$this->status = self::STATUS_PREPARING;
	}

	public function update() : void{
		$this->currentTick++;
		switch($this->status){
			case self::STATUS_WAITING:
				if($this->currentTick % 20 === 0){
					$msg = PracticeCore::COLOR . " Starting in: " . TextFormat::WHITE . $this->getCountdown();
					foreach($this->players as $p){
						if(($player = PlayerManager::getPlayerExact($p, true)) !== null){
							PlayerManager::getSession($player)->getScoreboardInfo()->updateLineOfScoreboard(1, $msg);
						}else{
							$this->removePlayer($p);
						}
					}
					foreach($this->spectators as $spec){
						if(($spectator = PlayerManager::getPlayerExact($spec, true)) !== null){
							PlayerManager::getSession($spectator)->getScoreboardInfo()->updateLineOfScoreboard(1, $msg);
						}else{
							$this->removeSpectator($spec);
						}
					}
					if($this->countdownSeconds === 5){
						$msg = TextFormat::RED . "Starting event in 5";
						$clickSound = new ClickSound();
						foreach($this->players as $p){
							if(($player = PlayerManager::getPlayerExact($p, true)) !== null){
								$player->sendTitle($msg, "", 5, 20, 5);
								$player->broadcastSound($clickSound, [$player]);
							}else{
								$this->removePlayer($p);
							}
						}
					}elseif($this->countdownSeconds < 5 && $this->countdownSeconds > 0){
						$msg = TextFormat::RED . $this->countdownSeconds . "...";
						$clickSound = new ClickSound();
						foreach($this->players as $p){
							if(($player = PlayerManager::getPlayerExact($p, true)) !== null){
								$player->sendTitle($msg, "", 5, 20, 5);
								$player->broadcastSound($clickSound, [$player]);
							}else{
								$this->removePlayer($p);
							}
						}
					}elseif($this->countdownSeconds === 0){
						if(count($this->players) < 2){
							$msg = PracticeCore::PREFIX . TextFormat::RED . "More players are required to start the event";
							foreach($this->players as $p){
								if(($player = PlayerManager::getPlayerExact($p, true)) !== null){
									$player->sendMessage($msg);
									PlayerManager::getSession($player)->reset();
								}
							}
							foreach($this->spectators as $spec){
								if(($spectator = PlayerManager::getPlayerExact($spec, true)) !== null){
									$spectator->sendMessage($msg);
									PlayerManager::getSession($spectator)->reset();
								}
							}
							$this->init();
						}else{
							$this->status = self::STATUS_IN_PROGRESS;
							$msg = TextFormat::RED . "Start!";
							$xpSound = new XpCollectSound();
							foreach($this->players as $p){
								if(($player = PlayerManager::getPlayerExact($p, true)) !== null){
									$player->sendTitle($msg, "", 5, 20, 5);
									$player->broadcastSound($xpSound, [$player]);
									PlayerManager::getSession($player)->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_EVENT);
								}else{
									$this->removePlayer($p);
								}
							}
							foreach($this->spectators as $spec){
								if(($spectator = PlayerManager::getPlayerExact($spec, true)) !== null){
									$spectator->sendTitle($msg, "", 5, 20, 5);
									PlayerManager::getSession($spectator)->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_EVENT);
								}else{
									$this->removeSpectator($spec);
								}
							}
							$this->startEvent();
						}
					}
					$this->countdownSeconds--;
				}
				break;
			case self::STATUS_IN_PROGRESS:
				break;
			case self::STATUS_ENDING:
				$this->endEvent();
				break;
		}
	}

	private function startEvent() : void{
		$i = 0;
		foreach($this->players as $player){
			if($i < 2){
				if($i % 2 == 0){
					$this->party1 = new PracticeParty($player, "Team 1");
				}else{
					$this->party2 = new PracticeParty($player, "Team 2");
				}
			}else{
				if($i % 2 == 0){
					$this->party1->addPlayer($player);
				}else{
					$this->party2->addPlayer($player);
				}
			}
			$i++;
		}
		PartyDuelHandler::placeInDuel($this->party1, $this->party2, $this->kit);
	}

	private function endEvent() : void{
		foreach($this->players as $player){
			if(($player = PlayerManager::getPlayerExact($player, true)) !== null){
				$this->sendFinalMessage($player);
				PlayerManager::getSession($player)->reset();
			}
		}
		foreach($this->spectators as $spec){
			if(($spectator = PlayerManager::getPlayerExact($spec, true)) !== null){
				$this->sendFinalMessage($spectator);
				PlayerManager::getSession($spectator)->reset();
			}
		}
		$this->init();
	}

	public function addPlayer(Player $player) : void{
		$this->players[$name = $player->getDisplayName()] = $name;
		$this->waiting[$name = $player->getDisplayName()] = $name;
		/** @var EventArena $arena */
		$arena = ArenaManager::getArena($this->arena);
		$pos = Position::fromObject($arena->getSpecSpawn(), $arena->getWorld());
		PracticeUtil::onChunkGenerated($pos->getWorld(), $pos->getFloorX() >> 4, $pos->getFloorZ() >> 4, function() use ($player, $pos){
			PracticeUtil::teleport($player, $pos);
			ItemHandler::giveLeaveItem($player);
			$session = PlayerManager::getSession($player);
			$session->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_EVENT);
			$session->setInHub(false);
		});
		$playerLine = $this->isStarted() ? 1 : 3;
		$playerSb = PracticeCore::COLOR . " Players: " . TextFormat::WHITE . $this->getPlayers(true);
		$msg = PracticeCore::PREFIX . TextFormat::GREEN . $name . TextFormat::GRAY . " has joined the event";
		foreach($this->players as $player){
			if(($player = PlayerManager::getPlayerExact($player, true)) !== null){
				PlayerManager::getSession($player)->getScoreboardInfo()->updateLineOfScoreboard($playerLine, $playerSb);
				$player->sendMessage($msg);
			}
		}
		foreach($this->spectators as $spec){
			if(($spectator = PlayerManager::getPlayerExact($spec, true)) !== null){
				PlayerManager::getSession($spectator)->getScoreboardInfo()->updateLineOfScoreboard($playerLine, $playerSb);
				$spectator->sendMessage($msg);
			}
		}
	}

	public function removePlayer(string $name) : void{
		if(isset($this->players[$name])){
			unset($this->players[$name]);
			unset($this->waiting[$name]);
			unset($this->played[$name]);
			$msg = PracticeCore::PREFIX . TextFormat::RED . $name . TextFormat::GRAY . " has left the event";
			$playerLine = $this->isStarted() ? 1 : 3;
			$playerSb = PracticeCore::COLOR . " Players: " . TextFormat::WHITE . $this->getPlayers(true);
			$eliminated = null;
			if($this->isStarted() && !isset($this->eliminated[$name])){
				$this->eliminated[$name] = count($this->eliminated) + 1;
				$eliminated = PracticeCore::COLOR . " Eliminated: " . TextFormat::WHITE . count($this->eliminated);
			}
			foreach($this->players as $player){
				if(($player = PlayerManager::getPlayerExact($player, true)) !== null){
					$sbInfo = PlayerManager::getSession($player)->getScoreboardInfo();
					$sbInfo->updateLineOfScoreboard($playerLine, $playerSb);
					if($eliminated !== null){
						$sbInfo->updateLineOfScoreboard(4, $eliminated);
					}
					$player->sendMessage($msg);
				}
			}
			foreach($this->spectators as $spec){
				if(($spectator = PlayerManager::getPlayerExact($spec, true)) !== null){
					$sbInfo = PlayerManager::getSession($spectator)->getScoreboardInfo();
					$sbInfo->updateLineOfScoreboard($playerLine, $playerSb);
					if($eliminated !== null){
						$sbInfo->updateLineOfScoreboard(4, $eliminated);
					}
					$spectator->sendMessage($msg);
				}
			}
			if(($player = PlayerManager::getPlayerExact($name, true)) !== null){
				$player->sendMessage($msg);
				PlayerManager::getSession($player)->reset();
			}
		}
	}

	public function addSpectator(Player $player) : void{
		if($this->status !== self::STATUS_ENDING && $player->isOnline()){
			$this->spectators[$name = $player->getDisplayName()] = $name;
			/** @var EventArena $arena */
			$arena = ArenaManager::getArena($this->arena);
			$pos = Position::fromObject($arena->getSpecSpawn(), $arena->getWorld());
			PracticeUtil::onChunkGenerated($pos->getWorld(), $pos->getFloorX() >> 4, $pos->getFloorZ() >> 4, function() use ($player, $pos){
				PracticeUtil::teleport($player, $pos);
				ItemHandler::giveLeaveItem($player);
				$session = PlayerManager::getSession($player);
				$session->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_EVENT);
				$session->setInHub(false);
			});
			if(isset($this->players[$name])){
				unset($this->players[$name]);
				unset($this->waiting[$name]);
				unset($this->played[$name]);
			}else{
				$msg = PracticeCore::PREFIX . TextFormat::GREEN . $name . TextFormat::GRAY . " is now spectating the duel";
				foreach($this->players as $player){
					PlayerManager::getPlayerExact($player, true)?->sendMessage($msg);
				}
				foreach($this->spectators as $spec){
					PlayerManager::getPlayerExact($spec, true)?->sendMessage($msg);
				}
			}
		}
	}

	public function removeSpectator(string $name) : void{
		if(isset($this->spectators[$name])){
			PlayerManager::getSession(PlayerManager::getPlayerExact($name, true))?->reset();
			$msg = PracticeCore::PREFIX . TextFormat::RED . $name . TextFormat::GRAY . " is no longer spectating the event";
			foreach($this->players as $player){
				PlayerManager::getPlayerExact($player, true)?->sendMessage($msg);
			}
			foreach($this->spectators as $spec){
				PlayerManager::getPlayerExact($spec, true)?->sendMessage($msg);
			}
			unset($this->spectators[$name]);
		}
	}

	public function sendFinalMessage(Player $playerToSendMessage) : void{
		$place = "None";
		if(isset($this->eliminated[$name = $playerToSendMessage->getDisplayName()])){
			$place = (count($this->eliminated) - $this->eliminated[$name]) + 1;
		}
		if(PracticeCore::isPackEnable()){
			$finalMessage = "\n";
			$finalMessage .= " " . PracticeUtil::formatUnicodeKit(str_replace("Event", "", ArenaManager::getArena($this->getArena())->getKit()->getName())) . TextFormat::BOLD . TextFormat::WHITE . " Event Summary" . TextFormat::RESET . "\n";
			$finalMessage .= TextFormat::GREEN . "  Winner: " . TextFormat::GRAY . (($this->winner === null) ? "None" : $this->winner->getDisplayName()) . "\n";
			$finalMessage .= TextFormat::WHITE . "  Place: " . TextFormat::GRAY . "$place \n";
			$finalMessage .= "\n";
		}else{
			$finalMessage = TextFormat::DARK_GRAY . "--------------------------\n";
			$finalMessage .= TextFormat::GREEN . "Winner: " . TextFormat::WHITE . (($this->winner === null) ? "None" : $this->winner->getDisplayName()) . "\n";
			$finalMessage .= TextFormat::GRAY . "Place: " . TextFormat::WHITE . "$place \n";
			$finalMessage .= TextFormat::DARK_GRAY . "--------------------------\n";
		}
		$playerToSendMessage->sendMessage($finalMessage);
	}

	public function setEnded() : void{
		foreach($this->players as $player){
			PlayerManager::getSession(PlayerManager::getPlayerExact($player, true))?->reset();
		}
		foreach($this->spectators as $spec){
			PlayerManager::getSession(PlayerManager::getPlayerExact($spec, true))?->reset();
		}
	}

	public function isPlayer(string|Player $player) : bool{
		return isset($this->players[$player instanceof Player ? $player->getDisplayName() : $player]);
	}

	public function isInGame(string|Player $player) : bool{
		if($this->currentGame === null){
			return false;
		}
		return $this->currentGame->isPlayer($player);
	}

	public function isSpectator(string|Player $player) : bool{
		return isset($this->spectators[$player instanceof Player ? $player->getDisplayName() : $player]);
	}

	public function isStarted() : bool{
		return $this->status === self::STATUS_IN_PROGRESS || $this->status === self::STATUS_ENDING;
	}

	public function isOpen() : bool{
		return $this->status === self::STATUS_WAITING;
	}

	public function open() : void{
		$this->status = self::STATUS_WAITING;
	}

	public function getPlayers(bool $asInt = false) : int|array{
		return $asInt ? count($this->players) : $this->players;
	}

	public function getEliminated(bool $asInt = false) : int|array{
		return $asInt ? count($this->eliminated) : $this->eliminated;
	}

	public function getCurrentGame() : ?EventDuel{
		return $this->currentGame;
	}

	public function getArena() : string{
		return $this->arena;
	}

	public function getCountdown() : string{
		$seconds = $this->countdownSeconds % 60;
		$minutes = (int) ($this->countdownSeconds / 60);
		return ($minutes < 10 ? "0" . $minutes : $minutes) . ":" . ($seconds < 10 ? "0" . $seconds : $seconds);
	}
}
