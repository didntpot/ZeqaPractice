<?php

declare(strict_types=1);

namespace zodiax\duel\types;

use pocketmine\block\Bed;
use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\color\Color;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Armor;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\ProjectileItem;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\ClickSound;
use pocketmine\world\sound\XpCollectSound;
use pocketmine\world\World;
use stdClass;
use zodiax\arena\ArenaManager;
use zodiax\arena\types\DuelArena;
use zodiax\discord\DiscordUtil;
use zodiax\duel\DuelHandler;
use zodiax\forms\display\game\duel\ReDuelForm;
use zodiax\game\items\ItemHandler;
use zodiax\game\world\BlockRemoverHandler;
use zodiax\game\world\thread\ChunkCache;
use zodiax\kits\DefaultKit;
use zodiax\kits\KitsManager;
use zodiax\player\info\duel\data\PlayerReplayData;
use zodiax\player\info\duel\data\WorldReplayData;
use zodiax\player\info\duel\DuelInfo;
use zodiax\player\info\duel\ReplayInfo;
use zodiax\player\info\EloInfo;
use zodiax\player\info\scoreboard\ScoreboardInfo;
use zodiax\player\info\StatsInfo;
use zodiax\player\misc\VanishHandler;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\utils\Math;
use function abs;
use function count;
use function in_array;
use function max;
use function rand;
use function str_repeat;
use function str_replace;
use function strtolower;

class PlayerDuel{

	const STATUS_STARTING = 0;
	const STATUS_IN_PROGRESS = 1;
	const STATUS_ENDING = 2;
	const STATUS_ENDED = 3;

	const MAX_DURATION_SECONDS = 600;

	private int $status;
	private int $currentTick;
	private int $countdownSeconds;
	private int $totalCountdown;
	private int $durationSeconds;
	private string $kit;
	private bool $ranked;
	private string $arena;
	private int $worldId;
	private ?World $world;
	private ?Position $centerPosition;
	private ?Player $winner;
	private ?Player $loser;
	private string $player1Name;
	private string $player2Name;
	private string $player1DisplayName;
	private string $player2DisplayName;
	private array $spectators;
	private array $chunks;
	private array $backup;
	private array $blocksRemover;
	private array $eloInfo;
	private array $clientInfo;
	private array $matchData;
	private array $replayData;

	public function __construct(int $worldId, Player $p1, Player $p2, DefaultKit $kit, bool $ranked, DuelArena $arena){
		$this->status = self::STATUS_STARTING;
		$this->currentTick = 0;
		$this->countdownSeconds = 5;
		$this->totalCountdown = 5;
		$this->durationSeconds = 0;
		$this->kit = $kit->getName();
		$this->ranked = $ranked;
		$this->arena = $arena->getName();
		$this->worldId = $worldId;
		/** @var DuelArena $arena */
		$arena = ArenaManager::getArena($this->arena);
		$this->world = ArenaManager::MAPS_MODE === ArenaManager::ADVANCE ? $arena->getWorld(true) : Server::getInstance()->getWorldManager()->getWorldByName("duel" . $this->worldId);
		$spawnPos1 = $arena->getP1Spawn();
		$spawnPos2 = $arena->getP2Spawn();
		$this->centerPosition = new Position((int) (($spawnPos2->getX() + $spawnPos1->getX()) / 2), $spawnPos1->getY(), (int) (($spawnPos2->getZ() + $spawnPos1->getZ()) / 2), $this->world);
		$this->winner = null;
		$this->loser = null;
		$this->player1Name = $p1->getName();
		$this->player2Name = $p2->getName();
		$this->player1DisplayName = $p1->getDisplayName();
		$this->player2DisplayName = $p2->getDisplayName();
		$this->spectators = [];
		$this->chunks = [];
		$this->backup = [];
		$this->blocksRemover = [];
		$p1Session = PlayerManager::getSession($p1);
		$p2Session = PlayerManager::getSession($p2);
		$this->eloInfo = [$this->player1Name => $p1Session->getEloInfo(), $this->player2Name => $p2Session->getEloInfo()];
		$this->clientInfo = [$this->player1Name => $p1Session->getClientInfo(), $this->player2Name => $p2Session->getClientInfo()];
		$this->matchData = [$this->player1Name => ["numHits" => 0, "criticalHits" => 0, "combo" => 0, "longestCombo" => 0, "potsUsed" => 0, "potsHit" => 0, "arrowsUsed" => 0, "arrowsHit" => 0, "rodUsed" => 0, "rodHit" => 0, "placedBlocks" => 0, "brokeBlocks" => 0, "kills" => 0, "deaths" => 0, "deathsCountdown" => ($kit->getName() === "StickFight" ? 5 : 0), "extraScores" => 0, "extraFlag" => true], $this->player2Name => ["numHits" => 0, "criticalHits" => 0, "combo" => 0, "longestCombo" => 0, "potsUsed" => 0, "potsHit" => 0, "arrowsUsed" => 0, "arrowsHit" => 0, "rodUsed" => 0, "rodHit" => 0, "placedBlocks" => 0, "brokeBlocks" => 0, "kills" => 0, "deaths" => 0, "deathsCountdown" => ($kit->getName() === "StickFight" ? 5 : 0), "extraScores" => 0, "extraFlag" => true]];
	}

