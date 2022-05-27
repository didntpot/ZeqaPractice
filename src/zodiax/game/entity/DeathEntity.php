<?php

declare(strict_types=1);

namespace zodiax\game\entity;

use pocketmine\entity\Human;
use pocketmine\player\Player;
use pocketmine\timings\Timings;
use pocketmine\world\format\Chunk;
use function abs;
use function get_class;
use function spl_object_id;

class DeathEntity extends Human{

	public function spawnTo(Player $player) : void{

	}

	public function spawnToSpecifyPlayer(Player $player) : void{
		$id = spl_object_id($player);
		//TODO: this will cause some visible lag during chunk resends; if the player uses a spawn egg in a chunk, the
		//created entity won't be visible until after the resend arrives. However, this is better than possibly crashing
		//the player by sending them entities too early.
		if(!isset($this->hasSpawned[$id]) && $player->getWorld() === $this->getWorld() && $player->hasReceivedChunk($this->location->getFloorX() >> Chunk::COORD_BIT_SIZE, $this->location->getFloorZ() >> Chunk::COORD_BIT_SIZE)){
			$this->hasSpawned[$id] = $player;

			$this->sendSpawnPacket($player);
		}
	}

	public function onUpdate(int $currentTick) : bool{
		if($this->closed){
			return false;
		}
		$tickDiff = $currentTick - $this->lastUpdate;
		if($tickDiff <= 0){
			if(!$this->justCreated){
				$this->server->getLogger()->debug("Expected tick difference of at least 1, got $tickDiff for " . get_class($this));
			}
			return true;
		}
		$this->lastUpdate = $currentTick;
		if(!$this->isAlive()){
			if($this->onDeathUpdate($tickDiff)){
				$this->flagForDespawn();
				return true;
			}
		}
		$this->timings->startTiming();
		if($this->hasMovementUpdate()){
			$this->tryChangeMovement();
			$this->motion = $this->motion->withComponents(
				abs($this->motion->x) <= self::MOTION_THRESHOLD ? 0 : null,
				abs($this->motion->y) <= self::MOTION_THRESHOLD ? 0 : null,
				abs($this->motion->z) <= self::MOTION_THRESHOLD ? 0 : null
			);
			if($this->motion->x != 0 || $this->motion->y != 0 || $this->motion->z != 0){
				$this->move($this->motion->x, $this->motion->y, $this->motion->z);
			}
			$this->forceMovementUpdate = false;
		}
		$this->updateMovement();
		Timings::$entityBaseTick->startTiming();
		$hasUpdate = $this->entityBaseTick($tickDiff);
		Timings::$entityBaseTick->stopTiming();
		$this->timings->stopTiming();
		return ($hasUpdate || $this->hasMovementUpdate());
	}
}
