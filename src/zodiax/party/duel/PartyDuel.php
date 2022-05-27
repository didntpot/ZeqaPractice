<?php

declare(strict_types=1);

namespace zodiax\party\duel;

use pocketmine\block\Bed;
use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\color\Color;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Armor;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
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
use zodiax\game\items\ItemHandler;
use zodiax\game\world\BlockRemoverHandler;
use zodiax\game\world\thread\ChunkCache;
use zodiax\kits\DefaultKit;
use zodiax\party\duel\misc\PracticeTeam;
use zodiax\party\PartyManager;
use zodiax\party\PracticeParty;
use zodiax\player\info\scoreboard\ScoreboardInfo;
use zodiax\player\info\StatsInfo;
use zodiax\player\misc\VanishHandler;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\utils\Math;
use function abs;
use function array_keys;
use function count;
use function in_array;
use function rand;
use function str_repeat;

class PartyDuel{

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
	private int $size;
	private string $arena;
	private int $worldId;
	private ?World $world;
	private ?Position $centerPosition;
	private ?PracticeParty $winner;
	private ?PracticeParty $loser;
	private string $party1;
	private string $party2;
	private ?PracticeTeam $team1;
	private ?PracticeTeam $team2;
	private array $spectators;
	private array $numHits;
	private array $extraScores;
	private array $kills;
	private array $deathsCountdown;
	private array $extraFlag;
	private array $chunks;
	private array $backup;
	private array $blocksRemover;

	public function __construct(int $worldId, PracticeParty $party1, PracticeParty $party2, DefaultKit $kit, DuelArena $arena){
		$this->status = self::STATUS_STARTING;
		$this->currentTick = 0;
		$this->countdownSeconds = 5;
		$this->totalCountdown = 5;
		$this->durationSeconds = 0;
		$this->kit = $kit->getName();
		$this->size = $party1->getPlayers(true);
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
		$this->party1 = $party1->getName();
		$this->party2 = $party2->getName();
		$this->team1 = new PracticeTeam(TextFormat::BLUE);
		$this->team1->addPartyToTeam($party1);
		$this->team2 = new PracticeTeam(TextFormat::RED);
		$this->team2->addPartyToTeam($party2);
		$this->spectators = [];
		$this->numHits = [$this->party1 => 0, $this->party2 => 0];
		$this->extraScores = [$this->party1 => ($kit->getName() === "StickFight" ? 5 : 0), $this->party2 => ($kit->getName() === "StickFight" ? 5 : 0)];
		$this->kills = [];
		foreach($party1->getPlayers() as $member){
			$this->kills[$member] = 0;
			$this->deathsCountdown[$member] = 0;
		}
		foreach($party2->getPlayers() as $member){
			$this->kills[$member] = 0;
			$this->deathsCountdown[$member] = 0;
		}
		$this->extraFlag = [$this->party1 => true, $this->party2 => true];
		$this->chunks = [];
		$this->backup = [];
		$this->blocksRemover = [];
	}

