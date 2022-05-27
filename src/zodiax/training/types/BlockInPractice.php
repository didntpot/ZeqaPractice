<?php

declare(strict_types=1);

namespace zodiax\training\types;

use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\VanillaItems;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\sound\ClickSound;
use pocketmine\world\sound\ExplodeSound;
use pocketmine\world\sound\XpCollectSound;
use pocketmine\world\World;
use zodiax\arena\ArenaManager;
use zodiax\arena\types\BlockInArena;
use zodiax\game\items\ItemHandler;
use zodiax\game\world\BlockRemoverHandler;
use zodiax\game\world\thread\ChunkCache;
use zodiax\kits\DefaultKit;
use zodiax\party\duel\misc\PracticeTeam;
use zodiax\player\info\scoreboard\ScoreboardInfo;
use zodiax\player\misc\VanishHandler;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\training\misc\DefenseGenerator;
use zodiax\training\TrainingHandler;
use function array_search;
use function array_values;
use function count;
use function in_array;
use function min;
use function number_format;

class BlockInPractice{

	const STATUS_PREPARING = 0;
	const STATUS_STARTING = 1;
	const STATUS_IN_PROGRESS = 2;
	const STATUS_ENDING = 3;
	const STATUS_ENDED = 4;

	private int $status;
	private int $currentTick;
	private int $countdownSeconds;
	private string $kit;
	private string $arena;
	private int $worldId;
	private ?World $world;
	private ?Position $centerPosition;
	private string $ownerName;
	private ?PracticeTeam $attackersTeam;
	private ?PracticeTeam $defendersTeam;
	private array $spectators;
	private array $blacklisted;
	private array $chunks;
	private array $deathsCountdown;
	private int $startTime;
	private float $bestTime;
	private int $blockInEntityId;
	private int $defenseType;
	private array $defenseBlocks;
	private array $armorSettings;
	private string $archerName;
	private bool $open;

	public function __construct(int $worldId, Player $player, DefaultKit $kit, BlockInArena $arena){
		$this->status = self::STATUS_PREPARING;
		$this->currentTick = 0;
		$this->countdownSeconds = 3;
		$this->kit = $kit->getName();
		$this->arena = $arena->getName();
		$this->worldId = $worldId;
		/** @var BlockInArena $arena */
		$arena = ArenaManager::getArena($this->arena);
		$this->world = ArenaManager::MAPS_MODE === ArenaManager::ADVANCE ? $arena->getWorld(true) : Server::getInstance()->getWorldManager()->getWorldByName("duel" . $this->worldId);
		$spawnPos1 = $arena->getP1Spawn();
		$spawnPos2 = $arena->getP2Spawn();
		$this->centerPosition = new Position((int) (($spawnPos2->getX() + $spawnPos1->getX()) / 2), $spawnPos1->getY(), (int) (($spawnPos2->getZ() + $spawnPos1->getZ()) / 2), $this->world);
		$this->ownerName = $player->getName();
		$this->attackersTeam = new PracticeTeam(TextFormat::BLUE);
		$this->defendersTeam = new PracticeTeam(TextFormat::RED);
		$this->spectators = [];
		$this->blacklisted = [];
		$this->chunks = [];
		$this->deathsCountdown = [];
		$this->startTime = 0;
		$this->bestTime = 0.00;
		$this->blockInEntityId = 0;
		$this->defenseType = DefenseGenerator::ONE_LAYER;
		$this->defenseBlocks = [VanillaBlocks::WOOL(), null, null, null]; // first, second, third, layer
		$this->armorSettings = [[VanillaItems::CHAINMAIL_HELMET(), VanillaItems::CHAINMAIL_CHESTPLATE(), VanillaItems::CHAINMAIL_LEGGINGS(), VanillaItems::CHAINMAIL_BOOTS()], [VanillaItems::CHAINMAIL_HELMET(), VanillaItems::CHAINMAIL_CHESTPLATE(), VanillaItems::CHAINMAIL_LEGGINGS(), VanillaItems::CHAINMAIL_BOOTS()]]; // [attackers, defenders]
		$this->archerName = "";
		$this->open = false;
		$this->resetBlockIn();
	}

