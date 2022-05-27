<?php

declare(strict_types=1);

namespace zodiax\training\types;

use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\sound\ClickSound;
use pocketmine\world\sound\XpCollectSound;
use pocketmine\world\World;
use zodiax\arena\ArenaManager;
use zodiax\arena\types\TrainingArena;
use zodiax\game\entity\DummyBot;
use zodiax\game\items\ItemHandler;
use zodiax\game\world\BlockRemoverHandler;
use zodiax\game\world\thread\ChunkCache;
use zodiax\kits\DefaultKit;
use zodiax\kits\KitsManager;
use zodiax\player\info\scoreboard\ScoreboardInfo;
use zodiax\player\misc\VanishHandler;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\training\TrainingHandler;
use function sqrt;

class ReducePractice{

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
	private string $playerName;
	private ?DummyBot $dummyBot;
	private array $spectators;
	private array $chunks;
	private int $attackSpeed;
	private int $attackTime;
	private int $extraHitDelaySeconds;
	private bool $extraHit;
	private int $blocks;
	private ?Vector3 $lastBlock;

	public function __construct(int $worldId, Player $player, DefaultKit $kit, TrainingArena $arena){
		$this->status = self::STATUS_STARTING;
		$this->currentTick = 0;
		$this->countdownSeconds = 3;
		$this->kit = $kit->getName();
		$this->arena = $arena->getName();
		$this->worldId = $worldId;
		/** @var TrainingArena $arena */
		$arena = ArenaManager::getArena($this->arena);
		$this->world = ArenaManager::MAPS_MODE === ArenaManager::ADVANCE ? $arena->getWorld(true) : Server::getInstance()->getWorldManager()->getWorldByName("duel" . $this->worldId);
		$spawnPos1 = $arena->getP1Spawn();
		$spawnPos2 = $arena->getP2Spawn();
		$this->centerPosition = new Position((int) (($spawnPos2->getX() + $spawnPos1->getX()) / 2), $spawnPos1->getY(), (int) (($spawnPos2->getZ() + $spawnPos1->getZ()) / 2), $this->world);
		$this->playerName = $player->getName();
		PracticeUtil::onChunkGenerated($this->world, $spawnPos2->getFloorX() >> 4, $spawnPos2->getFloorZ() >> 4, function() use ($player, $spawnPos1, $spawnPos2){
			$this->dummyBot = new DummyBot(Location::fromObject($spawnPos2, $this->world), $player->getSkin());
			$this->dummyBot->setNameTag(TextFormat::YELLOW . "Zeqa.net");
			$this->dummyBot->setNameTagVisible();
			$this->dummyBot->setNameTagAlwaysVisible();
			$this->dummyBot->setImmobile();
			$this->dummyBot->getKitHolder()->setKit($this->kit);
			$this->dummyBot->spawnTo($player);
			PracticeUtil::teleport($this->dummyBot, $spawnPos2, $spawnPos1);
		});
		$this->spectators = [];
		$this->chunks = [];
		$this->attackSpeed = $kit->getKnockbackInfo()->getSpeed();
		$this->attackTime = 20;
		$this->extraHitDelaySeconds = 1;
		$this->extraHit = false;
		$this->blocks = 0;
		$this->lastBlock = null;
	}

