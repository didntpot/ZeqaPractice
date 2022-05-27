<?php

declare(strict_types=1);

namespace zodiax\duel\types;

use pocketmine\block\BlockLegacyIds;
use pocketmine\entity\Location;
use pocketmine\inventory\ArmorInventory;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\Position;
use pocketmine\world\World;
use zodiax\arena\ArenaManager;
use zodiax\arena\types\DuelArena;
use zodiax\duel\ReplayHandler;
use zodiax\game\entity\replay\IReplayEntity;
use zodiax\game\entity\replay\ReplayHuman;
use zodiax\game\items\ItemHandler;
use zodiax\game\world\BlockRemoverHandler;
use zodiax\game\world\thread\ChunkCache;
use zodiax\player\info\duel\data\PlayerReplayData;
use zodiax\player\info\duel\ReplayInfo;
use zodiax\player\info\scoreboard\ScoreboardInfo;
use zodiax\player\misc\VanishHandler;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function abs;

class DuelReplay{

	private string $spectator;
	private ?ReplayHuman $humanA;
	private ?ReplayHuman $humanB;
	private ?ReplayInfo $info;
	private int $worldId;
	private ?World $world;
	private ?Position $centerPosition;
	private int $currentTick;
	private int $prevReplayIntegerTick;
	private float $currentReplayTick;
	private int|float $replaySecs;
	private bool $paused;
	private array $chunks;

	public function __construct(int $worldId, Player $spectator, ReplayInfo $info){
		$this->spectator = $spectator->getName();
		$this->humanA = null;
		$this->humanB = null;
		$this->info = $info;
		$this->worldId = $worldId;
		$this->currentTick = 0;
		$this->prevReplayIntegerTick = 0;
		$this->currentReplayTick = 0.0;
		$this->replaySecs = 5;
		$this->paused = false;
		$this->chunks = [];
		/** @var DuelArena $arena */
		$arena = ArenaManager::getArena($this->info->getArena());
		$this->world = ArenaManager::MAPS_MODE === ArenaManager::ADVANCE ? $arena->getWorld(true) : Server::getInstance()->getWorldManager()->getWorldByName("duel" . $this->worldId);
		$spawnPos1 = $arena->getP1Spawn();
		$spawnPos2 = $arena->getP2Spawn();
		$this->centerPosition = new Position((int) (($spawnPos2->getX() + $spawnPos1->getX()) / 2), $spawnPos1->getY(), (int) (($spawnPos2->getZ() + $spawnPos1->getZ()) / 2), $this->world);
	}