	public function update() : void{
		$p1Session = PlayerManager::getSession($player1 = PlayerManager::getPlayerExact($this->player1Name));
		$p2Session = PlayerManager::getSession($player2 = PlayerManager::getPlayerExact($this->player2Name));
		if($p1Session === null || $p1Session->isInHub()){
			$this->setEnded($player2);
			$this->endDuel();
		}elseif($p2Session === null || $p2Session->isInHub()){
			$this->setEnded($player1);
			$this->endDuel();
		}
		$this->currentTick++;
		switch($this->status){
			case self::STATUS_STARTING:
				if($this->currentTick % 20 === 0){
					if($this->countdownSeconds === $this->totalCountdown){
						if($this->currentTick === 20){
							$this->setInDuel();
						}
						$msg = TextFormat::RED . "Starting duel in $this->totalCountdown";
						$player1->sendTitle($msg, "", 5, 20, 5);
						$player2->sendTitle($msg, "", 5, 20, 5);
						$clickSound = new ClickSound();
						$player1->broadcastSound($clickSound, [$player1]);
						$player2->broadcastSound($clickSound, [$player2]);
					}elseif($this->countdownSeconds > 0 && $this->countdownSeconds < $this->totalCountdown){
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
				$p1Session->getClicksInfo()->update();
				$p2Session->getClicksInfo()->update();
				if($this->currentTick % 5 === 0){
					switch($this->kit){
						case "Bridge":
						case "BattleRush":
							/** @var DuelArena $arena */
							$arena = ArenaManager::getArena($this->arena);
							if($this->world->getBlock($pos = $player1->getPosition())->getId() === BlockLegacyIds::END_PORTAL){
								if($pos->distance($p2Spawn = $arena->getP2Spawn()) > $pos->distance($p1Spawn = $arena->getP1Spawn())){
									PracticeUtil::teleport($player1, $p1Spawn, $p2Spawn);
									$p1Session->getKitHolder()->setKit($this->kit);
									$this->adaptKitItems($player1);
									$this->saveLongestCombo($player1);
									$this->saveLongestCombo($player2);
								}else{
									$this->matchData[$this->player1Name]["extraScores"]++;
									$msg = TextFormat::BLUE . $player1->getDisplayName() . TextFormat::WHITE . " Scored!\n" . TextFormat::BLUE . $this->matchData[$this->player1Name]["extraScores"] . TextFormat::GRAY . " - " . TextFormat::RED . $this->matchData[$this->player2Name]["extraScores"];
									$player1->sendTitle($msg, "", 5, 20, 5);
									$player2->sendTitle($msg, "", 5, 20, 5);
									$player1->broadcastSound(new XpCollectSound(), [$player1]);
									$blue = TextFormat::BLUE . " [B] " . str_repeat("O", $this->matchData[$this->player1Name]["extraScores"]) . TextFormat::GRAY . str_repeat("O", ($this->kit === "Bridge" ? 5 : 3) - $this->matchData[$this->player1Name]["extraScores"]);
									$red = TextFormat::RED . " [R] " . str_repeat("O", $this->matchData[$this->player2Name]["extraScores"]) . TextFormat::GRAY . str_repeat("O", ($this->kit === "Bridge" ? 5 : 3) - $this->matchData[$this->player2Name]["extraScores"]);
									$p1Session->getScoreboardInfo()->updateLineOfScoreboard(1, $blue);
									$p1Session->getScoreboardInfo()->updateLineOfScoreboard(2, $red);
									$p2Session->getScoreboardInfo()->updateLineOfScoreboard(1, $blue);
									$p2Session->getScoreboardInfo()->updateLineOfScoreboard(2, $red);
									foreach($this->spectators as $spectator){
										if(($specSession = PlayerManager::getSession($spec = PlayerManager::getPlayerExact($spectator, true))) !== null){
											$spec->sendTitle($msg, "", 5, 20, 5);
											$sbInfo = $specSession->getScoreboardInfo();
											$sbInfo->updateLineOfScoreboard(1, $blue);
											$sbInfo->updateLineOfScoreboard(2, $red);
										}
									}
									$this->saveLongestCombo($player1);
									$this->saveLongestCombo($player2);
									if($this->matchData[$this->player1Name]["extraScores"] === ($this->kit === "Bridge" ? 5 : 3)){
										$this->setEnded($player1);
									}else{
										if($this->kit === "BattleRush"){
											BlockRemoverHandler::removeBlocks($this->world, $this->chunks);
											$this->chunks = [];
											$this->blocksRemover = [];
										}
										$this->setInDuel();
										$this->status = self::STATUS_STARTING;
										$this->countdownSeconds = 7;
									}
								}
							}
							if($this->world->getBlock($pos = $player2->getPosition())->getId() === BlockLegacyIds::END_PORTAL && $this->status === self::STATUS_IN_PROGRESS){
								if($pos->distance($p1Spawn = $arena->getP1Spawn()) > $pos->distance($p2Spawn = $arena->getP2Spawn())){
									PracticeUtil::teleport($player2, $p2Spawn, $p1Spawn);
									$p2Session->getKitHolder()->setKit($this->kit);
									$this->adaptKitItems($player2);
									$this->saveLongestCombo($player1);
									$this->saveLongestCombo($player2);
								}else{
									$this->matchData[$this->player2Name]["extraScores"]++;
									$msg = TextFormat::RED . $player2->getDisplayName() . TextFormat::WHITE . " Scored!\n" . TextFormat::RED . $this->matchData[$this->player2Name]["extraScores"] . TextFormat::GRAY . " - " . TextFormat::BLUE . $this->matchData[$this->player1Name]["extraScores"];
									$player1->sendTitle($msg, "", 5, 20, 5);
									$player2->sendTitle($msg, "", 5, 20, 5);
									$player2->broadcastSound(new XpCollectSound(), [$player2]);
									$blue = TextFormat::BLUE . " [B] " . str_repeat("O", $this->matchData[$this->player1Name]["extraScores"]) . TextFormat::GRAY . str_repeat("O", ($this->kit === "Bridge" ? 5 : 3) - $this->matchData[$this->player1Name]["extraScores"]);
									$red = TextFormat::RED . " [R] " . str_repeat("O", $this->matchData[$this->player2Name]["extraScores"]) . TextFormat::GRAY . str_repeat("O", ($this->kit === "Bridge" ? 5 : 3) - $this->matchData[$this->player2Name]["extraScores"]);
									$p1Session->getScoreboardInfo()->updateLineOfScoreboard(1, $blue);
									$p1Session->getScoreboardInfo()->updateLineOfScoreboard(2, $red);
									$p2Session->getScoreboardInfo()->updateLineOfScoreboard(1, $blue);
									$p2Session->getScoreboardInfo()->updateLineOfScoreboard(2, $red);
									foreach($this->spectators as $spectator){
										if(($specSession = PlayerManager::getSession($spec = PlayerManager::getPlayerExact($spectator, true))) !== null){
											$spec->sendTitle($msg, "", 5, 20, 5);
											$sbInfo = $specSession->getScoreboardInfo();
											$sbInfo->updateLineOfScoreboard(1, $blue);
											$sbInfo->updateLineOfScoreboard(2, $red);
										}
									}
									$this->saveLongestCombo($player1);
									$this->saveLongestCombo($player2);
									if($this->matchData[$this->player2Name]["extraScores"] === ($this->kit === "Bridge" ? 5 : 3)){
										$this->setEnded($player2);
									}else{
										if($this->kit === "BattleRush"){
											BlockRemoverHandler::removeBlocks($this->world, $this->chunks);
											$this->chunks = [];
											$this->blocksRemover = [];
										}
										$this->setInDuel();
										$this->status = self::STATUS_STARTING;
										$this->countdownSeconds = 7;
									}
								}
							}
							break;
						case "Sumo":
							/** @var DuelArena $arena */
							$arena = ArenaManager::getArena($this->arena);
							$minY = $arena->getP1Spawn()->getFloorY() - 2;
							if($player1->getPosition()->getFloorY() < $minY){
								$p1Session->onDeath();
							}elseif($player2->getPosition()->getFloorY() < $minY){
								$p2Session->onDeath();
							}
							break;
					}
					if($this->currentTick % 20 === 0){
						switch($this->kit){
							case "BedFight":
								/** @var DuelArena $arena */
								$arena = ArenaManager::getArena($this->arena);
								if($this->matchData[$this->player1Name]["deathsCountdown"] > 0){
									$this->matchData[$this->player1Name]["deathsCountdown"]--;
									if($this->matchData[$this->player1Name]["deathsCountdown"] !== 0){
										$player1->sendTitle(TextFormat::BLUE . $this->matchData[$this->player1Name]["deathsCountdown"], "", 5, 20, 5);
									}else{
										$player1->setGamemode(GameMode::SURVIVAL());
										PracticeUtil::teleport($player1, $arena->getP1Spawn(), $arena->getP2Spawn());
										$p1Session->getKitHolder()->setKit($this->kit);
										$this->adaptKitItems($player1);
									}
								}
								if($this->matchData[$this->player2Name]["deathsCountdown"] > 0){
									$this->matchData[$this->player2Name]["deathsCountdown"]--;
									if($this->matchData[$this->player2Name]["deathsCountdown"] !== 0){
										$player2->sendTitle(TextFormat::RED . $this->matchData[$this->player2Name]["deathsCountdown"], "", 5, 20, 5);
									}else{
										$player2->setGamemode(GameMode::SURVIVAL());
										PracticeUtil::teleport($player2, $arena->getP2Spawn(), $arena->getP1Spawn());
										$p2Session->getKitHolder()->setKit($this->kit);
										$this->adaptKitItems($player2);
									}
								}
								break;
							case "BattleRush":
							case "StickFight":
								$air = VanillaBlocks::AIR();
								foreach($this->blocksRemover as $hash => $sec){
									if(--$this->blocksRemover[$hash] === 0){
										World::getBlockXYZ($hash, $x, $y, $z);
										$vec3 = new Vector3($x, $y, $z);
										PracticeUtil::onChunkGenerated($this->world, $x >> 4, $z >> 4, function() use ($vec3, $air){
											if(($block = $this->world->getBlock($vec3))->getId() !== BlockLegacyIds::AIR){
												$this->world->addParticle($vec3->add(0.5, 0.5, 0.5), new BlockBreakParticle($block));
												$this->world->setBlock($vec3, $air, false);
												$this->setBlockAt($block, true);
											}
										});
										unset($this->blocksRemover[$hash]);
									}
								}
								break;
						}
						$p1Session->getScoreboardInfo()->updateDuration($this->getDuration());
						$p2Session->getScoreboardInfo()->updateDuration($this->getDuration());
						foreach($this->spectators as $spec){
							$spectator = PlayerManager::getPlayerExact($spec, true);
							if($spectator !== null){
								PlayerManager::getSession($spectator)->getScoreboardInfo()->updateDuration($this->getDuration());
							}else{
								$this->removeSpectator($spec);
							}
						}
						if(++$this->durationSeconds >= self::MAX_DURATION_SECONDS){
							$this->setEnded();
							return;
						}
					}
				}
				break;
			case self::STATUS_ENDING:
				if($this->currentTick % 20 === 0 && --$this->countdownSeconds === 0){
					$this->endDuel();
					return;
				}
				break;
		}
		if(PracticeCore::REPLAY){
			$this->updateReplayData();
		}
	}

	private function setInDuel() : void{
		/** @var DuelArena $arena */
		$arena = ArenaManager::getArena($this->arena);
		$spawnPos1 = $arena->getP1Spawn();
		$spawnPos2 = $arena->getP2Spawn();
		$player1 = PlayerManager::getPlayerExact($this->player1Name);
		$p1Session = PlayerManager::getSession($player1);
		$player2 = PlayerManager::getPlayerExact($this->player2Name);
		$p2Session = PlayerManager::getSession($player2);
		$player1->setImmobile();
		$player2->setImmobile();
		$player1->setLastDamageCause(new EntityDamageEvent($player1, EntityDamageEvent::CAUSE_MAGIC, 0));
		$player2->setLastDamageCause(new EntityDamageEvent($player2, EntityDamageEvent::CAUSE_MAGIC, 0));
		PracticeUtil::onChunkGenerated($this->world, $spawnPos1->getFloorX() >> 4, $spawnPos1->getFloorZ() >> 4, function() use ($player1, $p1Session, $spawnPos1, $spawnPos2){
			PracticeUtil::teleport($player1, Position::fromObject($spawnPos1, $this->world), $spawnPos2);
			$p1Session->getKitHolder()->setKit($this->kit);
			if($this->currentTick === 20){
				$p1Session->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_DUEL);
			}
			if($this->kit === "Bridge"){
				$this->adaptKitItems($player1);
				$p1Session->setShootArrow(true, false);
			}elseif($this->kit === "BedFight"){
				$this->adaptKitItems($player1);
				$p1Session->getScoreboardInfo()->updateLineOfScoreboard(2, TextFormat::BLUE . " B " . TextFormat::WHITE . "Blue: " . TextFormat::GREEN . "O" . TextFormat::GRAY . " [YOU]");
			}elseif($this->kit === "BattleRush" || $this->kit === "StickFight"){
				$this->adaptKitItems($player1);
			}
		});
		PracticeUtil::onChunkGenerated($this->world, $spawnPos2->getFloorX() >> 4, $spawnPos2->getFloorZ() >> 4, function() use ($player2, $p2Session, $spawnPos1, $spawnPos2){
			PracticeUtil::teleport($player2, Position::fromObject($spawnPos2, $this->world), $spawnPos1);
			$p2Session->getKitHolder()->setKit($this->kit);
			if($this->currentTick === 20){
				$p2Session->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_DUEL);
			}
			if($this->kit === "Bridge"){
				$this->adaptKitItems($player2);
				$p2Session->setShootArrow(true, false);
			}elseif($this->kit === "BedFight"){
				$this->adaptKitItems($player2);
				$p2Session->getScoreboardInfo()->updateLineOfScoreboard(1, TextFormat::RED . " R " . TextFormat::WHITE . "Red: " . TextFormat::GREEN . "O" . TextFormat::GRAY . " [YOU]");
			}elseif($this->kit === "BattleRush" || $this->kit === "StickFight"){
				$this->adaptKitItems($player2);
			}
		});
		if(PracticeCore::REPLAY && KitsManager::getKit($this->kit)->getMiscKitInfo()->isReplaysEnabled() && !isset($this->replayData[$this->player1Name], $this->replayData[$this->player2Name], $this->replayData["world"])){
			$this->replayData = [$this->player1Name => new PlayerReplayData($player1), $this->player2Name => new PlayerReplayData($player2), "world" => new WorldReplayData()];
		}
	}