	public function update() : void{
		if(!isset($this->dummyBot)){
			return;
		}
		if(($session = PlayerManager::getSession($player = PlayerManager::getPlayerExact($this->playerName))) === null || $this->dummyBot->isFlaggedForDespawn() || $this->dummyBot->isClosed()){
			$this->setEnded();
			return;
		}
		$this->currentTick++;
		switch($this->status){
			case self::STATUS_STARTING:
				if($this->currentTick % 20 === 0){
					if($this->countdownSeconds === 3){
						$this->setInReduce();
						$player->sendTitle(TextFormat::RED . "Starting in 3", "", 5, 20, 5);
						$player->broadcastSound(new ClickSound(), [$player]);
					}elseif($this->countdownSeconds > 0 && $this->countdownSeconds < 5){
						$player->sendTitle(TextFormat::RED . $this->countdownSeconds . "...", "", 5, 20, 5);
						$player->broadcastSound(new ClickSound(), [$player]);
					}elseif($this->countdownSeconds === 0){
						$player->sendTitle(TextFormat::RED . "Reduce!", "", 5, 20, 5);
						$player->broadcastSound(new XpCollectSound(), [$player]);
						$this->status = self::STATUS_IN_PROGRESS;
						$this->countdownSeconds = 2;
					}
					$this->countdownSeconds--;
				}
				break;
			case self::STATUS_IN_PROGRESS:
				$session->getClicksInfo()->update();
				if($this->currentTick % 20 === 0){
					$playerPos = $player->getPosition()->asVector3();
					$botPos = $this->dummyBot->getPosition()->asVector3();
					$distance = $this->distance($playerPos, $botPos);
					if($playerPos->getFloorY() < $botPos->getFloorY() - 3 || $playerPos->getFloorY() > 256){
						$this->resetReduce();
						return;
					}elseif($this->lastBlock === null && $distance > 5){
						$this->resetReduce();
						return;
					}elseif($this->lastBlock !== null){
						if($playerPos->getFloorY() < $this->lastBlock->getFloorY() - 3 || $this->distance($playerPos, $this->lastBlock) > 5){
							$this->resetReduce();
							return;
						}
					}
					$blocks = PracticeCore::COLOR . " Blocks: " . TextFormat::WHITE . $this->blocks;
					$distance = PracticeCore::COLOR . " Distance: " . TextFormat::WHITE . (int) $distance;
					$sbInfo = $session->getScoreboardInfo();
					$sbInfo->updateLineOfScoreboard(2, $blocks);
					$sbInfo->updateLineOfScoreboard(3, $distance);
					foreach($this->spectators as $spec){
						$spectator = PlayerManager::getPlayerExact($spec, true);
						if($spectator !== null){
							$sbInfo = PlayerManager::getSession($spectator)->getScoreboardInfo();
							$sbInfo->updateLineOfScoreboard(2, $blocks);
							$sbInfo->updateLineOfScoreboard(3, $distance);
						}else{
							$this->removeSpectator($spec);
						}
					}
					$inv = $player->getInventory();
					if($inv !== null){
						$block = VanillaBlocks::SANDSTONE()->asItem()->setCount(64);
						foreach($inv->getContents() as $slot => $item){
							if($item->getId() === BlockLegacyIds::SANDSTONE){
								$inv->setItem($slot, $block);
							}
						}
					}
				}
				$this->dummyBot->lookAt($player->getEyePos());
				if($this->attackTime === 0){
					$this->dummyBot->attackEntity($player);
					$this->attackTime = $this->attackSpeed;
					if($this->extraHit){
						$this->attackTime += PracticeUtil::secondsToTicks($this->extraHitDelaySeconds);
						$this->extraHit = false;
					}
				}else{
					$this->attackTime--;
				}
				break;
			case self::STATUS_ENDING:
				if($this->currentTick % 20 === 0 && --$this->countdownSeconds === 0){
					$this->status = self::STATUS_PREPARING;
					$this->countdownSeconds = 3;
				}
				break;
		}
	}