	public function update() : void{
		if($this->currentTick++ < 10){
			return;
		}
		if(PlayerManager::getPlayerExact($this->ownerName) === null){
			if(!$this->randomOwnership()){
				$this->setEnded();
				return;
			}
			if($this->status === self::STATUS_IN_PROGRESS){
				$this->resetBlockIn();
				return;
			}
		}
		$attackerSessions = [];
		foreach($this->attackersTeam->getPlayers() as $attacker){
			if(($aSession = PlayerManager::getSession(PlayerManager::getPlayerExact($attacker))) === null || $aSession->isInHub()){
				$this->attackersTeam->removeFromTeam($attacker, false);
			}else{
				$attackerSessions[$attacker] = $aSession;
			}
		}
		if(count($attackerSessions) === 0){
			if(!$this->replaceLastAttacker()){
				$this->setEnded();
				return;
			}
			if($this->status === self::STATUS_STARTING || $this->status === self::STATUS_IN_PROGRESS){
				$this->resetBlockIn();
				return;
			}
		}
		$defenderSessions = [];
		foreach($this->defendersTeam->getPlayers() as $defender){
			if(($dSession = PlayerManager::getSession(PlayerManager::getPlayerExact($defender))) === null || $dSession->isInHub()){
				$this->defendersTeam->removeFromTeam($defender, false);
				unset($this->deathsCountdown[$defender]);
			}else{
				$defenderSessions[$defender] = $dSession;
			}
		}
		switch($this->status){
			case self::STATUS_STARTING:
				if($this->currentTick % 20 === 0){
					if($this->countdownSeconds === 3){
						$this->setInBlockIn();
						$title = TextFormat::RED . "Starting in 3";
						$clickSound = new ClickSound();
						foreach($attackerSessions as $aSession){
							$attacker = $aSession->getPlayer();
							$attacker->sendTitle($title, "", 5, 20, 5);
							$attacker->broadcastSound($clickSound, [$attacker]);
						}
						foreach($defenderSessions as $dSession){
							$defender = $dSession->getPlayer();
							$defender->sendTitle($title, "", 5, 20, 5);
							$defender->broadcastSound($clickSound, [$defender]);
						}
					}elseif($this->countdownSeconds > 0 && $this->countdownSeconds < 5){
						$title = TextFormat::RED . $this->countdownSeconds . "...";
						$clickSound = new ClickSound();
						foreach($attackerSessions as $aSession){
							$attacker = $aSession->getPlayer();
							$attacker->sendTitle($title, "", 5, 20, 5);
							$attacker->broadcastSound($clickSound, [$attacker]);
						}
						foreach($defenderSessions as $dSession){
							$defender = $dSession->getPlayer();
							$defender->sendTitle($title, "", 5, 20, 5);
							$defender->broadcastSound($clickSound, [$defender]);
						}
					}elseif($this->countdownSeconds === 0){
						$title = TextFormat::RED . "Fight!";
						$xpSound = new XpCollectSound();
						foreach($attackerSessions as $aSession){
							$attacker = $aSession->getPlayer();
							$attacker->sendTitle($title, "", 5, 20, 5);
							$attacker->broadcastSound($xpSound, [$attacker]);
							if(!$aSession->isFrozen()){
								$attacker->setImmobile(false);
							}
						}
						foreach($defenderSessions as $dSession){
							$defender = $dSession->getPlayer();
							$defender->sendTitle($title, "", 5, 20, 5);
							$defender->broadcastSound($xpSound, [$defender]);
						}
						$this->status = self::STATUS_IN_PROGRESS;
						$this->startTime = $this->currentTick;
						$this->countdownSeconds = 3;
					}
					$this->countdownSeconds--;
				}
				break;
			case self::STATUS_IN_PROGRESS:
				$time = PracticeCore::COLOR . " Time: " . TextFormat::WHITE . number_format((($this->currentTick - $this->startTime) / 20), 2) . " Second(s)";
				foreach($attackerSessions as $aSession){
					$aSession->getClicksInfo()->update();
					$aSession->getScoreboardInfo()->updateLineOfScoreboard(5, $time);
				}
				foreach($defenderSessions as $dSession){
					$dSession->getClicksInfo()->update();
					$dSession->getScoreboardInfo()->updateLineOfScoreboard(5, $time);
				}
				foreach($this->spectators as $spec){
					$spectator = PlayerManager::getPlayerExact($spec, true);
					if($spectator !== null){
						PlayerManager::getSession($spectator)->getScoreboardInfo()->updateLineOfScoreboard(4, $time);
					}else{
						$this->removeSpectator($spec);
					}
				}
				if($this->currentTick % 20 === 0){
					foreach($defenderSessions as $defenderName => $dSession){
						if(isset($this->deathsCountdown[$defenderName])){
							$defender = $dSession->getPlayer();
							if($defender !== null){
								if($this->deathsCountdown[$defenderName] > 0){
									$this->deathsCountdown[$defenderName]--;
									if($this->deathsCountdown[$defenderName] !== 0){
										$defender->sendTitle(PracticeCore::COLOR . $this->deathsCountdown[$defenderName], "", 5, 20, 5);
									}else{
										/** @var BlockInArena $arena */
										$arena = ArenaManager::getArena($this->arena);
										$defender->setGamemode(GameMode::SURVIVAL());
										PracticeUtil::teleport($defender, $arena->getP2Spawn(), $arena->getCoreSpawn());
										$dSession->getKitHolder()->setKit($arena->getDefenderKit());
										$this->adaptKitItems($defender);
									}
								}
							}else{
								$this->defendersTeam->removeFromTeam($defenderName, false);
								unset($this->deathsCountdown[$defenderName]);
							}
						}
					}
				}
				break;
			case self::STATUS_ENDING:
				if($this->currentTick % 20 === 0 && --$this->countdownSeconds === 0){
					$this->resetBlockIn();
				}
				break;
		}
	}