	public function setEnded(mixed $winner = null, bool $logDuelHistory = true) : void{
		if($this->status !== self::STATUS_ENDING && $this->status !== self::STATUS_ENDED){
			$player1 = PlayerManager::getPlayerExact($this->player1Name);
			$player2 = PlayerManager::getPlayerExact($this->player2Name);
			if($this->kit === "BedFight"){
				$player1?->setGamemode(GameMode::SURVIVAL());
				$player2?->setGamemode(GameMode::SURVIVAL());
			}
			if($winner === null){
				if($this->kit === "Boxing"){
					if($this->matchData[$this->player1Name]["numHits"] > $this->matchData[$this->player2Name]["numHits"]){
						$winner = $player1;
					}elseif($this->matchData[$this->player2Name]["numHits"] > $this->matchData[$this->player1Name]["numHits"]){
						$winner = $player2;
					}
				}elseif($this->kit === "Bridge" || $this->kit === "BattleRush" || $this->kit === "MLGRush"){
					if($this->matchData[$this->player1Name]["extraScores"] > $this->matchData[$this->player2Name]["extraScores"]){
						$winner = $player1;
					}elseif($this->matchData[$this->player2Name]["extraScores"] > $this->matchData[$this->player1Name]["extraScores"]){
						$winner = $player2;
					}
				}elseif($this->kit === "StickFight"){
					if($this->matchData[$this->player1Name]["kills"] > $this->matchData[$this->player2Name]["kills"]){
						$winner = $player1;
					}elseif($this->matchData[$this->player2Name]["kills"] > $this->matchData[$this->player1Name]["kills"]){
						$winner = $player2;
					}
				}
			}
			if($winner !== null && $this->isPlayer($winner) && $logDuelHistory){
				$this->winner = $winner;
				$this->loser = $this->getOpponent($wname = $winner->getName());
				$duelInfo = new DuelInfo($this->player1Name, $this->player1DisplayName, $this->player2Name, $this->player2DisplayName, $this->kit, $this->ranked, $this->matchData, $winner->getName());
				if($this->loser instanceof Player && $this->loser->isOnline()){
					if($this->loser->getPosition()->getY() <= 0 || $this->kit === "Sumo"){
						PracticeUtil::teleport($this->loser, $this->centerPosition);
					}
					VanishHandler::addToVanish($this->loser);
					PlayerManager::getSession($this->loser)->getKitHolder()->clearKit();
				}
				if(PracticeCore::REPLAY){
					$this->setDeathTime($wname === $this->player1Name ? $this->player2Name : $this->player1Name);
				}
				if(($wsession = PlayerManager::getSession($this->winner)) !== null){
					$statsInfo = $wsession->getStatsInfo();
					$statsInfo->addKill();
					$statsInfo->addCurrency(StatsInfo::COIN, rand(StatsInfo::MIN_DUEL_WINNER_COIN, StatsInfo::MAX_DUEL_WINNER_COIN));
					$statsInfo->addBp(rand(StatsInfo::MIN_DUEL_WINNER_COIN, StatsInfo::MAX_DUEL_WINNER_COIN));
					$wsession->addToDuelHistory($duelInfo);
				}
				if(($lsession = PlayerManager::getSession($this->loser)) !== null){
					$statsInfo = $lsession->getStatsInfo();
					$statsInfo->addDeath();
					$statsInfo->addCurrency(StatsInfo::COIN, rand(StatsInfo::MIN_DUEL_LOSER_COIN, StatsInfo::MAX_DUEL_LOSER_COIN));
					$statsInfo->addBp(rand(StatsInfo::MIN_DUEL_LOSER_COIN, StatsInfo::MAX_DUEL_LOSER_COIN));
					$lsession->addToDuelHistory($duelInfo);
				}
			}elseif($winner === null && $logDuelHistory){
				$duelInfo = new DuelInfo($this->player1Name, $this->player1DisplayName, $this->player2Name, $this->player2DisplayName, $this->kit, $this->ranked, $this->matchData);
				PlayerManager::getSession($player1)?->addToDuelHistory($duelInfo);
				PlayerManager::getSession($player2)?->addToDuelHistory($duelInfo);
			}
			$this->countdownSeconds = 3;
			$this->status = self::STATUS_ENDING;
		}
	}