	public function update() : void{
		$party1 = PartyManager::getPartyFromName($this->party1);
		$party2 = PartyManager::getPartyFromName($this->party2);
		if($party1 === null || $party2 === null || $this->team1->isEliminated() || $this->team2->isEliminated()){
			if($this->status !== self::STATUS_ENDING || $this->status !== self::STATUS_ENDED){
				if($party1 === null){
					$this->setEnded($party2);
				}elseif($party2 === null){
					$this->setEnded($party1);
				}else{
					$this->setEnded(($this->team1->isEliminated() && $this->team2->isEliminated()) ? null : (($this->team1->isEliminated()) ? $party2 : $party1));
				}
				$this->status = self::STATUS_ENDING;
			}
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
						$clickSound = new ClickSound();
						foreach($this->team1->getPlayers() as $member){
							if(($member = PlayerManager::getPlayerExact($member)) !== null){
								$member->sendTitle($msg, "", 5, 20, 5);
								$member->broadcastSound($clickSound, [$member]);
							}
						}
						foreach($this->team2->getPlayers() as $member){
							if(($member = PlayerManager::getPlayerExact($member)) !== null){
								$member->sendTitle($msg, "", 5, 20, 5);
								$member->broadcastSound($clickSound, [$member]);
							}
						}
					}elseif($this->countdownSeconds > 0 && $this->countdownSeconds < $this->totalCountdown){
						$msg = TextFormat::RED . $this->countdownSeconds . "...";
						$clickSound = new ClickSound();
						foreach($this->team1->getPlayers() as $member){
							if(($member = PlayerManager::getPlayerExact($member)) !== null){
								$member->sendTitle($msg, "", 5, 20, 5);
								$member->broadcastSound($clickSound, [$member]);
							}
						}
						foreach($this->team2->getPlayers() as $member){
							if(($member = PlayerManager::getPlayerExact($member)) !== null){
								$member->sendTitle($msg, "", 5, 20, 5);
								$member->broadcastSound($clickSound, [$member]);
							}
						}
					}elseif($this->countdownSeconds === 0){
						$msg = TextFormat::RED . "Fight!";
						$xpSound = new XpCollectSound();
						foreach($this->team1->getPlayers() as $member){
							if(($member = PlayerManager::getPlayerExact($member)) !== null){
								$member->sendTitle($msg, "", 5, 20, 5);
								$member->broadcastSound($xpSound, [$member]);
								if(!PlayerManager::getSession($member)->isFrozen()){
									$member->setImmobile(false);
								}
							}
						}
						foreach($this->team2->getPlayers() as $member){
							if(($member = PlayerManager::getPlayerExact($member)) !== null){
								$member->sendTitle($msg, "", 5, 20, 5);
								$member->broadcastSound($xpSound, [$member]);
								if(!PlayerManager::getSession($member)->isFrozen()){
									$member->setImmobile(false);
								}
							}
						}
						$this->status = self::STATUS_IN_PROGRESS;
						$this->countdownSeconds = 3;
					}
					$this->countdownSeconds--;
				}
				break;
			case self::STATUS_IN_PROGRESS:
				$team1Sessions = [];
				$team2Sessions = [];
				foreach($this->team1->getPlayers() as $member){
					if(($msession = PlayerManager::getSession(PlayerManager::getPlayerExact($member))) !== null){
						$msession->getClicksInfo()->update();
						$team1Sessions[] = $msession;
					}
				}
				foreach($this->team2->getPlayers() as $member){
					if(($msession = PlayerManager::getSession(PlayerManager::getPlayerExact($member))) !== null){
						$msession->getClicksInfo()->update();
						$team2Sessions[] = $msession;
					}
				}
				if($this->currentTick % 5 == 0){
					switch($this->kit){
						case "Bridge":
						case "BattleRush":
							/** @var DuelArena $arena */
							$arena = ArenaManager::getArena($this->arena);
							foreach($team1Sessions as $msession){
								$member = $msession->getPlayer();
								$pos = $member->getPosition()->asVector3();
								$block = $this->world->getBlock($pos);
								if($block->getId() === BlockLegacyIds::END_PORTAL && $this->status === self::STATUS_IN_PROGRESS){
									if($pos->distance($p2Spawn = $arena->getP2Spawn()) > $pos->distance($p1Spawn = $arena->getP1Spawn())){
										PracticeUtil::teleport($member, $p1Spawn, $p2Spawn);
										$msession->getKitHolder()->setKit($this->kit);
										$this->adaptKitItems($member);
									}else{
										$this->extraScores[$this->party1]++;
										$msg = TextFormat::BLUE . $this->party1 . TextFormat::WHITE . " Scored!\n" . TextFormat::BLUE . $this->extraScores[$this->party1] . TextFormat::GRAY . " - " . TextFormat::RED . $this->extraScores[$this->party2];
										$blue = TextFormat::BLUE . " [B] " . str_repeat("O", $this->extraScores[$this->party1]) . TextFormat::GRAY . str_repeat("O", ($this->kit === "Bridge" ? 5 : 3) - $this->extraScores[$this->party1]);
										$red = TextFormat::RED . " [R] " . str_repeat("O", $this->extraScores[$this->party2]) . TextFormat::GRAY . str_repeat("O", ($this->kit === "Bridge" ? 5 : 3) - $this->extraScores[$this->party2]);
										foreach($team1Sessions as $m){
											$m->getPlayer()->sendTitle($msg, "", 5, 20, 5);
											$m->getScoreboardInfo()->updateLineOfScoreboard(1, $blue);
											$m->getScoreboardInfo()->updateLineOfScoreboard(2, $red);
										}
										foreach($team2Sessions as $m){
											$m->getPlayer()->sendTitle($msg, "", 5, 20, 5);
											$m->getScoreboardInfo()->updateLineOfScoreboard(1, $blue);
											$m->getScoreboardInfo()->updateLineOfScoreboard(2, $red);
										}
										foreach($this->spectators as $spectator){
											if(($specSession = PlayerManager::getSession($spec = PlayerManager::getPlayerExact($spectator, true))) !== null){
												$spec->sendTitle($msg, "", 5, 20, 5);
												$sbInfo = $specSession->getScoreboardInfo();
												$sbInfo->updateLineOfScoreboard(1, $blue);
												$sbInfo->updateLineOfScoreboard(2, $red);
											}
										}
										$member->broadcastSound(new XpCollectSound(), [$member]);
										if($this->extraScores[$this->party1] === ($this->kit === "Bridge" ? 5 : 3)){
											$this->setEnded($party1);
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
							}
							foreach($team2Sessions as $msession){
								$member = $msession->getPlayer();
								$pos = $member->getPosition()->asVector3();
								$block = $this->world->getBlock($pos);
								if($block->getId() === BlockLegacyIds::END_PORTAL && $this->status === self::STATUS_IN_PROGRESS){
									if($pos->distance($p1Spawn = $arena->getP1Spawn()) > $pos->distance($p2Spawn = $arena->getP2Spawn())){
										PracticeUtil::teleport($member, $p2Spawn, $p1Spawn);
										$msession->getKitHolder()->setKit($this->kit);
										$this->adaptKitItems($member);
									}else{
										$this->extraScores[$this->party2]++;
										$msg = TextFormat::RED . $this->party2 . TextFormat::WHITE . " Scored!\n" . TextFormat::RED . $this->extraScores[$this->party2] . TextFormat::GRAY . " - " . TextFormat::BLUE . $this->extraScores[$this->party1];
										$blue = TextFormat::BLUE . " [B] " . str_repeat("O", $this->extraScores[$this->party1]) . TextFormat::GRAY . str_repeat("O", ($this->kit === "Bridge" ? 5 : 3) - $this->extraScores[$this->party1]);
										$red = TextFormat::RED . " [R] " . str_repeat("O", $this->extraScores[$this->party2]) . TextFormat::GRAY . str_repeat("O", ($this->kit === "Bridge" ? 5 : 3) - $this->extraScores[$this->party2]);
										foreach($team1Sessions as $m){
											$m->getPlayer()->sendTitle($msg, "", 5, 20, 5);
											$m->getScoreboardInfo()->updateLineOfScoreboard(1, $blue);
											$m->getScoreboardInfo()->updateLineOfScoreboard(2, $red);
										}
										foreach($team2Sessions as $m){
											$m->getPlayer()->sendTitle($msg, "", 5, 20, 5);
											$m->getScoreboardInfo()->updateLineOfScoreboard(1, $blue);
											$m->getScoreboardInfo()->updateLineOfScoreboard(2, $red);
										}
										foreach($this->spectators as $spectator){
											if(($specSession = PlayerManager::getSession($spec = PlayerManager::getPlayerExact($spectator, true))) !== null){
												$spec->sendTitle($msg, "", 5, 20, 5);
												$sbInfo = $specSession->getScoreboardInfo();
												$sbInfo->updateLineOfScoreboard(1, $blue);
												$sbInfo->updateLineOfScoreboard(2, $red);
											}
										}
										$member->broadcastSound(new XpCollectSound(), [$member]);
										if($this->extraScores[$this->party2] === ($this->kit === "Bridge" ? 5 : 3)){
											$this->setEnded($party2);
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
							}
							break;
						case "Sumo":
							/** @var DuelArena $arena */
							$arena = ArenaManager::getArena($this->arena);
							$minY = $arena->getP1Spawn()->getFloorY() - 2;
							foreach($team1Sessions as $msession){
								if($msession->getPlayer()->getPosition()->getFloorY() < $minY){
									$msession->onDeath();
								}
							}
							foreach($team2Sessions as $msession){
								if($msession->getPlayer()->getPosition()->getFloorY() < $minY){
									$msession->onDeath();
								}
							}
							break;
					}
					if($this->currentTick % 20 === 0){
						switch($this->kit){
							case "BedFight":
								/** @var DuelArena $arena */
								$arena = ArenaManager::getArena($this->arena);
								foreach($team1Sessions as $msession){
									$member = $msession->getPlayer();
									if($this->deathsCountdown[$name = $member->getName()] > 0){
										$this->deathsCountdown[$name]--;
										if($this->deathsCountdown[$name] !== 0){
											$member->sendTitle(TextFormat::BLUE . $this->deathsCountdown[$name], "", 5, 20, 5);
										}else{
											$member->setGamemode(GameMode::SURVIVAL());
											PracticeUtil::teleport($member, $arena->getP1Spawn(), $arena->getP2Spawn());
											$msession->getKitHolder()->setKit($this->kit);
											$this->adaptKitItems($member);
										}
									}
								}
								foreach($team2Sessions as $msession){
									$member = $msession->getPlayer();
									if($this->deathsCountdown[$name = $member->getName()] > 0){
										$this->deathsCountdown[$name]--;
										if($this->deathsCountdown[$name] !== 0){
											$member->sendTitle(TextFormat::RED . $this->deathsCountdown[$name], "", 5, 20, 5);
										}else{
											$member->setGamemode(GameMode::SURVIVAL());
											PracticeUtil::teleport($member, $arena->getP2Spawn(), $arena->getP1Spawn());
											$msession->getKitHolder()->setKit($this->kit);
											$this->adaptKitItems($member);
										}
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
											}
										});
										unset($this->blocksRemover[$hash]);
									}
								}
								break;
						}
						foreach($team1Sessions as $msession){
							$msession->getScoreboardInfo()->updateDuration($this->getDuration());
						}
						foreach($team2Sessions as $msession){
							$msession->getScoreboardInfo()->updateDuration($this->getDuration());
						}
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
	}

	private function setInDuel() : void{
		/** @var DuelArena $arena */
		$arena = ArenaManager::getArena($this->arena);
		$spawnPos1 = $arena->getP1Spawn();
		$p1Pos = Position::fromObject($spawnPos1, $this->world);
		$spawnPos2 = $arena->getP2Spawn();
		$p2Pos = Position::fromObject($spawnPos2, $this->world);
		foreach($this->team1->getPlayers() as $member){
			if(($msession = PlayerManager::getSession($member = PlayerManager::getPlayerExact($member))) !== null){
				$member->setImmobile();
				$member->setLastDamageCause(new EntityDamageEvent($member, EntityDamageEvent::CAUSE_MAGIC, 0));
				PracticeUtil::onChunkGenerated($this->world, $spawnPos1->getFloorX() >> 4, $spawnPos1->getFloorZ() >> 4, function() use ($member, $msession, $p1Pos, $p2Pos){
					PracticeUtil::teleport($member, $p1Pos, $p2Pos);
					$msession->getKitHolder()->setKit($this->kit);
					if($this->currentTick === 20){
						$msession->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_DUEL);
					}
					if($this->kit === "Bridge"){
						$this->adaptKitItems($member);
						$msession->setShootArrow(true, false);
					}elseif($this->kit === "BedFight"){
						$this->adaptKitItems($member);
						$msession->getScoreboardInfo()->updateLineOfScoreboard(2, TextFormat::BLUE . " B " . TextFormat::WHITE . "Blue: " . TextFormat::GREEN . "O" . TextFormat::GRAY . " [YOU]");
					}elseif($this->kit === "BattleRush" || $this->kit === "StickFight"){
						$this->adaptKitItems($member);
					}
					$msession->updateNameTag();
				});
			}
		}
		foreach($this->team2->getPlayers() as $member){
			if(($msession = PlayerManager::getSession($member = PlayerManager::getPlayerExact($member))) !== null){
				$member->setImmobile();
				$member->setLastDamageCause(new EntityDamageEvent($member, EntityDamageEvent::CAUSE_MAGIC, 0));
				PracticeUtil::onChunkGenerated($this->world, $spawnPos2->getFloorX() >> 4, $spawnPos2->getFloorZ() >> 4, function() use ($member, $msession, $p1Pos, $p2Pos){
					PracticeUtil::teleport($member, $p2Pos, $p1Pos);
					$msession->getKitHolder()->setKit($this->kit);
					if($this->currentTick === 20){
						$msession->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_DUEL);
					}
					if($this->kit === "Bridge"){
						$this->adaptKitItems($member);
						$msession->setShootArrow(true, false);
					}elseif($this->kit === "BedFight"){
						$this->adaptKitItems($member);
						$msession->getScoreboardInfo()->updateLineOfScoreboard(1, TextFormat::RED . " R " . TextFormat::WHITE . "Red: " . TextFormat::GREEN . "O" . TextFormat::GRAY . " [YOU]");
					}elseif($this->kit === "BattleRush" || $this->kit === "StickFight"){
						$this->adaptKitItems($member);
					}
					$msession->updateNameTag();
				});
			}
		}
	}

	public function setEnded(mixed $winner = null) : void{
		if($this->status !== self::STATUS_ENDING && $this->status !== self::STATUS_ENDED){
			// CHECK IF IN WAR EVENT DO SOMETHING
			if($this->kit === "BedFight"){
				$players = array_keys($this->kills);
				foreach($players as $player){
					PlayerManager::getPlayerExact($player)?->setGamemode(GameMode::SURVIVAL());
				}
			}
			if($winner !== null && $this->isParty($winner)){
				$this->winner = $winner;
				foreach($this->winner->getPlayers() as $member){
					if(($wsession = PlayerManager::getSession($member = PlayerManager::getPlayerExact($member))) !== null){
						if($member->getPosition()->getY() <= 0){
							PracticeUtil::teleport($member, $this->centerPosition);
						}
						$wsession->getStatsInfo()->addCurrency(StatsInfo::COIN, rand(StatsInfo::MIN_DUEL_WINNER_COIN, StatsInfo::MAX_DUEL_WINNER_COIN));
						$wsession->getStatsInfo()->addBp(rand(StatsInfo::MIN_DUEL_WINNER_COIN, StatsInfo::MAX_DUEL_WINNER_COIN));
					}
				}
				$loser = $this->getOpponent($winner->getName());
				$this->loser = $loser;
				foreach($this->loser?->getPlayers() ?? [] as $member){
					if(($lsession = PlayerManager::getSession($member = PlayerManager::getPlayerExact($member))) !== null){
						if($member->getPosition()->getY() <= 0){
							PracticeUtil::teleport($member, $this->centerPosition);
						}
						$lsession->getStatsInfo()->addCurrency(StatsInfo::COIN, rand(StatsInfo::MIN_DUEL_LOSER_COIN, StatsInfo::MAX_DUEL_LOSER_COIN));
						$lsession->getStatsInfo()->addBp(rand(StatsInfo::MIN_DUEL_LOSER_COIN, StatsInfo::MAX_DUEL_LOSER_COIN));
					}
				}
			}
			$this->countdownSeconds = 3;
			$this->status = self::STATUS_ENDING;
		}
	}

	private function endDuel() : void{
		$this->status = self::STATUS_ENDED;
		$this->team1->setEliminated();
		$this->team2->setEliminated();
		$fillerMessages = new stdClass();
		$this->generateMessages($fillerMessages);
		$players = array_keys($this->kills);
		foreach($players as $player){
			if(($session = PlayerManager::getSession($player = PlayerManager::getPlayerExact($player))) !== null){
				$this->sendFinalMessage($player, $fillerMessages);
				$session->reset();
				$session->updateNameTag();
			}
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
		PartyDuelHandler::removeDuel($this->worldId);
	}

	private function generateMessages(stdClass $fillerMessages) : void{
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
	}

	private function sendFinalMessage(Player $playerToSendMessage, stdClass $extensionMessages) : void{
		if($playerToSendMessage->isOnline()){
			if(PracticeCore::isPackEnable()){
				$finalMessage = "\n";
				$finalMessage .= " " . PracticeUtil::formatUnicodeKit($this->kit) . TextFormat::BOLD . TextFormat::WHITE . " Duel Summary" . TextFormat::RESET . "\n";
				$finalMessage .= TextFormat::GREEN . "  Winner: " . TextFormat::GRAY . (($this->winner === null) ? "None" : $this->winner->getName()) . "\n";
				$finalMessage .= TextFormat::RED . "  Loser: " . TextFormat::GRAY . (($this->loser === null) ? "None" : $this->loser->getName()) . "\n\n";
				$finalMessage .= "  " . TextFormat::WHITE . "Spectator(s): " . TextFormat::GRAY . ($extensionMessages->spectatorString ?? "None") . "\n";
				$finalMessage .= "\n";
			}else{
				$finalMessage = TextFormat::DARK_GRAY . "--------------------------\n";
				$finalMessage .= TextFormat::GREEN . "Winner: " . TextFormat::WHITE . (($this->winner === null) ? "None" : $this->winner->getName()) . "\n";
				$finalMessage .= TextFormat::RED . "Loser: " . TextFormat::WHITE . (($this->loser === null) ? "None" : $this->loser->getName()) . "\n";
				$finalMessage .= TextFormat::DARK_GRAY . "--------------------------\n";
				$finalMessage .= TextFormat::GRAY . "Spectator(s): " . TextFormat::WHITE . ($extensionMessages->spectatorString ?? "None") . "\n";
				$finalMessage .= TextFormat::DARK_GRAY . "--------------------------\n";
			}
			$playerToSendMessage->sendMessage($finalMessage);
		}
	}

	public function getTeam($player) : ?PracticeTeam{
		return ($this->team1->isInTeam($player) ? $this->team1 : ($this->team2->isInTeam($player) ? $this->team2 : null));
	}

	public function removeFromTeam(Player $player) : void{
		$team = $this->getTeam($player);
		if($team instanceof PracticeTeam){
			if(($session = PlayerManager::getSession($player)) !== null){
				$session->getKitHolder()->clearKit();
				if($player->getPosition()->getY() <= 0 || $this->kit === "Sumo"){
					PracticeUtil::teleport($player, $this->centerPosition);
				}
				VanishHandler::addToVanish($player);
				$session->getExtensions()->clearAll();
			}
			$msg = PracticeCore::PREFIX . $team->getTeamColor() . $player->getDisplayName() . TextFormat::GRAY . " has been eliminated";
			foreach(PartyManager::getPartyFromName($this->party1)?->getPlayers() ?? [] as $member){
				PlayerManager::getPlayerExact($member)?->sendMessage($msg);
			}
			foreach(PartyManager::getPartyFromName($this->party2)?->getPlayers() ?? [] as $member){
				PlayerManager::getPlayerExact($member)?->sendMessage($msg);
			}
			foreach($this->spectators as $spectator){
				PlayerManager::getPlayerExact($spectator, true)?->sendMessage($msg);
			}
			$team->removeFromTeam($player);
		}
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
			foreach(PartyManager::getPartyFromName($this->party1)?->getPlayers() ?? [] as $member){
				PlayerManager::getPlayerExact($member)?->sendMessage($msg);
			}
			foreach(PartyManager::getPartyFromName($this->party2)?->getPlayers() ?? [] as $member){
				PlayerManager::getPlayerExact($member)?->sendMessage($msg);
			}
			foreach($this->spectators as $spec){
				PlayerManager::getPlayerExact($spec, true)?->sendMessage($msg);
			}
		}
	}

	public function removeSpectator(string $name) : void{
		if(isset($this->spectators[$name])){
			PlayerManager::getSession(PlayerManager::getPlayerExact($name, true))?->reset();
			$msg = PracticeCore::PREFIX . TextFormat::RED . $name . TextFormat::GRAY . " is no longer spectating the duel";
			foreach(PartyManager::getPartyFromName($this->party1)?->getPlayers() ?? [] as $member){
				PlayerManager::getPlayerExact($member)?->sendMessage($msg);
			}
			foreach(PartyManager::getPartyFromName($this->party2)?->getPlayers() ?? [] as $member){
				PlayerManager::getPlayerExact($member)?->sendMessage($msg);
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

	public function addHitTo(string|Player $player, bool $ignore) : void{
		if($this->kit === "Boxing" && isset($this->numHits[$name = (PartyManager::getPartyFromName($this->party1)?->isPlayer($player) ? $this->party1 : $this->party2)])){
			$this->numHits[$name] += 1;
			$party1Score = $this->numHits[$this->party1];
			$party2Score = $this->numHits[$this->party2];
			$diff = abs($party1Score - $party2Score);
			$isPartyOneLeading = $party1Score >= $party2Score;
			foreach(PartyManager::getPartyFromName($this->party1)?->getPlayers() ?? [] as $member){
				if(($msession = PlayerManager::getSession(PlayerManager::getPlayerExact($member))) !== null){
					$sbInfo = $msession->getScoreboardInfo();
					$sbInfo->updateLineOfScoreboard(1, PracticeCore::COLOR . " Hits: " . ($isPartyOneLeading ? TextFormat::GREEN . "(+$diff)" : TextFormat::RED . "(-$diff)"));
					$sbInfo->updateLineOfScoreboard(2, PracticeCore::COLOR . "   You: " . TextFormat::WHITE . $party1Score);
					$sbInfo->updateLineOfScoreboard(3, PracticeCore::COLOR . "   Them: " . TextFormat::WHITE . $party2Score);
				}
			}
			foreach(PartyManager::getPartyFromName($this->party2)?->getPlayers() ?? [] as $member){
				if(($msession = PlayerManager::getSession(PlayerManager::getPlayerExact($member))) !== null){
					$sbInfo = $msession->getScoreboardInfo();
					$sbInfo->updateLineOfScoreboard(1, PracticeCore::COLOR . " Hits: " . ($isPartyOneLeading ? TextFormat::RED . "(-$diff)" : TextFormat::GREEN . "(+$diff)"));
					$sbInfo->updateLineOfScoreboard(2, PracticeCore::COLOR . "   You: " . TextFormat::WHITE . $party2Score);
					$sbInfo->updateLineOfScoreboard(3, PracticeCore::COLOR . "   Them: " . TextFormat::WHITE . $party1Score);
				}
			}
			$party1Line = PracticeCore::COLOR . "   $this->party1: " . TextFormat::WHITE . $party1Score;
			$party2Line = PracticeCore::COLOR . "   $this->party2: " . TextFormat::WHITE . $party2Score;
			foreach($this->spectators as $spectator){
				if(($specSession = PlayerManager::getSession(PlayerManager::getPlayerExact($spectator, true))) !== null){
					$sbInfo = $specSession->getScoreboardInfo();
					$sbInfo->updateLineOfScoreboard(2, $party1Line);
					$sbInfo->updateLineOfScoreboard(3, $party2Line);
				}
			}
			if($this->numHits[$name] >= 100 * $this->size && $this->status === self::STATUS_IN_PROGRESS){
				$this->setEnded((PartyManager::getPartyFromName($this->party1)->isPlayer($player) ? PartyManager::getPartyFromName($this->party1) : PartyManager::getPartyFromName($this->party2)));
			}
		}
	}

	public function addKillTo(string|Player $player) : void{
		if(isset($this->kills[$name = $player instanceof Player ? $player->getName() : $player])){
			$this->kills[$name]++;
			if($this->kit === "Bridge" || $this->kit === "BedFight" || $this->kit === "BattleRush"){
				PlayerManager::getSession(PlayerManager::getPlayerExact($name))?->getScoreboardInfo()->updateLineOfScoreboard(4, PracticeCore::COLOR . " Kills: " . TextFormat::WHITE . $this->kills[$name]);
			}
		}
	}

	public function adaptKitItems(Player $player) : void{
		$inv = $player->getInventory();
		$items = $inv->getContents();
		foreach($items as $slot => $item){
			if(($terracotta = $item->getId() === BlockLegacyIds::TERRACOTTA) || $item->getId() === BlockLegacyIds::WOOL){
				$inv->setItem($slot, ItemFactory::getInstance()->get(($terracotta ? BlockLegacyIds::TERRACOTTA : BlockLegacyIds::WOOL), (PartyManager::getPartyFromName($this->party1)->isPlayer($player)) ? 11 : 14, $item->getCount()));
			}
		}
		$armorinv = $player->getArmorInventory();
		$armors = $armorinv->getContents();
		foreach($armors as $slot => $armor){
			if($armor instanceof Armor){
				$armorinv->setItem($slot, $armor->setCustomColor((PartyManager::getPartyFromName($this->party1)->isPlayer($player)) ? new Color(0, 0, 255) : new Color(255, 0, 0)));
			}
		}
	}

	public function tryBreakOrPlaceBlock(Player $player, Block $block, bool $break = true) : bool{
		if(($team = $this->getTeam($player)) !== null && $this->isRunning()){
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
						if($this->deathsCountdown[$player->getName()] > 0){
							return false;
						}
						$color = $block->getColor()->getDisplayName();
						if($color === "Blue"){
							if($team->getTeamColor() === $this->team1->getTeamColor()){
								$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You can not break your own bed");
								return false;
							}
							$this->extraFlag[$this->party1] = false;
							$msg = TextFormat::BLUE . "Blue bed was destroyed";
							$sb = TextFormat::BLUE . " B " . TextFormat::WHITE . "Blue: " . TextFormat::RED . "X";
							$players = [];
							foreach(PartyManager::getPartyFromName($this->party1)?->getPlayers() ?? [] as $member){
								if(($msession = PlayerManager::getSession($member = PlayerManager::getPlayerExact($member))) !== null){
									$member->sendTitle($msg, "", 5, 20, 5);
									$msession->getScoreboardInfo()->updateLineOfScoreboard(2, $sb . TextFormat::GRAY . " [YOU]");
									$players[] = $member;
								}
							}
							foreach(PartyManager::getPartyFromName($this->party2)?->getPlayers() ?? [] as $member){
								if(($msession = PlayerManager::getSession($member = PlayerManager::getPlayerExact($member))) !== null){
									$member->sendTitle($msg, "", 5, 20, 5);
									$msession->getScoreboardInfo()->updateLineOfScoreboard(2, $sb);
									$players[] = $member;
								}
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
							if($team->getTeamColor() === $this->team2->getTeamColor()){
								$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You can not break your own bed");
								return false;
							}
							$this->extraFlag[$this->party2] = false;
							$msg = TextFormat::RED . "Red bed was destroyed";
							$sb = TextFormat::RED . " R " . TextFormat::WHITE . "Red: " . TextFormat::RED . "X";
							$players = [];
							foreach(PartyManager::getPartyFromName($this->party1)?->getPlayers() ?? [] as $member){
								if(($msession = PlayerManager::getSession($member = PlayerManager::getPlayerExact($member))) !== null){
									$member->sendTitle($msg, "", 5, 20, 5);
									$msession->getScoreboardInfo()->updateLineOfScoreboard(1, $sb);
									$players[] = $member;
								}
							}
							foreach(PartyManager::getPartyFromName($this->party2)?->getPlayers() ?? [] as $member){
								if(($msession = PlayerManager::getSession($member = PlayerManager::getPlayerExact($member))) !== null){
									$member->sendTitle($msg, "", 5, 20, 5);
									$msession->getScoreboardInfo()->updateLineOfScoreboard(1, $sb . TextFormat::GRAY . " [YOU]");
									$players[] = $member;
								}
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
						$color = $block->getColor()->getDisplayName();
						if($color === "Blue"){
							if($team->getTeamColor() === $this->team1->getTeamColor()){
								$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You can not break your own bed");
								return false;
							}
							$this->extraScores[$this->party2]++;
							$msg = TextFormat::RED . $player->getDisplayName() . TextFormat::WHITE . " Scored!\n" . TextFormat::RED . $this->extraScores[$this->party2] . TextFormat::GRAY . " - " . TextFormat::BLUE . $this->extraScores[$this->party1];
							$blue = TextFormat::BLUE . " [B] " . str_repeat("O", $this->extraScores[$this->party1]) . TextFormat::GRAY . str_repeat("O", 5 - $this->extraScores[$this->party1]);
							$red = TextFormat::RED . " [R] " . str_repeat("O", $this->extraScores[$this->party2]) . TextFormat::GRAY . str_repeat("O", 5 - $this->extraScores[$this->party2]);
							foreach(PartyManager::getPartyFromName($this->party1)?->getPlayers() ?? [] as $member){
								if(($msession = PlayerManager::getSession($member = PlayerManager::getPlayerExact($member))) !== null){
									$member->sendTitle($msg, "", 5, 20, 5);
									$sbInfo = $msession->getScoreboardInfo();
									$sbInfo->updateLineOfScoreboard(1, $blue);
									$sbInfo->updateLineOfScoreboard(2, $red);
								}
							}
							foreach(PartyManager::getPartyFromName($this->party2)?->getPlayers() ?? [] as $member){
								if(($msession = PlayerManager::getSession($member = PlayerManager::getPlayerExact($member))) !== null){
									$member->sendTitle($msg, "", 5, 20, 5);
									$sbInfo = $msession->getScoreboardInfo();
									$sbInfo->updateLineOfScoreboard(1, $blue);
									$sbInfo->updateLineOfScoreboard(2, $red);
								}
							}
							foreach($this->spectators as $spec){
								if(($specSession = PlayerManager::getSession($spec = PlayerManager::getPlayerExact($spec, true))) !== null){
									$spec->sendTitle($msg, "", 5, 20, 5);
									$sbInfo = $specSession->getScoreboardInfo();
									$sbInfo->updateLineOfScoreboard(1, $blue);
									$sbInfo->updateLineOfScoreboard(2, $red);
								}
							}
							$player->broadcastSound(new XpCollectSound(), [$player]);
							if($this->extraScores[$this->party2] === 5){
								$this->setEnded(PartyManager::getPartyFromName($this->party2));
							}else{
								BlockRemoverHandler::removeBlocks($this->world, $this->chunks);
								$this->chunks = [];
								$this->blocksRemover = [];
								$this->setInDuel();
								$this->status = self::STATUS_STARTING;
								$this->countdownSeconds = 7;
							}
						}elseif($color === "Red"){
							if($team->getTeamColor() === $this->team2->getTeamColor()){
								$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You can not break your own bed");
								return false;
							}
							$this->extraScores[$this->party1]++;
							$msg = TextFormat::BLUE . $player->getDisplayName() . TextFormat::WHITE . " Scored!\n" . TextFormat::BLUE . $this->extraScores[$this->party1] . TextFormat::GRAY . " - " . TextFormat::RED . $this->extraScores[$this->party2];
							$blue = TextFormat::BLUE . " [B] " . str_repeat("O", $this->extraScores[$this->party1]) . TextFormat::GRAY . str_repeat("O", 5 - $this->extraScores[$this->party1]);
							$red = TextFormat::RED . " [R] " . str_repeat("O", $this->extraScores[$this->party2]) . TextFormat::GRAY . str_repeat("O", 5 - $this->extraScores[$this->party2]);
							foreach(PartyManager::getPartyFromName($this->party1)?->getPlayers() ?? [] as $member){
								if(($msession = PlayerManager::getSession($member = PlayerManager::getPlayerExact($member))) !== null){
									$member->sendTitle($msg, "", 5, 20, 5);
									$sbInfo = $msession->getScoreboardInfo();
									$sbInfo->updateLineOfScoreboard(1, $blue);
									$sbInfo->updateLineOfScoreboard(2, $red);
								}
							}
							foreach(PartyManager::getPartyFromName($this->party2)?->getPlayers() ?? [] as $member){
								if(($msession = PlayerManager::getSession($member = PlayerManager::getPlayerExact($member))) !== null){
									$member->sendTitle($msg, "", 5, 20, 5);
									$sbInfo = $msession->getScoreboardInfo();
									$sbInfo->updateLineOfScoreboard(1, $blue);
									$sbInfo->updateLineOfScoreboard(2, $red);
								}
							}
							foreach($this->spectators as $spec){
								if(($specSession = PlayerManager::getSession($spec = PlayerManager::getPlayerExact($spec, true))) !== null){
									$spec->sendTitle($msg, "", 5, 20, 5);
									$sbInfo = $specSession->getScoreboardInfo();
									$sbInfo->updateLineOfScoreboard(1, $blue);
									$sbInfo->updateLineOfScoreboard(2, $red);
								}
							}
							$player->broadcastSound(new XpCollectSound(), [$player]);
							if($this->extraScores[$this->party1] === 5){
								$this->setEnded(PartyManager::getPartyFromName($this->party1));
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
				$this->chunks[$hash]->removeBlock($block, $pos);
			}else{
				$this->chunks[$hash]->addBlock(VanillaBlocks::AIR(), $pos);
			}
			return true;
		}
		return false;
	}

	public function deathCountdown(string|Player $player) : bool{
		if(isset($this->deathsCountdown[$name = $player instanceof Player ? $player->getName() : $player])){
			if($this->kit === "StickFight"){
				$this->extraScores[$party = PartyManager::getPartyFromName($this->party1)?->isPlayer($player) ? $this->party1 : $this->party2]--;
				$msg = $this->party1 === $party ? TextFormat::RED . $this->party2 . TextFormat::WHITE . " Scored!\n" . TextFormat::RED . 5 - $this->extraScores[$this->party1] . TextFormat::GRAY . " - " . TextFormat::BLUE . 5 - $this->extraScores[$this->party2] : TextFormat::BLUE . $this->party1 . TextFormat::WHITE . " Scored!\n" . TextFormat::BLUE . 5 - $this->extraScores[$this->party2] . TextFormat::GRAY . " - " . TextFormat::RED . 5 - $this->extraScores[$this->party1];
				foreach(PartyManager::getPartyFromName($this->party1)?->getPlayers() ?? [] as $member){
					if(($msession = PlayerManager::getSession($member = PlayerManager::getPlayerExact($member))) !== null){
						$member->sendTitle($msg, "", 5, 20, 5);
						if($this->party1 !== $party){
							$member->broadcastSound(new XpCollectSound(), [$member]);
						}
						$sbInfo = $msession->getScoreboardInfo();
						$sbInfo->updateLineOfScoreboard(2, PracticeCore::COLOR . "   You: " . TextFormat::WHITE . $this->extraScores[$this->party1]);
						$sbInfo->updateLineOfScoreboard(3, PracticeCore::COLOR . "   Them: " . TextFormat::WHITE . $this->extraScores[$this->party2]);
					}
				}
				foreach(PartyManager::getPartyFromName($this->party2)?->getPlayers() ?? [] as $member){
					if(($msession = PlayerManager::getSession($member = PlayerManager::getPlayerExact($member))) !== null){
						$member->sendTitle($msg, "", 5, 20, 5);
						if($this->party2 !== $party){
							$member->broadcastSound(new XpCollectSound(), [$member]);
						}
						$sbInfo = $msession->getScoreboardInfo();
						$sbInfo->updateLineOfScoreboard(2, PracticeCore::COLOR . "   You: " . TextFormat::WHITE . $this->extraScores[$this->party2]);
						$sbInfo->updateLineOfScoreboard(3, PracticeCore::COLOR . "   Them: " . TextFormat::WHITE . $this->extraScores[$this->party1]);
					}
				}
				$party1Line = PracticeCore::COLOR . "   $this->party1: " . TextFormat::WHITE . ($this->party1 === $party ? $this->extraScores[$this->party1] : $this->extraScores[$this->party2]);
				$party2Line = PracticeCore::COLOR . "   $this->party2: " . TextFormat::WHITE . ($this->party1 === $party ? $this->extraScores[$this->party2] : $this->extraScores[$this->party1]);
				foreach($this->spectators as $spectator){
					if(($specSession = PlayerManager::getSession($spec = PlayerManager::getPlayerExact($spectator, true))) !== null){
						$spec->sendTitle($msg, "", 5, 20, 5);
						$sbInfo = $specSession->getScoreboardInfo();
						$sbInfo->updateLineOfScoreboard(2, $party1Line);
						$sbInfo->updateLineOfScoreboard(3, $party2Line);
					}
				}
				if($this->extraScores[$party] === 0){
					$this->setEnded($this->getOpponent($party));
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
				if($this->extraFlag[PartyManager::getPartyFromName($this->party1)?->isPlayer($player) ? $this->party1 : $this->party2]){
					if($this->deathsCountdown[$name] === 0){
						$this->deathsCountdown[$name] = 4;
						PlayerManager::getSession($player)->getKitHolder()->clearKit();
						$player->setGamemode(GameMode::SPECTATOR());
						return true;
					}else{
						PracticeUtil::teleport($player, $this->centerPosition);
					}
				}else{
					if($this->getTeam($player) !== null){
						$this->removeFromTeam($player);
					}else{
						PracticeUtil::teleport($player, $this->centerPosition);
					}
				}
			}
		}
		return false;
	}

	public function getParty1() : string{
		return $this->party1;
	}

	public function getParty2() : string{
		return $this->party2;
	}

	public function getTeam1() : PracticeTeam{
		return $this->team1;
	}

	public function getTeam2() : PracticeTeam{
		return $this->team2;
	}

	public function getOpponent($party) : ?PracticeParty{
		if($this->isParty($party)){
			return ($party instanceof PracticeParty ? $party->getName() : $party) === $this->party1 ? PartyManager::getPartyFromName($this->party2) : PartyManager::getPartyFromName($this->party1);
		}
		return null;
	}

	public function isPlayer(string|Player $player) : bool{
		$name = $player instanceof Player ? $player->getName() : $player;
		return $this->team1->isInTeam($name) || $this->team2->isInTeam($name);
	}

	public function isParty($party) : bool{
		$name = $party instanceof PracticeParty ? $party->getName() : $party;
		return $this->party1 === $name || $this->party2 === $name;
	}

	public function getKit() : string{
		return $this->kit;
	}

	public function getSize() : int{
		return $this->size;
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

	public function destroyCycles() : void{
		$this->world = null;
		$this->centerPosition = null;
		$this->winner = null;
		$this->loser = null;
		$this->spectators = [];
		$this->team1 = null;
		$this->team2 = null;
		$this->numHits = [];
		$this->extraScores = [];
		$this->kills = [];
		$this->deathsCountdown = [];
		$this->extraFlag = [];
		$this->chunks = [];
		$this->backup = [];
		$this->blocksRemover = [];
		ArenaManager::getArena($this->arena)?->setPreWorldAsAvailable($this->worldId);
	}
}