	private function setInBlockIn() : void{
		/** @var BlockInArena $arena */
		$arena = ArenaManager::getArena($this->arena);
		$attackerPos = Position::fromObject($arena->getP1Spawn(), $this->world);
		$attackerKit = $arena->getAttackerKit();
		$defenderPos = Position::fromObject($arena->getP2Spawn(), $this->world);
		$defenderKit = $arena->getDefenderKit();
		$corePos = $arena->getCoreSpawn();
		$time = PracticeCore::COLOR . " Time: " . TextFormat::WHITE . "0.00 Second(s)";
		$best = PracticeCore::COLOR . " Best: " . TextFormat::WHITE . number_format($this->bestTime, 2) . " Second(s)";
		foreach($this->attackersTeam->getPlayers() as $attacker){
			if(($aSession = PlayerManager::getSession($attacker = PlayerManager::getPlayerExact($attacker))) !== null){
				$attacker->setImmobile();
				$attacker->setLastDamageCause(new EntityDamageEvent($attacker, EntityDamageEvent::CAUSE_MAGIC, 0));
				PracticeUtil::onChunkGenerated($this->world, $attackerPos->getFloorX() >> 4, $attackerPos->getFloorZ() >> 4, function() use ($attacker, $aSession, $attackerPos, $attackerKit, $corePos, $best){
					PracticeUtil::teleport($attacker, $attackerPos, $corePos);
					$aSession->getKitHolder()->setKit($attackerKit);
					$this->adaptKitItems($attacker);
					$sbInfo = $aSession->getScoreboardInfo();
					$sbInfo->setScoreboard(ScoreboardInfo::SCOREBOARD_DUEL);
					$sbInfo->updateLineOfScoreboard(6, $best);
				});
			}
		}
		foreach($this->defendersTeam->getPlayers() as $defender){
			if(($dSession = PlayerManager::getSession($defender = PlayerManager::getPlayerExact($defender))) !== null){
				$defender->setLastDamageCause(new EntityDamageEvent($defender, EntityDamageEvent::CAUSE_MAGIC, 0));
				PracticeUtil::onChunkGenerated($this->world, $defenderPos->getFloorX() >> 4, $defenderPos->getFloorZ() >> 4, function() use ($defender, $dSession, $defenderPos, $defenderKit, $corePos, $best){
					PracticeUtil::teleport($defender, $defenderPos, $corePos);
					$dSession->getKitHolder()->setKit($defenderKit);
					$this->adaptKitItems($defender);
					$sbInfo = $dSession->getScoreboardInfo();
					$sbInfo->setScoreboard(ScoreboardInfo::SCOREBOARD_DUEL);
					$sbInfo->updateLineOfScoreboard(6, $best);
				});
			}
		}
		foreach($this->spectators as $spec){
			$spectator = PlayerManager::getPlayerExact($spec, true);
			if($spectator !== null){
				$sbInfo = PlayerManager::getSession($spectator)->getScoreboardInfo();
				$sbInfo->updateLineOfScoreboard(4, $time);
				$sbInfo->updateLineOfScoreboard(5, $best);
			}else{
				$this->removeSpectator($spec);
			}
		}
	}

	public function setAsStarting() : void{
		if($this->status === self::STATUS_PREPARING){
			$this->status = self::STATUS_STARTING;
		}
	}

	public function onCoreBreak(Player $player) : void{
		if($this->status === self::STATUS_IN_PROGRESS && $this->getTeam($player) === $this->attackersTeam && PlayerManager::getSession($player)?->getKitHolder()->hasKit()){
			$this->status = self::STATUS_ENDING;
			TrainingHandler::removeBlockInEntity($this->blockInEntityId);
			$time = (($this->currentTick - $this->startTime) / 20);
			$this->bestTime = ($this->bestTime === 0.00) ? $time : min($this->bestTime, $time);
			$time = number_format($time, 2);
			$subtitle = PracticeCore::COLOR . $time . " Second(s)";
			$best = PracticeCore::COLOR . " Best: " . TextFormat::WHITE . number_format($this->bestTime, 2) . " Second(s)";
			$explodeSound = new ExplodeSound();
			foreach($this->attackersTeam->getPlayers() as $attacker){
				if(($aSession = PlayerManager::getSession($attacker = PlayerManager::getPlayerExact($attacker))) !== null){
					$attacker->sendTitle(TextFormat::RESET, $subtitle, 20, 40, 20);
					$attacker->broadcastSound($explodeSound, [$attacker]);
					$aSession->getScoreboardInfo()->updateLineOfScoreboard(6, $best);
				}
			}
			foreach($this->defendersTeam->getPlayers() as $defender){
				if(($dSession = PlayerManager::getSession($defender = PlayerManager::getPlayerExact($defender))) !== null){
					$defender->sendTitle(TextFormat::RESET, $subtitle, 20, 40, 20);
					$defender->broadcastSound($explodeSound, [$defender]);
					$dSession->getScoreboardInfo()->updateLineOfScoreboard(6, $best);
				}
			}
			foreach($this->spectators as $spec){
				$spectator = PlayerManager::getPlayerExact($spec, true);
				if($spectator !== null){
					$spectator->sendTitle(TextFormat::RESET, $subtitle, 20, 40, 20);
					$spectator->broadcastSound($explodeSound, [$spectator]);
					PlayerManager::getSession($spectator)->getScoreboardInfo()->updateLineOfScoreboard(5, $best);
				}else{
					$this->removeSpectator($spec);
				}
			}
		}
	}