	private function endDuel() : void{
		$this->status = self::STATUS_ENDED;
		$info = null;
		if(PracticeCore::REPLAY && isset($this->replayData[$this->player1Name], $this->replayData[$this->player2Name], $this->replayData["world"])){
			$info = new ReplayInfo($this->currentTick, $this->replayData[$this->player1Name], $this->replayData[$this->player2Name], $this->replayData["world"], $this->kit, $this->arena, $this->ranked);
		}
		$fillerMessages = new stdClass();
		if($this->isRanked()){
			$calculatedElo = new stdClass();
			$this->calculateAndSetElo($calculatedElo);
			$this->generateMessages($fillerMessages, $calculatedElo);
		}else{
			$this->generateMessages($fillerMessages);
		}
		if(($player1 = PlayerManager::getPlayerExact($this->player1Name)) !== null){
			$this->sendFinalMessage($player1, $fillerMessages);
			$p1Session = PlayerManager::getSession($player1);
			if($info !== null){
				$p1Session->addReplayInfo($info);
			}
			$p1Session->reset();
			$p1Session->updateNameTag();
			ReDuelForm::onDisplay($player1, $this->player2DisplayName, $this->getKit(), $this->isRanked());
		}
		if(($player2 = PlayerManager::getPlayerExact($this->player2Name)) !== null){
			$this->sendFinalMessage($player2, $fillerMessages);
			$p2Session = PlayerManager::getSession($player2);
			if($info !== null){
				$p2Session->addReplayInfo($info);
			}
			$p2Session->reset();
			$p2Session->updateNameTag();
			ReDuelForm::onDisplay($player2, $this->player1DisplayName, $this->getKit(), $this->isRanked());
		}
		foreach($this->spectators as $spec){
			if(($spectator = PlayerManager::getPlayerExact($spec, true)) !== null){
				$this->sendFinalMessage($spectator, $fillerMessages);
				PlayerManager::getSession($spectator)->reset();
			}
		}
		if($this->world instanceof World){
			if(ArenaManager::MAPS_MODE !== ArenaManager::NORMAL){
				BlockRemoverHandler::removeBlocks($this->world, $this->chunks, $this->backup);
			}
		}
		DuelHandler::removeDuel($this->worldId);
	}

	private function generateMessages(stdClass $fillerMessages, ?stdClass $extensionMessages = null) : void{
		if(($spectatorsSize = count($this->spectators)) > 0){
			$spectatorsString = "";
			$currentSpectatorIndex = 0;
			$loopedSpectatorsSize = Math::ceil($spectatorsSize, 3);
			foreach($this->spectators as $spec){
				if(($spectator = PlayerManager::getPlayerExact($spec, true)) !== null){
					if($currentSpectatorIndex === $loopedSpectatorsSize){
						$more = count($this->spectators) - $loopedSpectatorsSize;
						$spectatorsString .= TextFormat::DARK_GRAY . ", " . TextFormat::WHITE . "(+$more more)";
						break;
					}
					$comma = ($currentSpectatorIndex === ($loopedSpectatorsSize - 1)) ? "" : TextFormat::DARK_GRAY . ", ";
					$spectatorsString .= TextFormat::WHITE . $spectator->getDisplayName() . $comma;
					$currentSpectatorIndex++;
				}
			}
			$fillerMessages->spectatorString = $spectatorsString;
		}
		if($this->winner !== null && $extensionMessages !== null){
			$winnerString = TextFormat::GRAY . "{$this->winner->getDisplayName()}" . TextFormat::GRAY . "(" . TextFormat::GREEN . "+" . $extensionMessages->winnerEloChange . TextFormat::GRAY . ")";
			$loserString = TextFormat::GRAY . (($this->loser === null) ? ($this->winner->getName() === $this->player1Name ? $this->player2DisplayName : $this->player1DisplayName) : $this->loser->getDisplayName()) . TextFormat::GRAY . "(" . TextFormat::RED . "-" . $extensionMessages->loserEloChange . TextFormat::GRAY . ")";
			$fillerMessages->eloChangesExtension = $winnerString . TextFormat::RESET . TextFormat::DARK_GRAY . ", " . $loserString;
			$fillerMessages->winnerEloChange = $extensionMessages->winnerEloChange;
			$fillerMessages->loserEloChange = $extensionMessages->loserEloChange;
			DiscordUtil::sendLogs("**Ranked Result (" . PracticeCore::getRegionInfo() . ")**\nMode: $this->kit\nDuration: {$this->getDuration()}\nWinner: {$this->winner->getName()} (+$extensionMessages->winnerEloChange)\nLoser: " . (($this->loser === null) ? ($this->winner->getName() === $this->player1Name ? $this->player2Name : $this->player1Name) : $this->loser->getName()) . " (-$extensionMessages->loserEloChange)", true, 0xFFA500, "http://api.zeqa.net/api/players/avatars/" . str_replace(" ", "%20", $this->winner->getName()));
		}
	}

	private function calculateAndSetElo(stdClass &$result) : void{
		$result = null;
		if($this->winner !== null){
			$isWinner = $this->winner->getName() === $this->player1Name;
			$winnerElo = $isWinner ? $this->eloInfo[$this->player1Name] : $this->eloInfo[$this->player2Name];
			$loserElo = $isWinner ? $this->eloInfo[$this->player2Name] : $this->eloInfo[$this->player1Name];
			$winnerInfo = $isWinner ? $this->clientInfo[$this->player1Name] : $this->clientInfo[$this->player2Name];
			$loserInfo = $isWinner ? $this->clientInfo[$this->player2Name] : $this->clientInfo[$this->player1Name];
			$result = EloInfo::calculateElo($wElo = $winnerElo->getEloFromKit($this->kit), $lElo = $loserElo->getEloFromKit($this->kit), $winnerInfo, $loserInfo);
			$result->winnerElo = $wElo + $result->winnerEloChange;
			$result->loserElo = $lElo - $result->loserEloChange;
			$winnerElo->setElo(strtolower($this->kit), $result->winnerElo);
			$winnerElo->save($winnerInfo->getXuid(), $this->winner->getName(), function(){
			});
			$loserElo->setElo(strtolower($this->kit), $result->loserElo);
			$loserElo->save($loserInfo->getXuid(), ($this->loser === null ? ($isWinner ? $this->player2Name : $this->player1Name) : $this->loser->getName()), function(){
			});
		}
	}

	private function sendFinalMessage(Player $playerToSendMessage, stdClass $extensionMessages) : void{
		if(PracticeCore::isPackEnable()){
			$finalMessage = "\n";
			$finalMessage .= " " . PracticeUtil::formatUnicodeKit($this->kit) . TextFormat::BOLD . TextFormat::WHITE . " Duel Summary" . TextFormat::RESET . "\n";
			$finalMessage .= TextFormat::GREEN . "  Winner: " . TextFormat::GRAY . (($this->winner === null) ? "None" : $this->winner->getDisplayName());
			if($this->ranked && isset($extensionMessages->eloChangesExtension)){
				$finalMessage .= TextFormat::GRAY . " (" . TextFormat::WHITE . "+" . $extensionMessages->winnerEloChange . TextFormat::GRAY . ")\n";
			}else{
				$finalMessage .= "\n";
			}
			$finalMessage .= TextFormat::RED . "  Loser: " . TextFormat::GRAY . (($this->loser === null) ? ($this->winner === null ? "None" : ($this->winner->getName() === $this->player1Name ? $this->player2DisplayName : $this->player1DisplayName)) : $this->loser->getDisplayName());
			if($this->ranked && isset($extensionMessages->eloChangesExtension)){
				$finalMessage .= TextFormat::GRAY . " (" . TextFormat::WHITE . "-" . $extensionMessages->loserEloChange . TextFormat::GRAY . ")\n\n";
			}else{
				$finalMessage .= "\n\n";
			}
			$finalMessage .= "  " . TextFormat::WHITE . "Spectator(s): " . TextFormat::GRAY . ($extensionMessages->spectatorString ?? "None") . "\n";
			$finalMessage .= "\n";
		}else{
			$finalMessage = TextFormat::DARK_GRAY . "--------------------------\n";
			$finalMessage .= TextFormat::GREEN . "Winner: " . TextFormat::WHITE . (($this->winner === null) ? "None" : $this->winner->getDisplayName()) . "\n";
			$finalMessage .= TextFormat::RED . "Loser: " . TextFormat::WHITE . (($this->loser === null) ? ($this->winner === null ? "None" : ($this->winner->getName() === $this->player1Name ? $this->player2DisplayName : $this->player1DisplayName)) : $this->loser->getDisplayName()) . "\n";
			$finalMessage .= TextFormat::DARK_GRAY . "--------------------------\n";
			if($this->ranked && isset($extensionMessages->eloChangesExtension)){
				$finalMessage .= TextFormat::GOLD . "Elo Changes: " . $extensionMessages->eloChangesExtension . "\n";
				$finalMessage .= TextFormat::DARK_GRAY . "--------------------------\n";
			}
			$finalMessage .= TextFormat::GRAY . "Spectator(s): " . TextFormat::WHITE . ($extensionMessages->spectatorString ?? "None") . "\n";
			$finalMessage .= TextFormat::DARK_GRAY . "--------------------------\n";
		}
		$playerToSendMessage->sendMessage($finalMessage);
	}