	public function update() : void{
		if(($spectator = PlayerManager::getPlayerExact($this->spectator)) === null){
			$this->setEnded();
			return;
		}
		$this->currentTick++;
		$prevStartReplayCount = $this->canStartReplayCount();
		if(!$this->canStartReplayCount() && $this->currentTick === 5){
			PracticeUtil::onChunkGenerated($this->world, $this->centerPosition->getFloorX() >> 4, $this->centerPosition->getFloorZ() >> 4, function() use ($spectator){
				PracticeUtil::teleport($spectator, $this->centerPosition);
				VanishHandler::addToVanish($spectator);
				ItemHandler::spawnReplayItems($spectator);
				PlayerManager::getSession($spectator)->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_REPLAY);
			});
		}else{
			if($prevStartReplayCount !== true){
				$this->loadEntities();
			}
			if(!$this->paused && $this->currentReplayTick <= $this->info->getEndTick()){
				$this->currentReplayTick++;
				$this->updateHuman($this->humanA, $this->info->getPlayerAData());
				$this->updateHuman($this->humanB, $this->info->getPlayerBData());
				$this->updateWorld(false);
			}
			$this->prevReplayIntegerTick = $this->getReplayTickAsInteger();
		}
		if($this->currentTick % 20 === 0){
			PlayerManager::getSession($spectator)?->getScoreboardInfo()->updateLineOfScoreboard(4, " " . $this->getDuration() . TextFormat::WHITE . " | " . $this->getMaxDuration() . " ");
		}
	}

	public function setEnded() : void{
		if(($session = PlayerManager::getSession($player = PlayerManager::getPlayerExact($this->spectator))) !== null){
			$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Teleport to hub");
			$session->reset();
		}
		$this->humanA?->close();
		$this->humanB?->close();
		$this->prevReplayIntegerTick = $this->getReplayTickAsInteger();
		if($this->world instanceof World){
			foreach($this->world->getEntities() as $entity){
				if($entity instanceof IReplayEntity){
					$entity->close();
				}
			}
			if(ArenaManager::MAPS_MODE !== ArenaManager::NORMAL){
				BlockRemoverHandler::removeBlocks($this->world, $this->chunks);
			}
		}
		ReplayHandler::deleteReplay($this->worldId);
	}

	private function updateHuman(ReplayHuman $human, PlayerReplayData $data) : void{
		if($human->isClosed()){
			return;
		}
		if($data->didDie()){
			if($this->currentReplayTick >= $data->getDeathTime() || $this->currentReplayTick >= $this->info->getEndTick()){
				$human->setInvisible();
				$human->getInventory()->setItemInHand(VanillaItems::AIR());
				$armorInv = $human->getArmorInventory();
				$armorInv->setHelmet(VanillaItems::AIR());
				$armorInv->setChestplate(VanillaItems::AIR());
				$armorInv->setLeggings(VanillaItems::AIR());
				$armorInv->setBoots(VanillaItems::AIR());
			}elseif($human->isInvisible()){
				$human->setInvisible(false);
			}
		}
		$attributes = $data->getAttributesAt($replayTickAsInteger = $this->getReplayTickAsInteger());
		if(isset($attributes["location"])){
			$human->setTargetPosition($location = $attributes["location"]);
			$human->setRotation($location->getYaw(), $location->getPitch());
		}
		if(abs($replayTickAsInteger - $this->prevReplayIntegerTick) > 0){
			if(isset($attributes["scoretag"])){
				$human->setScoreTag($attributes["scoretag"]);
			}
			if(isset($attributes["sneak"])){
				$human->setSneaking($attributes["sneak"]);
			}
			if(isset($attributes["item"])){
				$human->getInventory()->setItemInHand($attributes["item"]);
			}
			if(isset($attributes["armor"])){
				$this->doArmor($attributes["armor"], $human->getArmorInventory());
			}
			if(isset($attributes["thrown"])){
				switch($attributes["thrown"]){
					case "Ender Pearl":
						$human->throwPearl();
						break;
					case "Snowball":
						$human->throwSnowball();
						break;
					case "Strong Healing Splash Potion":
						$human->throwPotion();
						break;
				}
			}
			if(isset($attributes["fishing"])){
				$human->useRod($attributes["fishing"]);
			}
			if(isset($attributes["bow"])){
				$human->useBow($attributes["bow"]);
			}
			if(isset($attributes["animation"])){
				$spectator = PlayerManager::getPlayerExact($this->spectator);
				$server = Server::getInstance();
				$id = $human->getId();
				foreach($attributes["animation"] as $animation){
					$server->broadcastPackets([$spectator], [ActorEventPacket::create($id, $animation["event"], $animation["data"])]);
				}
			}
		}
	}

	private function updateHumanAfterTickChange(ReplayHuman $human, PlayerReplayData $replayPlayerData) : void{
		if($human->isClosed()){
			return;
		}
		if($replayPlayerData->didDie()){
			if($this->currentReplayTick >= $replayPlayerData->getDeathTime() || $this->currentReplayTick >= $this->info->getEndTick()){
				$human->setInvisible();
			}elseif($human->isInvisible()){
				$human->setInvisible(false);
			}
		}
		$attributes = $replayPlayerData->getAttributesAt($replayTickAsInteger = $this->getReplayTickAsInteger());
		if($this->currentReplayTick <= 0.0){
			PracticeUtil::teleport($human, $this->centerPosition);
		}elseif(isset($attributes["location"])){
			$location = $attributes["location"];
			PracticeUtil::teleport($human, $location->asVector3());
			$human->setTargetPosition($location);
			$human->setRotation($location->getYaw(), $location->getPitch());
		}
		if(isset($attributes["item"])){
			$human->getInventory()?->setItemInHand($attributes["item"]);
		}elseif(($lastItem = $replayPlayerData->getLastAttributeUpdate($replayTickAsInteger, "item")) !== null){
			$human->getInventory()?->setItemInHand($lastItem);
		}
		if(isset($attributes["armor"])){
			$this->doArmor($attributes["armor"], $human->getArmorInventory());
		}elseif(($armor = $replayPlayerData->getLastAttributeUpdate($replayTickAsInteger, "armor")) !== null){
			$this->doArmor($armor, $human->getArmorInventory());
		}
		if(isset($attributes["sneak"])){
			$human->setSneaking($attributes["sneak"]);
		}else{
			$human->setSneaking($replayPlayerData->getLastAttributeUpdate($replayTickAsInteger, "sneak") ?? false);
		}
	}

	public function updateWorld(bool $updatePause, bool $approximateBlockUpdates = false) : void{
		$blocks = $this->info->getWorldData()->getBlocksAt($this->getReplayTickAsInteger(), $approximateBlockUpdates);
		foreach($blocks as $block){
			/** @var Vector3 $position */
			$position = $block->getPosition();
			$block = $block->getBlock();
			$currentBlock = $this->world->getBlock($position);
			if($currentBlock->getId() !== $block->getId() || $currentBlock->getMeta() !== $block->getMeta()){
				PracticeUtil::onChunkGenerated($this->world, $position->getFloorX() >> 4, $position->getFloorZ() >> 4, function() use ($position, $block, $currentBlock){
					if($block->getId() === BlockLegacyIds::AIR){
						$this->world->addParticle($position->add(0.5, 0.5, 0.5), new BlockBreakParticle($currentBlock));
					}else{
						if(!isset($this->chunks[$hash = World::chunkHash($position->getFloorX() >> 4, $position->getFloorZ() >> 4)])){
							$this->chunks[$hash] = new ChunkCache();
						}
						$this->chunks[$hash]->addBlock($block, $position);
					}
					PracticeUtil::onChunkGenerated($this->world, $position->getFloorX() >> 4, $position->getFloorZ() >> 4, function() use ($position, $block){
						$this->world->setBlock($position, $block, false);
					});
				});
			}
		}
		$entities = $this->world->getEntities();
		foreach($entities as $entity){
			if($entity instanceof IReplayEntity){
				if($approximateBlockUpdates && !$entity instanceof ReplayHuman){
					$entity->flagForDespawn();
					continue;
				}
				$entity->setPaused($updatePause);
			}
		}
	}

	private function loadEntities() : void{
		if($this->humanA !== null && $this->humanB !== null){
			return;
		}
		/** @var DuelArena $arena */
		$arena = ArenaManager::getArena($this->info->getArena());
		if(!$this->world->isInLoadedTerrain($humanAPosition = $arena->getP1Spawn())){
			$this->world->loadChunk($humanAPosition->getFloorX() >> 4, $humanAPosition->getFloorZ() >> 4);
		}
		if(!$this->world->isInLoadedTerrain($humanBPosition = $arena->getP2Spawn())){
			$this->world->loadChunk($humanBPosition->getFloorX() >> 4, $humanBPosition->getFloorZ() >> 4);
		}
		$spectator = PlayerManager::getPlayerExact($this->spectator);
		$playerAData = $this->info->getPlayerAData();
		$this->humanA = new ReplayHuman(new Location($humanAPosition->getX(), $humanAPosition->getY(), $humanAPosition->getZ(), $this->world, 0, 0), $playerAData->getSkin());
		$this->humanA->setNameTag($playerAData->getName());
		$this->humanA->setNameTagAlwaysVisible();
		$this->humanA->spawnTo($spectator);
		$this->humanA->setPotColor($playerAData->getPotColor());
		$playerBData = $this->info->getPlayerBData();
		$this->humanB = new ReplayHuman(new Location($humanBPosition->getX(), $humanBPosition->getY(), $humanBPosition->getZ(), $this->world, 0, 0), $playerBData->getSkin());
		$this->humanB->setNameTag($playerBData->getName());
		$this->humanB->setNameTagAlwaysVisible();
		$this->humanB->spawnTo($spectator);
		$this->humanB->setPotColor($playerBData->getPotColor());
		$this->initInventories();
	}

	private function initInventories() : void{
		$this->initHumanInventory($this->humanA, $this->info->getPlayerAData());
		$this->initHumanInventory($this->humanB, $this->info->getPlayerBData());
	}

	private function initHumanInventory(ReplayHuman $human, PlayerReplayData $replayData){
		$human->getInventory()->setContents($replayData->getStartInventory());
		$human->getArmorInventory()->setContents($replayData->getStartArmorInventory());
	}

	private function doArmor(array $armor, ArmorInventory $armorInv) : void{
		if(isset($armor["helmet"])){
			$armorInv->setHelmet($armor["helmet"]);
		}
		if(isset($armor["chest"])){
			$armorInv->setChestplate($armor["chest"]);
		}
		if(isset($armor["pants"])){
			$armorInv->setLeggings($armor["pants"]);
		}
		if(isset($armor["boots"])){
			$armorInv->setBoots($armor["boots"]);
		}
	}

	public function getSpectator() : string{
		return $this->spectator;
	}

	public function setReplaySecs(float|int $secs) : void{
		$this->replaySecs = $secs;
	}

	public function getReplaySecs() : float{
		return $this->replaySecs;
	}

	private function getReplayTickAsInteger() : int{
		return (int) $this->currentReplayTick;
	}

	private function setTicks(float|int $ticks) : void{
		if(!$this->canStartReplayCount()){
			return;
		}
		if($ticks <= 0){
			$ticks = 0;
		}elseif($ticks >= $this->info->getEndTick()){
			$ticks = $this->info->getEndTick();
		}
		$this->currentReplayTick = $ticks;
		$prevReplayFloatTick = $this->currentReplayTick - 1;
		if($prevReplayFloatTick <= 0.0){
			$prevReplayFloatTick = 0.0;
		}
		$this->prevReplayIntegerTick = (int) $prevReplayFloatTick;
		if($this->humanA !== null && $this->humanB !== null){
			PlayerManager::getSession(PlayerManager::getPlayerExact($this->spectator))?->getScoreboardInfo()->updateLineOfScoreboard(4, " " . $this->getDuration() . TextFormat::WHITE . " | " . $this->getMaxDuration() . " ");
			$this->updateHumanAfterTickChange($this->humanA, $this->info->getPlayerAData());
			$this->updateHumanAfterTickChange($this->humanB, $this->info->getPlayerBData());
			$this->updateWorld($this->paused, true);
		}
		$this->prevReplayIntegerTick = $this->getReplayTickAsInteger();
	}

	public function fastForward() : void{
		$this->setTicks(($this->replaySecs * 20) + $this->currentReplayTick);
	}

	public function rewind() : void{
		$this->setTicks(-($this->replaySecs * 20) + $this->currentReplayTick);
	}

	private function canStartReplayCount() : bool{
		return $this->currentTick >= 20;
	}

	public function setPaused(bool $paused) : void{
		$session = PlayerManager::getSession($spectator = PlayerManager::getPlayerExact($this->spectator));
		if($this->paused){
			ItemHandler::givePauseItem($spectator);
			$session->getScoreboardInfo()->removePausedFromScoreboard();
		}else{
			ItemHandler::givePlayItem($spectator);
			$session->getScoreboardInfo()->addPausedToScoreboard();
		}
		$this->updateWorld($paused);
		$this->paused = $paused;
	}

	public function isPaused() : bool{
		return $this->paused;
	}

	public function isRanked() : bool{
		return $this->info->isRanked();
	}

	public function getArena() : string{
		return $this->info->getArena();
	}

	public function getCenterPosition() : Position{
		return $this->centerPosition;
	}

	public function getKit() : string{
		return $this->info->getKit();
	}

	public function getDuration() : string{
		return $this->duration((int) ($this->currentReplayTick / 20) - 5);
	}

	public function getMaxDuration() : string{
		return $this->duration((int) ($this->info->getEndTick() / 20) - 5);
	}

	public function duration(int $durationSeconds) : string{
		$seconds = $durationSeconds % 60;
		$minutes = (int) ($durationSeconds / 60);
		return ($minutes < 10 ? "0" . $minutes : $minutes) . ":" . ($seconds < 10 ? "0" . $seconds : $seconds);
	}

	public function destroyCycles() : void{
		$this->humanA = null;
		$this->humanB = null;
		$arena = $this->getArena();
		$this->info = null;
		$this->world = null;
		$this->centerPosition = null;
		ArenaManager::getArena($arena)?->setPreWorldAsAvailable($this->worldId);
	}
}