	public function resetBlockIn() : void{
		$this->status = self::STATUS_PREPARING;
		$this->countdownSeconds = 3;
		/** @var BlockInArena $arena */
		$arena = ArenaManager::getArena($this->arena);
		if($this->world instanceof World){
			BlockRemoverHandler::removeBlocks($this->world, $this->chunks);
			$this->chunks = [];
			DefenseGenerator::generateDefenseByType($this->defenseType, Position::fromObject($arena->getCoreSpawn(), $this->world), $this->defenseBlocks[0], $this->defenseBlocks[1], $this->defenseBlocks[2], $this->defenseBlocks[3]);
			$this->blockInEntityId = TrainingHandler::createBlockInEntity($this);
			$blockInEntity = TrainingHandler::getBlockInEntityfromEntityId($this->blockInEntityId);
			foreach($this->attackersTeam->getPlayers() as $attacker){
				if(($attacker = PlayerManager::getPlayerExact($attacker)) !== null){
					$blockInEntity->spawnTo($attacker);
					PracticeUtil::teleport($attacker, $arena->getP1Spawn(), $arena->getCoreSpawn());
					VanishHandler::removeFromVanish($attacker);
					$aSession = PlayerManager::getSession($attacker);
					$aSession->getKitHolder()->clearKit();
					$aSession->updateNameTag();
					ItemHandler::spawnBlockInItems($attacker);
				}
			}
			foreach($this->defendersTeam->getPlayers() as $defender){
				if(($defender = PlayerManager::getPlayerExact($defender)) !== null){
					$blockInEntity->spawnTo($defender);
					$this->deathsCountdown[$defender->getName()] = 0;
					$defender->setGamemode(GameMode::SURVIVAL());
					PracticeUtil::teleport($defender, $arena->getP2Spawn(), $arena->getCoreSpawn());
					$dSession = PlayerManager::getSession($defender);
					$dSession->getKitHolder()->clearKit();
					$dSession->updateNameTag();
					ItemHandler::spawnBlockInItems($defender);
				}
			}
			foreach($this->spectators as $spec){
				if(($spectator = PlayerManager::getPlayerExact($spec, true)) !== null){
					$blockInEntity->spawnTo($spectator);
				}
			}
		}
	}

	public function setDefenseType(int $type, Block $first, ?Block $second = null, ?Block $third = null, ?Block $layer = null) : void{
		$this->defenseType = $type;
		$this->defenseBlocks = [$first, $second, $third, $layer];
		$msg = PracticeCore::COLOR . " Defense: " . TextFormat::WHITE . $this->getDefenseType(true);
		foreach($this->attackersTeam->getPlayers() as $attacker){
			PlayerManager::getSession(PlayerManager::getPlayerExact($attacker))?->getScoreboardInfo()->updateLineOfScoreboard(3, $msg);
		}
		foreach($this->defendersTeam->getPlayers() as $defender){
			PlayerManager::getSession(PlayerManager::getPlayerExact($defender))?->getScoreboardInfo()->updateLineOfScoreboard(3, $msg);
		}
		foreach($this->spectators as $spec){
			$spectator = PlayerManager::getPlayerExact($spec, true);
			if($spectator !== null){
				PlayerManager::getSession($spectator)->getScoreboardInfo()->updateLineOfScoreboard(2, $msg);
			}else{
				$this->removeSpectator($spec);
			}
		}
		$this->resetBlockIn();
	}

	public function getArmorSettings() : array{
		return $this->armorSettings;
	}

	public function setArmorSettings(array $attackers, array $defenders) : void{
		$this->armorSettings = [$attackers, $defenders];
	}