	public function addSpectator(Player $player) : void{
		if($this->status !== self::STATUS_ENDING && $this->status !== self::STATUS_ENDED){
			$this->spectators[$name = $player->getDisplayName()] = $name;
			PracticeUtil::onChunkGenerated($this->world, $this->centerPosition->getFloorX() >> 4, $this->centerPosition->getFloorZ() >> 4, function() use ($player){
				PracticeUtil::teleport($player, $this->centerPosition);
				VanishHandler::addToVanish($player);
				ItemHandler::giveSpectatorItem($player);
				$session = PlayerManager::getSession($player);
				$session->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_SPECTATOR);
				$session->setInHub(false);
			});
			$msg = PracticeCore::PREFIX . TextFormat::GREEN . $name . TextFormat::GRAY . " is now spectating the duel";
			PlayerManager::getPlayerExact($this->player1Name)?->sendMessage($msg);
			PlayerManager::getPlayerExact($this->player2Name)?->sendMessage($msg);
			foreach($this->spectators as $spec){
				PlayerManager::getPlayerExact($spec, true)?->sendMessage($msg);
			}
		}
	}

	public function removeSpectator(string $name) : void{
		if(isset($this->spectators[$name])){
			PlayerManager::getSession(PlayerManager::getPlayerExact($name, true))?->reset();
			$msg = PracticeCore::PREFIX . TextFormat::RED . $name . TextFormat::GRAY . " is no longer spectating the duel";
			PlayerManager::getPlayerExact($this->player1Name)?->sendMessage($msg);
			PlayerManager::getPlayerExact($this->player2Name)?->sendMessage($msg);
			foreach($this->spectators as $spec){
				PlayerManager::getPlayerExact($spec, true)?->sendMessage($msg);
			}
			unset($this->spectators[$name]);
		}
	}

	public function isSpectator(string|Player $player) : bool{
		return isset($this->spectators[$player instanceof Player ? $player->getDisplayName() : $player]);
	}

	public function addHitTo(string|Player $player, bool $critical) : void{
		if(isset($this->matchData[$name = $player instanceof Player ? $player->getName() : $player])){
			$this->matchData[$name]["numHits"]++;
			if($critical){
				$this->matchData[$name]["criticalHits"]++;
			}
			$this->matchData[$name]["combo"]++;
			$this->matchData[$name]["longestCombo"] = max($this->matchData[$name]["longestCombo"], $this->matchData[$name]["combo"]);
			$this->saveLongestCombo($this->getOpponent($name));
			if($this->kit === "Boxing"){
				$player1Score = $this->matchData[$this->player1Name]["numHits"];
				$player2Score = $this->matchData[$this->player2Name]["numHits"];
				$diff = abs($player1Score - $player2Score);
				$isPlayerOneLeading = $player1Score >= $player2Score;
				if(($player1 = PlayerManager::getPlayerExact($this->player1Name)) !== null){
					$sbInfo = PlayerManager::getSession($player1)->getScoreboardInfo();
					$sbInfo->updateLineOfScoreboard(1, PracticeCore::COLOR . " Hits: " . ($isPlayerOneLeading ? TextFormat::GREEN . "(+$diff)" : TextFormat::RED . "(-$diff)"));
					$sbInfo->updateLineOfScoreboard(2, PracticeCore::COLOR . "   You: " . TextFormat::WHITE . $player1Score);
					$sbInfo->updateLineOfScoreboard(3, PracticeCore::COLOR . "   Them: " . TextFormat::WHITE . $player2Score);
				}
				if(($player2 = PlayerManager::getPlayerExact($this->player2Name)) !== null){
					$sbInfo = PlayerManager::getSession($player2)->getScoreboardInfo();
					$sbInfo->updateLineOfScoreboard(1, PracticeCore::COLOR . " Hits: " . ($isPlayerOneLeading ? TextFormat::RED . "(-$diff)" : TextFormat::GREEN . "(+$diff)"));
					$sbInfo->updateLineOfScoreboard(2, PracticeCore::COLOR . "   You: " . TextFormat::WHITE . $player2Score);
					$sbInfo->updateLineOfScoreboard(3, PracticeCore::COLOR . "   Them: " . TextFormat::WHITE . $player1Score);
				}
				$player1Line = PracticeCore::COLOR . "   $this->player1DisplayName: " . TextFormat::WHITE . $player1Score;
				$player2Line = PracticeCore::COLOR . "   $this->player2DisplayName: " . TextFormat::WHITE . $player2Score;
				foreach($this->spectators as $spectator){
					if(($specSession = PlayerManager::getSession(PlayerManager::getPlayerExact($spectator, true))) !== null){
						$sbInfo = $specSession->getScoreboardInfo();
						$sbInfo->updateLineOfScoreboard(2, $player1Line);
						$sbInfo->updateLineOfScoreboard(3, $player2Line);
					}
				}
				if($this->matchData[$name]["numHits"] >= 100 && $this->status === self::STATUS_IN_PROGRESS){
					if(($session = PlayerManager::getSession($this->getOpponent($name))) !== null){
						Server::getInstance()->broadcastPackets([PlayerManager::getPlayerExact($name)], [LevelSoundEventPacket::create(LevelSoundEvent::HURT, $session->getPlayer()->getPosition(), -1, "minecraft:player", false, false)]);
						$session->onDeath();
					}else{
						$this->setEnded(PlayerManager::getPlayerExact($name));
					}
				}
			}
		}
	}

	public function saveLongestCombo(string|Player $player) : void{
		if(isset($this->matchData[$name = $player instanceof Player ? $player->getName() : $player])){
			$this->matchData[$name]["longestCombo"] = max($this->matchData[$name]["longestCombo"], $this->matchData[$name]["combo"]);
			$this->matchData[$name]["combo"] = 0;
		}
	}

	public function addKillTo(string|Player $player) : void{
		if(isset($this->matchData[$name = $player instanceof Player ? $player->getName() : $player])){
			$this->matchData[$name]["kills"]++;
			$this->saveLongestCombo($name);
			$this->saveLongestCombo($this->getOpponent($name));
			$this->matchData[$this->player1Name === $name ? $this->player2Name : $this->player1Name]["deaths"]++;
			if($this->kit === "Bridge" || $this->kit === "BedFight" || $this->kit === "BattleRush"){
				PlayerManager::getSession(PlayerManager::getPlayerExact($name))?->getScoreboardInfo()->updateLineOfScoreboard(4, PracticeCore::COLOR . " Kills: " . TextFormat::WHITE . $this->matchData[$name]["kills"]);
			}
		}
	}

	public function adaptKitItems(Player $player) : void{
		$inv = $player->getInventory();
		$items = $inv->getContents();
		foreach($items as $slot => $item){
			if(($terracotta = $item->getId() === BlockLegacyIds::TERRACOTTA) || $item->getId() === BlockLegacyIds::WOOL){
				$inv->setItem($slot, ItemFactory::getInstance()->get(($terracotta ? BlockLegacyIds::TERRACOTTA : BlockLegacyIds::WOOL), ($player->getName() === $this->player1Name) ? 11 : 14, $item->getCount()));
			}
		}
		$armorinv = $player->getArmorInventory();
		$armors = $armorinv->getContents();
		foreach($armors as $slot => $armor){
			if($armor instanceof Armor){
				$armorinv->setItem($slot, $armor->setCustomColor(($player->getName() === $this->player1Name) ? new Color(0, 0, 255) : new Color(255, 0, 0)));
			}
		}
	}

	public function tryBreakOrPlaceBlock(Player $player, Block $block, bool $break = true) : bool{
		if($this->isPlayer($player) && $this->isRunning()){
			if($break){
				if($this->kit === "Bridge"){
					if(!in_array($block->getId(), [BlockLegacyIds::TERRACOTTA, BlockLegacyIds::CONCRETE], true)){
						return false;
					}
					if(!in_array($block->getMeta(), [0, 11, 14], true)){
						return false;
					}
					/** @var DuelArena $arena */
					$arena = ArenaManager::getArena($this->arena);
					if(!$arena->canBuild($block->getPosition())){
						return false;
					}
				}elseif($this->kit === "BuildUHC"){
					if(!in_array($block->getId(), [BlockLegacyIds::COBBLESTONE, BlockLegacyIds::WOODEN_PLANKS, BlockLegacyIds::OBSIDIAN, BlockLegacyIds::FLOWING_WATER, BlockLegacyIds::WATER, BlockLegacyIds::FLOWING_LAVA, BlockLegacyIds::LAVA], true)){
						return false;
					}
					if(($block->getId() === BlockLegacyIds::WOODEN_PLANKS && $block->getMeta() !== 0)){
						return false;
					}
					/** @var DuelArena $arena */
					$arena = ArenaManager::getArena($this->arena);
					if(!$arena->canBuild($block->getPosition())){
						return false;
					}
				}elseif($this->kit === "BedFight"){
					if(!in_array($block->getId(), [BlockLegacyIds::WOOL, BlockLegacyIds::END_STONE, BlockLegacyIds::WOODEN_PLANKS, BlockLegacyIds::BED_BLOCK], true)){
						return false;
					}
					if(($block->getId() === BlockLegacyIds::WOOL && $block->getMeta() !== 11 && $block->getMeta() !== 14) || ($block->getId() === BlockLegacyIds::WOODEN_PLANKS && $block->getMeta() !== 0)){
						return false;
					}
					if($block instanceof Bed){
						if($this->matchData[$name = $player->getName()]["deathsCountdown"] > 0){
							return false;
						}
						$color = $block->getColor()->getDisplayName();
						if($color === "Blue"){
							if($name === $this->player1Name){
								$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You can not break your own bed");
								return false;
							}
							$this->matchData[$this->player1Name]["extraFlag"] = false;
							$msg = TextFormat::BLUE . "Blue bed was destroyed";
							$sb = TextFormat::BLUE . " B " . TextFormat::WHITE . "Blue: " . TextFormat::RED . "X";
							$players = [];
							if(($p1Session = PlayerManager::getSession($player1 = PlayerManager::getPlayerExact($this->player1Name))) !== null){
								$player1->sendTitle($msg, "", 5, 20, 5);
								$p1Session->getScoreboardInfo()->updateLineOfScoreboard(2, $sb . TextFormat::GRAY . " [YOU]");
								$players[] = $player1;
							}
							if(($p2Session = PlayerManager::getSession($player2 = PlayerManager::getPlayerExact($this->player2Name))) !== null){
								$player2->sendTitle($msg, "", 5, 20, 5);
								$p2Session->getScoreboardInfo()->updateLineOfScoreboard(2, $sb);
								$players[] = $player2;
							}
							foreach($this->spectators as $spec){
								if(($specSession = PlayerManager::getSession($spec = PlayerManager::getPlayerExact($spec, true))) !== null){
									$spec->sendTitle($msg, "", 5, 20, 5);
									$specSession->getScoreboardInfo()->updateLineOfScoreboard(2, $sb);
									$players[] = $spec;
								}
							}
							$pos = $block->getPosition();
							Server::getInstance()->broadcastPackets($players, [PlaySoundPacket::create("mob.enderdragon.growl", $pos->getX(), $pos->getY(), $pos->getZ(), 1, 1)]);
						}elseif($color === "Red"){
							if($name === $this->player2Name){
								$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You can not break your own bed");
								return false;
							}
							$this->matchData[$this->player2Name]["extraFlag"] = false;
							$msg = TextFormat::RED . "Red bed was destroyed";
							$sb = TextFormat::RED . " R " . TextFormat::WHITE . "Red: " . TextFormat::RED . "X";
							$players = [];
							if(($p1Session = PlayerManager::getSession($player1 = PlayerManager::getPlayerExact($this->player1Name))) !== null){
								$player1->sendTitle($msg, "", 5, 20, 5);
								$p1Session->getScoreboardInfo()->updateLineOfScoreboard(1, $sb);
								$players[] = $player1;
							}
							if(($p2Session = PlayerManager::getSession($player2 = PlayerManager::getPlayerExact($this->player2Name))) !== null){
								$player2->sendTitle($msg, "", 5, 20, 5);
								$p2Session->getScoreboardInfo()->updateLineOfScoreboard(1, $sb . TextFormat::GRAY . " [YOU]");
								$players[] = $player2;
							}
							foreach($this->spectators as $spec){
								if(($specSession = PlayerManager::getSession($spec = PlayerManager::getPlayerExact($spec, true))) !== null){
									$spec->sendTitle($msg, "", 5, 20, 5);
									$specSession->getScoreboardInfo()->updateLineOfScoreboard(1, $sb);
									$players[] = $spec;
								}
							}
							$pos = $block->getPosition();
							Server::getInstance()->broadcastPackets($players, [PlaySoundPacket::create("mob.enderdragon.growl", $pos->getX(), $pos->getY(), $pos->getZ(), 1, 1)]);
						}
						if(($half = $block->getOtherHalf()) !== null){
							if(PracticeCore::REPLAY){
								$this->setBlockAt($half, true);
							}
							$pos = $half->getPosition();
							if(!isset($this->chunks[$hash = World::chunkHash($pos->getFloorX() >> 4, $pos->getFloorZ() >> 4)])){
								$this->chunks[$hash] = new ChunkCache();
							}
							$this->chunks[$hash]->removeBlock($half, $half->getPosition());
							$this->backup[World::blockHash($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ())] = $half;
							$pos = $block->getPosition();
							$this->backup[World::blockHash($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ())] = $block;
						}
					}else{
						/** @var DuelArena $arena */
						$arena = ArenaManager::getArena($this->arena);
						if(!$arena->canBuild($block->getPosition())){
							return false;
						}
						$player->getInventory()->addItem($block->asItem());
					}
				}elseif($this->kit === "BattleRush" || $this->kit === "StickFight"){
					if($block->getId() !== BlockLegacyIds::WOOL){
						return false;
					}
					if(($block->getId() === BlockLegacyIds::WOOL && $block->getMeta() !== 11 && $block->getMeta() !== 14)){
						return false;
					}
					/** @var DuelArena $arena */
					$arena = ArenaManager::getArena($this->arena);
					if(!$arena->canBuild($block->getPosition())){
						return false;
					}
					$player->getInventory()->addItem($block->asItem());
				}elseif($this->kit === "MLGRush"){
					if(!in_array($block->getId(), [BlockLegacyIds::SANDSTONE, BlockLegacyIds::BED_BLOCK], true)){
						return false;
					}
					if($block instanceof Bed){
						$name = $player->getName();
						$color = $block->getColor()->getDisplayName();
						if($color === "Blue"){
							if($name === $this->player1Name){
								$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You can not break your own bed");
								return false;
							}
							$this->matchData[$this->player2Name]["extraScores"]++;
							$p1Session = PlayerManager::getSession($player1 = PlayerManager::getPlayerExact($this->player1Name));
							$p2Session = PlayerManager::getSession($player2 = PlayerManager::getPlayerExact($this->player2Name));
							$msg = TextFormat::RED . $player2->getDisplayName() . TextFormat::WHITE . " Scored!\n" . TextFormat::RED . $this->matchData[$this->player2Name]["extraScores"] . TextFormat::GRAY . " - " . TextFormat::BLUE . $this->matchData[$this->player1Name]["extraScores"];
							$player1->sendTitle($msg, "", 5, 20, 5);
							$player2->sendTitle($msg, "", 5, 20, 5);
							$player2->broadcastSound(new XpCollectSound(), [$player2]);
							$blue = TextFormat::BLUE . " [B] " . str_repeat("O", $this->matchData[$this->player1Name]["extraScores"]) . TextFormat::GRAY . str_repeat("O", 5 - $this->matchData[$this->player1Name]["extraScores"]);
							$red = TextFormat::RED . " [R] " . str_repeat("O", $this->matchData[$this->player2Name]["extraScores"]) . TextFormat::GRAY . str_repeat("O", 5 - $this->matchData[$this->player2Name]["extraScores"]);
							$p1Session->getScoreboardInfo()->updateLineOfScoreboard(1, $blue);
							$p1Session->getScoreboardInfo()->updateLineOfScoreboard(2, $red);
							$p2Session->getScoreboardInfo()->updateLineOfScoreboard(1, $blue);
							$p2Session->getScoreboardInfo()->updateLineOfScoreboard(2, $red);
							foreach($this->spectators as $spectator){
								if(($specSession = PlayerManager::getSession($spec = PlayerManager::getPlayerExact($spectator, true))) !== null){
									$spec->sendTitle($msg, "", 5, 20, 5);
									$sbInfo = $specSession->getScoreboardInfo();
									$sbInfo->updateLineOfScoreboard(1, $blue);
									$sbInfo->updateLineOfScoreboard(2, $red);
								}
							}
							$this->saveLongestCombo($player1);
							$this->saveLongestCombo($player2);
							if($this->matchData[$this->player2Name]["extraScores"] === 5){
								$this->setEnded($player2);
							}else{
								BlockRemoverHandler::removeBlocks($this->world, $this->chunks);
								$this->chunks = [];
								$this->blocksRemover = [];
								$this->setInDuel();
								$this->status = self::STATUS_STARTING;
								$this->countdownSeconds = 7;
							}
						}elseif($color === "Red"){
							if($name === $this->player2Name){
								$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You can not break your own bed");
								return false;
							}
							$this->matchData[$this->player1Name]["extraScores"]++;
							$p1Session = PlayerManager::getSession($player1 = PlayerManager::getPlayerExact($this->player1Name));
							$p2Session = PlayerManager::getSession($player2 = PlayerManager::getPlayerExact($this->player2Name));
							$msg = TextFormat::BLUE . $player1->getDisplayName() . TextFormat::WHITE . " Scored!\n" . TextFormat::BLUE . $this->matchData[$this->player1Name]["extraScores"] . TextFormat::GRAY . " - " . TextFormat::RED . $this->matchData[$this->player2Name]["extraScores"];
							$player1->sendTitle($msg, "", 5, 20, 5);
							$player2->sendTitle($msg, "", 5, 20, 5);
							$player2->broadcastSound(new XpCollectSound(), [$player2]);
							$blue = TextFormat::BLUE . " [B] " . str_repeat("O", $this->matchData[$this->player1Name]["extraScores"]) . TextFormat::GRAY . str_repeat("O", 5 - $this->matchData[$this->player1Name]["extraScores"]);
							$red = TextFormat::RED . " [R] " . str_repeat("O", $this->matchData[$this->player2Name]["extraScores"]) . TextFormat::GRAY . str_repeat("O", 5 - $this->matchData[$this->player2Name]["extraScores"]);
							$p1Session->getScoreboardInfo()->updateLineOfScoreboard(1, $blue);
							$p1Session->getScoreboardInfo()->updateLineOfScoreboard(2, $red);
							$p2Session->getScoreboardInfo()->updateLineOfScoreboard(1, $blue);
							$p2Session->getScoreboardInfo()->updateLineOfScoreboard(2, $red);
							foreach($this->spectators as $spectator){
								if(($specSession = PlayerManager::getSession($spec = PlayerManager::getPlayerExact($spectator, true))) !== null){
									$spec->sendTitle($msg, "", 5, 20, 5);
									$sbInfo = $specSession->getScoreboardInfo();
									$sbInfo->updateLineOfScoreboard(1, $blue);
									$sbInfo->updateLineOfScoreboard(2, $red);
								}
							}
							$this->saveLongestCombo($player1);
							$this->saveLongestCombo($player2);
							if($this->matchData[$this->player1Name]["extraScores"] === 5){
								$this->setEnded($player1);
							}else{
								BlockRemoverHandler::removeBlocks($this->world, $this->chunks);
								$this->chunks = [];
								$this->blocksRemover = [];
								$this->setInDuel();
								$this->status = self::STATUS_STARTING;
								$this->countdownSeconds = 7;
							}
						}
						return false;
					}else{
						/** @var DuelArena $arena */
						$arena = ArenaManager::getArena($this->arena);
						if(!$arena->canBuild($block->getPosition())){
							return false;
						}
					}
				}elseif($this->kit === "Spleef"){
					if($block->getId() !== BlockLegacyIds::SNOW){
						return false;
					}
					/** @var DuelArena $arena */
					$arena = ArenaManager::getArena($this->arena);
					if(!$arena->canBuild($block->getPosition())){
						return false;
					}
					$player->getInventory()->addItem(ItemFactory::getInstance()->get(ItemIds::SNOWBALL, 0, rand(1, 3)));
				}
				if(PracticeCore::REPLAY){
					$this->setBlockAt($block, true);
				}
				$this->matchData[$player->getName()]["brokeBlocks"]++;
				$pos = $block->getPosition();
				if(!isset($this->chunks[$hash = World::chunkHash($pos->getFloorX() >> 4, $pos->getFloorZ() >> 4)])){
					$this->chunks[$hash] = new ChunkCache();
				}
				$this->chunks[$hash]->removeBlock($block, $pos);
				if($this->kit === "BattleRush" || $this->kit === "StickFight"){
					unset($this->blocksRemover[World::blockHash($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ())]);
				}
			}else{
				/** @var DuelArena $arena */
				$arena = ArenaManager::getArena($this->arena);
				if(!$arena->canBuild($pos = $block->getPosition())){
					return false;
				}
				if($this->kit === "BuildUHC" && $block->getId() === BlockLegacyIds::MOB_HEAD_BLOCK){
					return false;
				}
				if(PracticeCore::REPLAY){
					$this->setBlockAt($block);
				}
				$this->matchData[$player->getName()]["placedBlocks"]++;
				if(!isset($this->chunks[$hash = World::chunkHash($pos->getFloorX() >> 4, $pos->getFloorZ() >> 4)])){
					$this->chunks[$hash] = new ChunkCache();
				}
				$this->chunks[$hash]->addBlock(VanillaBlocks::AIR(), $pos);
				if($this->kit === "BattleRush" || $this->kit === "StickFight"){
					$this->blocksRemover[World::blockHash($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ())] = 10;
				}
			}
			return true;
		}
		return false;
	}

	public function tryUpdateBlock(Block $block, bool $break = true) : bool{
		if($this->isRunning()){
			/** @var DuelArena $arena */
			$arena = ArenaManager::getArena($this->arena);
			if(!$arena->canBuild($pos = $block->getPosition())){
				return false;
			}
			if(!isset($this->chunks[$hash = World::chunkHash($pos->getFloorX() >> 4, $pos->getFloorZ() >> 4)])){
				$this->chunks[$hash] = new ChunkCache();
			}
			if($break){
				if(PracticeCore::REPLAY){
					$this->setBlockAt($block, true);
				}
				$this->chunks[$hash]->removeBlock($block, $pos);
			}else{
				if(PracticeCore::REPLAY){
					$this->setBlockAt($block);
				}
				$this->chunks[$hash]->addBlock(VanillaBlocks::AIR(), $pos);
			}
			return true;
		}
		return false;
	}

	public function deathCountdown(string|Player $player) : bool{
		if(isset($this->matchData[$name = $player instanceof Player ? $player->getName() : $player])){
			if($this->kit === "StickFight"){
				$this->matchData[$name]["deathsCountdown"]--;
				$player1 = PlayerManager::getPlayerExact($this->player1Name);
				$player2 = PlayerManager::getPlayerExact($this->player2Name);
				$msg = $this->player1Name === $name ? TextFormat::RED . $this->player2DisplayName . TextFormat::WHITE . " Scored!\n" . TextFormat::RED . 5 - $this->matchData[$this->player1Name]["deathsCountdown"] . TextFormat::GRAY . " - " . TextFormat::BLUE . 5 - $this->matchData[$this->player2Name]["deathsCountdown"] : TextFormat::BLUE . $this->player1DisplayName . TextFormat::WHITE . " Scored!\n" . TextFormat::BLUE . 5 - $this->matchData[$this->player2Name]["deathsCountdown"] . TextFormat::GRAY . " - " . TextFormat::RED . 5 - $this->matchData[$this->player1Name]["deathsCountdown"];
				if(($p1Session = PlayerManager::getSession($player1)) !== null){
					$player1->sendTitle($msg, "", 5, 20, 5);
					if($this->player1Name !== $name){
						$player1->broadcastSound(new XpCollectSound(), [$player1]);
					}
					$sbInfo = $p1Session->getScoreboardInfo();
					$sbInfo->updateLineOfScoreboard(2, PracticeCore::COLOR . "   You: " . TextFormat::WHITE . $this->matchData[$this->player1Name]["deathsCountdown"]);
					$sbInfo->updateLineOfScoreboard(3, PracticeCore::COLOR . "   Them: " . TextFormat::WHITE . $this->matchData[$this->player2Name]["deathsCountdown"]);
				}
				if(($p2Session = PlayerManager::getSession($player2)) !== null){
					$player2->sendTitle($msg, "", 5, 20, 5);
					if($this->player2Name !== $name){
						$player2->broadcastSound(new XpCollectSound(), [$player2]);
					}
					$sbInfo = $p2Session->getScoreboardInfo();
					$sbInfo->updateLineOfScoreboard(2, PracticeCore::COLOR . "   You: " . TextFormat::WHITE . $this->matchData[$this->player2Name]["deathsCountdown"]);
					$sbInfo->updateLineOfScoreboard(3, PracticeCore::COLOR . "   Them: " . TextFormat::WHITE . $this->matchData[$this->player1Name]["deathsCountdown"]);
				}
				$player1Line = PracticeCore::COLOR . "   $this->player1DisplayName: " . TextFormat::WHITE . ($this->player1Name === $name ? $this->matchData[$this->player1Name]["deathsCountdown"] : $this->matchData[$this->player2Name]["deathsCountdown"]);
				$player2Line = PracticeCore::COLOR . "   $this->player2DisplayName: " . TextFormat::WHITE . ($this->player1Name === $name ? $this->matchData[$this->player2Name]["deathsCountdown"] : $this->matchData[$this->player1Name]["deathsCountdown"]);
				foreach($this->spectators as $spectator){
					if(($specSession = PlayerManager::getSession($spec = PlayerManager::getPlayerExact($spectator, true))) !== null){
						$spec->sendTitle($msg, "", 5, 20, 5);
						$sbInfo = $specSession->getScoreboardInfo();
						$sbInfo->updateLineOfScoreboard(2, $player1Line);
						$sbInfo->updateLineOfScoreboard(3, $player2Line);
					}
				}
				$this->saveLongestCombo($player1);
				$this->saveLongestCombo($player2);
				if($this->matchData[$name]["deathsCountdown"] === 0){
					$opponent = ($name === $this->player1Name ? $this->player2Name : $this->player1Name);
					$this->matchData[$opponent]["kills"] = 5;
					$this->matchData[$opponent]["deaths"] = 5 - $this->matchData[$opponent]["deathsCountdown"];
					$this->matchData[$name]["kills"] = 5 - $this->matchData[$opponent]["deathsCountdown"];
					$this->matchData[$name]["deaths"] = 5;
					$this->setEnded($this->getOpponent($name));
				}else{
					BlockRemoverHandler::removeBlocks($this->world, $this->chunks);
					$this->chunks = [];
					$this->blocksRemover = [];
					$this->setInDuel();
					$this->status = self::STATUS_STARTING;
					$this->totalCountdown = 3;
					$this->countdownSeconds = 5;
				}
			}else{
				if($this->matchData[$name]["extraFlag"]){
					if($this->matchData[$name]["deathsCountdown"] === 0){
						$this->matchData[$name]["deathsCountdown"] = 4;
						PlayerManager::getSession($player)->getKitHolder()->clearKit();
						$player->setGamemode(GameMode::SPECTATOR());
						return true;
					}else{
						$this->saveLongestCombo($name);
						$this->saveLongestCombo($this->getOpponent($name));
						PracticeUtil::teleport($player, $this->centerPosition);
					}
				}else{
					$this->addKillTo($player);
					$this->setEnded($this->getOpponent($player));
				}
			}
		}
		return false;
	}

	public function getPlayer1() : string{
		return $this->player1Name;
	}

	public function getPlayer2() : string{
		return $this->player2Name;
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
		return $this->kit;
	}

	public function isRanked() : bool{
		return $this->ranked;
	}

	public function getArena() : string{
		return $this->arena;
	}

	public function getCenterPosition() : Position{
		return $this->centerPosition;
	}

	public function isRunning() : bool{
		return $this->status === self::STATUS_IN_PROGRESS;
	}

	public function getDuration() : string{
		$seconds = $this->durationSeconds % 60;
		$minutes = (int) ($this->durationSeconds / 60);
		return ($minutes < 10 ? "0" . $minutes : $minutes) . ":" . ($seconds < 10 ? "0" . $seconds : $seconds);
	}

	private function updateReplayData() : void{
		if(($player1 = PlayerManager::getPlayerExact($this->player1Name)) !== null && ($player2 = PlayerManager::getPlayerExact($this->player2Name)) !== null && isset($this->replayData[$this->player1Name], $this->replayData[$this->player2Name], $this->replayData["world"])){
			$tick = $this->currentTick;
			$p1ReplayData = $this->replayData[$this->player1Name];
			$p2ReplayData = $this->replayData[$this->player2Name];
			$p1ReplayData->setScoreTagAt($tick, $player1->getScoreTag());
			$p1ReplayData->setItemAt($tick, $player1->getInventory()->getItemInHand());
			$p1ReplayData->setLocationAt($tick, Location::fromObject($p1Location = $player1->getLocation(), null, $p1Location->getYaw(), $p1Location->getPitch()));
			$p1Armor = $player1->getArmorInventory()->getContents(true);
			$p1ReplayData->setArmorAt($tick, ["helmet" => $p1Armor[0], "chest" => $p1Armor[1], "pants" => $p1Armor[2], "boots" => $p1Armor[3]]);
			$p2ReplayData->setScoreTagAt($tick, $player2->getScoreTag());
			$p2ReplayData->setItemAt($tick, $player2->getInventory()->getItemInHand());
			$p2ReplayData->setLocationAt($tick, Location::fromObject($p2Location = $player2->getLocation(), null, $p2Location->getYaw(), $p2Location->getPitch()));
			$p2Armor = $player2->getArmorInventory()->getContents(true);
			$p2ReplayData->setArmorAt($tick, ["helmet" => $p2Armor[0], "chest" => $p2Armor[1], "pants" => $p2Armor[2], "boots" => $p2Armor[3]]);
		}
	}

	public function setAnimationFor(string|Player $player, int $animation, int $data = 0) : void{
		($this->replayData[$player instanceof Player ? $player->getName() : $player] ?? null)?->setAnimationAt($this->currentTick, $animation, $data);
	}

	public function setThrowFor(string|Player $player, ProjectileItem $item) : void{
		if($item->getId() === ItemIds::SPLASH_POTION && isset($this->matchData[$name = $player instanceof Player ? $player->getName() : $player])){
			$this->matchData[$name]["potsUsed"]++;
		}
		if(PracticeCore::REPLAY){
			($this->replayData[$player instanceof Player ? $player->getName() : $player] ?? null)?->setThrowAt($this->currentTick, $item->getVanillaName());
		}
	}

	public function setFishingFor(string|Player $player, bool $fishing) : void{
		if(!$fishing && isset($this->matchData[$name = $player instanceof Player ? $player->getName() : $player])){
			$this->matchData[$name]["rodUsed"]++;
		}
		if(PracticeCore::REPLAY){
			($this->replayData[$player instanceof Player ? $player->getName() : $player] ?? null)?->setFishingAt($this->currentTick, $fishing);
		}
	}

	public function setReleaseBow(string|Player $player, float $force) : void{
		if(isset($this->matchData[$name = $player instanceof Player ? $player->getName() : $player])){
			$this->matchData[$name]["arrowsUsed"]++;
		}
		if(PracticeCore::REPLAY){
			($this->replayData[$player instanceof Player ? $player->getName() : $player] ?? null)?->setReleaseBowAt($this->currentTick, $force);
		}
	}

	public function setSneaking(string|Player $player, bool $sneak) : void{
		($this->replayData[$player instanceof Player ? $player->getName() : $player] ?? null)?->setSneakingAt($this->currentTick, $sneak);
	}

	private function setDeathTime(string|Player $player) : void{
		($this->replayData[$player instanceof Player ? $player->getName() : $player] ?? null)?->setDeathTime($this->currentTick);
	}

	public function setBlockAt(Block $block, bool $air = false) : void{
		($this->replayData["world"] ?? null)?->setBlockAt($this->currentTick, $block, $air);
	}

	public function addProjectileHit(string|Player $player, string $key) : void{
		if(isset($this->matchData[$name = $player instanceof Player ? $player->getName() : $player])){
			$this->matchData[$name][$key]++;
		}
	}

	public function destroyCycles() : void{
		$this->world = null;
		$this->centerPosition = null;
		$this->winner = null;
		$this->loser = null;
		$this->spectators = [];
		$this->chunks = [];
		$this->backup = [];
		$this->blocksRemover = [];
		$this->eloInfo = [];
		$this->clientInfo = [];
		$this->replayData = [];
		ArenaManager::getArena($this->arena)?->setPreWorldAsAvailable($this->worldId);
	}
}