	private function setInReduce() : void{
		/** @var TrainingArena $arena */
		$arena = ArenaManager::getArena($this->arena);
		$playerPos = Position::fromObject($arena->getP1Spawn(), $this->world);
		if(($session = PlayerManager::getSession($player = PlayerManager::getPlayerExact($this->playerName))) !== null){
			$player->setLastDamageCause(new EntityDamageEvent($player, EntityDamageEvent::CAUSE_MAGIC, 0));
			PracticeUtil::onChunkGenerated($this->world, $playerPos->getFloorX() >> 4, $playerPos->getFloorZ() >> 4, function() use ($player, $session, $playerPos){
				PracticeUtil::teleport($player, $playerPos, $this->dummyBot->getPosition());
				$this->dummyBot->lookAt($player->getEyePos());
				$session->getKitHolder()->setKit(KitsManager::getKit($this->kit));
				$session->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_DUEL);
			});
		}
		$blocks = PracticeCore::COLOR . " Blocks: " . TextFormat::WHITE . 0;
		$distance = PracticeCore::COLOR . " Distance: " . TextFormat::WHITE . 0;
		foreach($this->spectators as $spec){
			$spectator = PlayerManager::getPlayerExact($spec, true);
			if($spectator !== null){
				$sbInfo = PlayerManager::getSession($spectator)->getScoreboardInfo();
				$sbInfo->updateLineOfScoreboard(2, $blocks);
				$sbInfo->updateLineOfScoreboard(3, $distance);
			}else{
				$this->removeSpectator($spec);
			}
		}
	}

	public function setAsStarting() : void{
		$this->status = self::STATUS_STARTING;
		$this->countdownSeconds = 3;
	}

	public function resetReduce() : void{
		$this->status = self::STATUS_ENDING;
		$this->attackTime = 0;
		$this->extraHit = false;
		$this->blocks = 0;
		$this->lastBlock = null;
		if($this->world instanceof World){
			BlockRemoverHandler::removeBlocks($this->world, $this->chunks);
			$this->chunks = [];
		}
		if(($session = PlayerManager::getSession($player = PlayerManager::getPlayerExact($this->playerName))) !== null){
			/** @var TrainingArena $arena */
			$arena = ArenaManager::getArena($this->arena);
			PracticeUtil::teleport($player, $arena->getP1Spawn());
			$player->lookAt($this->dummyBot->getPosition(), $player->getEyePos());
			$session->getKitHolder()->clearKit();
			ItemHandler::spawnReduceItems($player);
		}
	}

	public function setEnded() : void{
		if($this->status !== self::STATUS_ENDED){
			$this->status = self::STATUS_ENDED;
			$this->dummyBot->despawnFromAll();
			$this->dummyBot->close();
			PlayerManager::getSession(PlayerManager::getPlayerExact($this->playerName))?->reset();
			foreach($this->spectators as $spec){
				if(($spectator = PlayerManager::getPlayerExact($spec, true)) !== null){
					PlayerManager::getSession($spectator)->reset();
				}
			}
			if($this->world instanceof World){
				if(ArenaManager::MAPS_MODE !== ArenaManager::NORMAL){
					BlockRemoverHandler::removeBlocks($this->world, $this->chunks);
				}
			}
			TrainingHandler::removeReduce($this->worldId);
		}
	}

	public function getPlayer() : string{
		return $this->playerName;
	}

	public function isPlayer(string|Player $player) : bool{
		return ($player instanceof Player ? $player->getName() : $player) === $this->playerName;
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
				$this->dummyBot->spawnTo($player);
			});
			$msg = PracticeCore::PREFIX . TextFormat::GREEN . $name . TextFormat::GRAY . " is now spectating";
			PlayerManager::getPlayerExact($this->playerName)?->sendMessage($msg);
			foreach($this->spectators as $spec){
				PlayerManager::getPlayerExact($spec, true)?->sendMessage($msg);
			}
		}
	}

	public function removeSpectator(string $name) : void{
		if(isset($this->spectators[$name])){
			PlayerManager::getSession(PlayerManager::getPlayerExact($name, true))?->reset();
			$msg = PracticeCore::PREFIX . TextFormat::RED . $name . TextFormat::GRAY . " is no longer spectating";
			PlayerManager::getPlayerExact($this->playerName)?->sendMessage($msg);
			foreach($this->spectators as $spec){
				PlayerManager::getPlayerExact($spec, true)?->sendMessage($msg);
			}
			unset($this->spectators[$name]);
		}
	}

	public function isSpectator(string|Player $player) : bool{
		return isset($this->spectators[$player instanceof Player ? $player->getDisplayName() : $player]);
	}

	public function tryBreakOrPlaceBlock(Player $player, Block $block, bool $break = true) : bool{
		if($this->isPlayer($player) && $this->isRunning()){
			if($break){
				if($block->getId() !== BlockLegacyIds::SANDSTONE){
					return false;
				}
				$pos = $block->getPosition();
				if(!isset($this->chunks[$hash = World::chunkHash($pos->getFloorX() >> 4, $pos->getFloorZ() >> 4)])){
					$this->chunks[$hash] = new ChunkCache();
				}
				$this->chunks[$hash]->removeBlock($block, $pos);
			}else{
				$this->blocks++;
				$this->lastBlock = $pos = $block->getPosition();
				if(!isset($this->chunks[$hash = World::chunkHash($pos->getFloorX() >> 4, $pos->getFloorZ() >> 4)])){
					$this->chunks[$hash] = new ChunkCache();
				}
				$this->chunks[$hash]->addBlock(VanillaBlocks::AIR(), $pos);
			}
			return true;
		}
		return false;
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

	public function isRunning() : bool{
		return $this->status === self::STATUS_IN_PROGRESS;
	}

	public function addExtraHitDelay() : void{
		$this->extraHit = true;
	}

	public function getExtraHitDelaySeconds() : int{
		return $this->extraHitDelaySeconds;
	}

	public function setExtraHitDelaySeconds(int $extraHitDelaySeconds) : void{
		$this->extraHitDelaySeconds = $extraHitDelaySeconds;
	}

	private function distance(Vector3 $vec1, Vector3 $vec2) : float{
		return sqrt((($vec1->x - $vec2->x) ** 2) + (($vec1->z - $vec2->z) ** 2));
	}

	public function destroyCycles() : void{
		$this->world = null;
		$this->centerPosition = null;
		$this->dummyBot = null;
		$this->spectators = [];
		$this->chunks = [];
		$this->lastBlock = null;
		ArenaManager::getArena($this->arena)?->setPreWorldAsAvailable($this->worldId);
	}
}