	public function setEnded() : void{
		$this->status = self::STATUS_ENDED;
		TrainingHandler::removeBlockInEntity($this->blockInEntityId);
		foreach($this->attackersTeam->getPlayers() as $attacker){
			$this->attackersTeam->removeFromTeam($attacker);
			if(($attacker = PlayerManager::getPlayerExact($attacker)) !== null){
				$aSession = PlayerManager::getSession($attacker);
				$aSession->reset();
				$aSession->updateNameTag();
			}
		}
		foreach($this->defendersTeam->getPlayers() as $defender){
			$this->defendersTeam->removeFromTeam($defender);
			if(($defender = PlayerManager::getPlayerExact($defender)) !== null){
				$dSession = PlayerManager::getSession($defender);
				$dSession->reset();
				$dSession->updateNameTag();
			}
		}
		foreach($this->spectators as $spec){
			if(($spectator = PlayerManager::getPlayerExact($spec, true)) !== null){
				PlayerManager::getSession($spectator)->reset();
			}
		}
		if($this->world instanceof World){
			if(ArenaManager::MAPS_MODE !== ArenaManager::NORMAL){
				BlockRemoverHandler::removeBlocks($this->world, $this->chunks);
			}
			DefenseGenerator::clear(Position::fromObject(ArenaManager::getArena($this->arena)->getCoreSpawn(), $this->world));
		}
		TrainingHandler::removeBlockIn($this->worldId);
	}

	public function transferOwnership(Player $player) : void{
		$oldOwnerName = $this->ownerName;
		$this->ownerName = $player->getName();
		$oldOwner = PlayerManager::getPlayerExact($oldOwnerName);
		$player->sendMessage(PracticeCore::PREFIX . TextFormat::YELLOW . ($oldOwner?->getDisplayName() ?? $oldOwnerName) . TextFormat::GRAY . " has transferred ownership to you");
		if($this->status === self::STATUS_PREPARING){
			ItemHandler::spawnBlockInItems($player);
			if($oldOwner !== null){
				PlayerManager::getSession($oldOwner)->getExtensions()->clearAll();
				ItemHandler::spawnBlockInItems($oldOwner);
			}
		}
	}

	public function randomOwnership() : bool{
		foreach($this->attackersTeam->getPlayers() as $attacker){
			$oSession = PlayerManager::getSession($owner = PlayerManager::getPlayerExact($attacker));
			if($oSession === null || $oSession->isInHub()){
				$this->attackersTeam->removeFromTeam($attacker, false);
			}else{
				$this->transferOwnership($owner);
				return true;
			}
		}
		foreach($this->defendersTeam->getPlayers() as $defender){
			$oSession = PlayerManager::getSession($owner = PlayerManager::getPlayerExact($defender));
			$this->defendersTeam->removeFromTeam($defender, false);
			unset($this->deathsCountdown[$defender]);
			if($oSession === null || $oSession->isInHub()){
				if(count($this->defendersTeam->getPlayers()) <= 0){
					return false;
				}
			}else{
				$this->attackersTeam->addToTeam($owner);
				$owner->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "You are now " . TextFormat::GREEN . "Attacker");
				$oSession->getScoreboardInfo()->updateLineOfScoreboard(2, PracticeCore::COLOR . " Role: " . TextFormat::WHITE . "Attacker");
				$oSession->updateNameTag();
				$this->transferOwnership($owner);
				return true;
			}
		}
		return false;
	}

	public function swapRoles(array $data, array $roles) : void{
		$i = 0;
		$attackers = 0;
		$archer = 0;
		$changes = [];
		foreach($roles as $player => $value){
			if($this->isPlayer($player) && isset($data[$i])){
				if($data[$i] === 0){
					$attackers++;
				}elseif($data[$i] === 2){
					$archer++;
				}
				if($data[$i] !== $value){
					$changes[$player] = $data[$i];
				}
			}
			$i++;
		}
		$owner = PlayerManager::getPlayerExact($this->ownerName);
		if($attackers === 0){
			$owner?->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "Need at least " . TextFormat::RED . "an attacker");
			return;
		}
		if($archer > 1){
			$owner?->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "You can only set" . TextFormat::RED . "a bow defender");
			return;
		}
		foreach($changes as $player => $value){
			if(($pSession = PlayerManager::getSession($player = PlayerManager::getPlayerExact($player))) !== null){
				if($value === 0){
					unset($this->deathsCountdown[$player->getName()]);
					$this->defendersTeam->removeFromTeam($player);
					$this->attackersTeam->addToTeam($player);
				}else{
					$this->attackersTeam->removeFromTeam($player);
					$this->defendersTeam->addToTeam($player);
					$this->deathsCountdown[$player->getName()] = 0;
					if($value === 2){
						$this->archerName = $player->getName();
					}
				}
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "You are now " . TextFormat::GREEN . ($value === 0 ? "Attacker" : "Defender"));
				$pSession->getScoreboardInfo()->updateLineOfScoreboard(2, PracticeCore::COLOR . " Role: " . TextFormat::WHITE . ($value === 0 ? "Attacker" : "Defender"));
				$pSession->updateNameTag();
			}
		}
		$owner?->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully swapped roles");
	}

	private function replaceLastAttacker() : bool{
		if(count($this->defendersTeam->getPlayers()) > 0){
			foreach($this->defendersTeam->getPlayers() as $defender){
				$dSession = PlayerManager::getSession($defender = PlayerManager::getPlayerExact($defender));
				$this->defendersTeam->removeFromTeam($defender, false);
				unset($this->deathsCountdown[$defender->getName()]);
				if($dSession === null || $dSession->isInHub()){
					if(count($this->defendersTeam->getPlayers()) <= 0){
						return false;
					}
				}else{
					$this->attackersTeam->addToTeam($defender);
					$defender->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "You are now " . TextFormat::GREEN . "Attacker");
					$dSession->getScoreboardInfo()->updateLineOfScoreboard(2, PracticeCore::COLOR . " Role: " . TextFormat::WHITE . "Attacker");
					$dSession->updateNameTag();
					return true;
				}
			}
		}
		return false;
	}

	public function addPlayer(Player $player) : void{
		if($this->status !== self::STATUS_ENDED){
			$targetTeam = ((count($this->attackersTeam->getPlayers()) === 0) ? $this->attackersTeam : ((count($this->attackersTeam->getPlayers()) >= count($this->defendersTeam->getPlayers())) ? $this->defendersTeam : $this->attackersTeam));
			$isAttacker = $targetTeam === $this->attackersTeam;
			/** @var BlockInArena $arena */
			$arena = ArenaManager::getArena($this->arena);
			$spawnPos = Position::fromObject($isAttacker ? $arena->getP1Spawn() : $arena->getP2Spawn(), $this->world);
			$corePos = $arena->getCoreSpawn();
			if(($pSession = PlayerManager::getSession($player)) !== null){
				$pSession->getExtensions()->clearAll();
				$pSession->setInHub(false);
				$targetTeam->addToTeam($player);
				if($isAttacker){
					if($this->status === self::STATUS_STARTING || $this->status === self::STATUS_IN_PROGRESS){
						VanishHandler::addToVanish($player);
					}
				}else{
					$this->deathsCountdown[$player->getName()] = 0;
					if($this->status === self::STATUS_STARTING || $this->status === self::STATUS_IN_PROGRESS){
						$this->deathsCountdown[$player->getName()] = 4;
						$player->setGamemode(GameMode::SPECTATOR());
					}
				}
			}
			PracticeUtil::onChunkGenerated($this->world, $spawnPos->getFloorX() >> 4, $spawnPos->getFloorZ() >> 4, function() use ($pSession, $targetTeam, $spawnPos, $corePos){
				if(($player = $pSession->getPlayer()) !== null){
					PracticeUtil::teleport($player, $spawnPos, $corePos);
					if($this->status === self::STATUS_PREPARING){
						ItemHandler::spawnBlockInItems($player);
					}
					$sbInfo = $pSession->getScoreboardInfo();
					$sbInfo->setScoreboard(ScoreboardInfo::SCOREBOARD_DUEL);
					$sbInfo->updateLineOfScoreboard(6, PracticeCore::COLOR . " Best: " . TextFormat::WHITE . number_format($this->bestTime, 2) . " Second(s)");
					$pSession->updateNameTag();
					TrainingHandler::getBlockInEntityfromEntityId($this->blockInEntityId)?->spawnTo($player);
				}
			});
			$msg = PracticeCore::PREFIX . TextFormat::GREEN . $player->getDisplayName() . TextFormat::GRAY . " joined as " . ($isAttacker ? "Attacker" : "Defender");
			foreach($this->attackersTeam->getPlayers() as $attacker){
				PlayerManager::getPlayerExact($attacker)?->sendMessage($msg);
			}
			foreach($this->defendersTeam->getPlayers() as $defender){
				PlayerManager::getPlayerExact($defender)?->sendMessage($msg);
			}
			foreach($this->spectators as $spec){
				PlayerManager::getPlayerExact($spec, true)?->sendMessage($msg);
			}
		}
	}

	public function removePlayer(Player $player, bool $blacklist = false) : void{
		$team = $this->getTeam($player);
		if($team instanceof PracticeTeam){
			$msg = PracticeCore::PREFIX . TextFormat::RED . $player->getDisplayName() . TextFormat::GRAY . " left";
			foreach($this->attackersTeam->getPlayers() as $attacker){
				PlayerManager::getPlayerExact($attacker)?->sendMessage($msg);
			}
			foreach($this->defendersTeam->getPlayers() as $defender){
				PlayerManager::getPlayerExact($defender)?->sendMessage($msg);
			}
			foreach($this->spectators as $spec){
				PlayerManager::getPlayerExact($spec, true)?->sendMessage($msg);
			}
			$team->removeFromTeam($player, false);
			unset($this->deathsCountdown[$player->getName()]);
			if($blacklist){
				$this->addToBlacklist($player);
			}
			if(($pSession = PlayerManager::getSession($player)) !== null){
				$player->setGamemode(GameMode::SURVIVAL());
				$pSession->reset();
				$pSession->updateNameTag();
			}
			if($team === $this->attackersTeam){
				if(count($this->attackersTeam->getPlayers()) === 0 && !$this->replaceLastAttacker()){
					$this->setEnded();
				}
			}elseif($player->getName() === $this->archerName){
				$this->archerName = "";
			}
		}
	}

	public function isPlayer(string|Player $player) : bool{
		return $this->getTeam($player instanceof Player ? $player->getName() : $player) !== null;
	}

	public function addToBlacklist(string|Player $player) : void{
		$this->blacklisted[] = ($player instanceof Player) ? $player->getName() : $player;
	}

	public function removeFromBlacklist(string|Player $player) : void{
		if(in_array($name = ($player instanceof Player) ? $player->getName() : $player, $this->blacklisted, true)){
			unset($this->blacklisted[array_search($name, $this->blacklisted, true)]);
			$this->blacklisted = array_values($this->blacklisted);
		}
	}

	public function isBlackListed(string|Player $player) : bool{
		return in_array(($player instanceof Player ? $player->getName() : $player), $this->blacklisted, true);
	}

	public function getBlacklisted() : array{
		return $this->blacklisted;
	}

	public function addSpectator(Player $player) : void{
		if($this->status !== self::STATUS_ENDED){
			$this->spectators[$name = $player->getDisplayName()] = $name;
			PracticeUtil::onChunkGenerated($this->world, $this->centerPosition->getFloorX() >> 4, $this->centerPosition->getFloorZ() >> 4, function() use ($player){
				PracticeUtil::teleport($player, $this->centerPosition);
				VanishHandler::addToVanish($player);
				ItemHandler::giveSpectatorItem($player);
				$session = PlayerManager::getSession($player);
				$session->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_SPECTATOR);
				$session->setInHub(false);
				TrainingHandler::getBlockInEntityfromEntityId($this->blockInEntityId)?->spawnTo($player);
			});
			$msg = PracticeCore::PREFIX . TextFormat::GREEN . $name . TextFormat::GRAY . " is now spectating";
			foreach($this->attackersTeam->getPlayers() as $attacker){
				PlayerManager::getPlayerExact($attacker)?->sendMessage($msg);
			}
			foreach($this->defendersTeam->getPlayers() as $defender){
				PlayerManager::getPlayerExact($defender)?->sendMessage($msg);
			}
			foreach($this->spectators as $spec){
				PlayerManager::getPlayerExact($spec, true)?->sendMessage($msg);
			}
		}
	}

	public function removeSpectator(string $name) : void{
		if(isset($this->spectators[$name])){
			PlayerManager::getSession(PlayerManager::getPlayerExact($name, true))?->reset();
			$msg = PracticeCore::PREFIX . TextFormat::RED . $name . TextFormat::GRAY . " is no longer spectating";
			foreach($this->attackersTeam->getPlayers() as $attacker){
				PlayerManager::getPlayerExact($attacker)?->sendMessage($msg);
			}
			foreach($this->defendersTeam->getPlayers() as $defender){
				PlayerManager::getPlayerExact($defender)?->sendMessage($msg);
			}
			foreach($this->spectators as $spec){
				PlayerManager::getPlayerExact($spec, true)?->sendMessage($msg);
			}
			unset($this->spectators[$name]);
		}
	}

	public function isSpectator(string|Player $player) : bool{
		return isset($this->spectators[$player instanceof Player ? $player->getDisplayName() : $player]);
	}

	public function adaptKitItems(Player $player) : void{
		if($this->isPlayer($player) && ($team = $this->getTeam($player)) !== null){
			$isAttacker = $team === $this->attackersTeam;
			$player->getArmorInventory()->setContents($this->armorSettings[$isAttacker ? 0 : 1]);
			if(!$isAttacker && $player->getName() === $this->archerName){
				$inv = $player->getInventory();
				$inv->remove($bow = VanillaItems::BOW());
				$inv->addItem($bow);
				$inv->remove($arrow = VanillaItems::ARROW());
				$inv->addItem($arrow->setCount(16));
			}
		}
	}

	public function tryBreakOrPlaceBlock(Player $player, Block $block, bool $break = true) : bool{
		if($this->isPlayer($player) && $this->isRunning()){
			if($break){
				if(!in_array($block->getId(), [BlockLegacyIds::WOOL, BlockLegacyIds::PLANKS, BlockLegacyIds::CONCRETE, BlockLegacyIds::END_STONE], true)){
					return false;
				}
				$player->getInventory()->addItem($block->asItem());
				$pos = $block->getPosition();
				if(!isset($this->chunks[$hash = World::chunkHash($pos->getFloorX() >> 4, $pos->getFloorZ() >> 4)])){
					$this->chunks[$hash] = new ChunkCache();
				}
				$this->chunks[$hash]->removeBlock($block, $pos);
			}else{
				/** @var BlockInArena $arena */
				$arena = ArenaManager::getArena($this->arena);
				$pos = $block->getPosition();
				$core = $arena->getCoreSpawn();
				if($pos->equals($core) || $pos->getFloorY() > $core->getFloorY() + 15){
					return false;
				}
				if(!isset($this->chunks[$hash = World::chunkHash($pos->getFloorX() >> 4, $pos->getFloorZ() >> 4)])){
					$this->chunks[$hash] = new ChunkCache();
				}
				$this->chunks[$hash]->addBlock(VanillaBlocks::AIR(), $pos);
			}
			return true;
		}
		return false;
	}

	public function onAttackerDeath(Player $player) : void{
		if($this->attackersTeam->isInTeam($player)){
			if(($session = PlayerManager::getSession($player)) !== null){
				$session->getKitHolder()->clearKit();
				if($player->getPosition()->getY() <= 0){
					PracticeUtil::teleport($player, $this->centerPosition);
				}
				VanishHandler::addToVanish($player);
			}
			$msg = PracticeCore::PREFIX . TextFormat::BLUE . $player->getDisplayName() . TextFormat::GRAY . " has been eliminated";
			$flag = false;
			foreach($this->attackersTeam->getPlayers() as $attacker){
				if(($aSession = PlayerManager::getSession($attacker = PlayerManager::getPlayerExact($attacker))) !== null){
					$attacker->sendMessage($msg);
					if(!$flag && $aSession->getKitHolder()->hasKit()){
						$flag = true;
					}
				}
			}
			foreach($this->defendersTeam->getPlayers() as $defender){
				PlayerManager::getPlayerExact($defender)?->sendMessage($msg);
			}
			foreach($this->spectators as $spec){
				PlayerManager::getPlayerExact($spec, true)?->sendMessage($msg);
			}
			if(!$flag){
				$this->status = self::STATUS_ENDING;
				TrainingHandler::removeBlockInEntity($this->blockInEntityId);
			}
		}
	}

	public function deathCountdown(string|Player $player) : bool{
		if(isset($this->deathsCountdown[$name = $player instanceof Player ? $player->getName() : $player])){
			if($this->status === self::STATUS_IN_PROGRESS){
				if($this->deathsCountdown[$name] === 0){
					$this->deathsCountdown[$name] = 4;
					PlayerManager::getSession($player)->getKitHolder()->clearKit();
					$player->setGamemode(GameMode::SPECTATOR());
					return true;
				}else{
					PracticeUtil::teleport($player, $this->centerPosition);
				}
			}else{
				/** @var BlockInArena $arena */
				$arena = ArenaManager::getArena($this->arena);
				PracticeUtil::teleport($player, $arena->getP2Spawn(), $arena->getCoreSpawn());
			}
		}
		return false;
	}

	public function getOwner() : string{
		return $this->ownerName;
	}

	public function isOwner(string|Player $player) : bool{
		return ($player instanceof Player ? $player->getName() : $player) === $this->ownerName;
	}

	public function getTeam($player) : ?PracticeTeam{
		return ($this->attackersTeam->isInTeam($player) ? $this->attackersTeam : ($this->defendersTeam->isInTeam($player) ? $this->defendersTeam : null));
	}

	public function getTeam1() : PracticeTeam{
		return $this->attackersTeam;
	}

	public function getTeam2() : PracticeTeam{
		return $this->defendersTeam;
	}

	public function getKit() : string{
		return $this->kit;
	}

	public function getArena() : string{
		return $this->arena;
	}

	public function getCenterPosition() : Position{
		return $this->centerPosition;
	}

	public function getWorld() : ?World{
		return $this->world;
	}

	public function getWorldId() : int{
		return $this->worldId;
	}

	public function getBlockInEntityID() : int{
		return $this->blockInEntityId;
	}

	public function getDefenseType(bool $asString = false) : int|string{
		return $asString ? DefenseGenerator::DEFENSES_LIST[$this->defenseType] : $this->defenseType;
	}

	public function isOpen() : bool{
		return $this->open;
	}

	public function setOpen(bool $open = true) : void{
		$this->open = $open;
	}

	public function isAvailable() : bool{
		return $this->status !== self::STATUS_ENDED;
	}

	public function isRunning() : bool{
		return $this->status === self::STATUS_IN_PROGRESS;
	}

	public function destroyCycles() : void{
		$this->world = null;
		$this->centerPosition = null;
		$this->attackersTeam = null;
		$this->defendersTeam = null;
		$this->spectators = [];
		$this->blacklisted = [];
		$this->chunks = [];
		$this->deathsCountdown = [];
		$this->defenseBlocks = [];
		ArenaManager::getArena($this->arena)?->setPreWorldAsAvailable($this->worldId);
	}
}
