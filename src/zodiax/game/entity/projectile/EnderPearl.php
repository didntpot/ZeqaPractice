<?php

declare(strict_types=1);

namespace zodiax\game\entity\projectile;

use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\EnderPearl as PMEnderPearl;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\world\particle\EndermanTeleportParticle;
use pocketmine\world\particle\Particle;
use pocketmine\world\sound\EndermanTeleportSound;
use zodiax\player\PlayerManager;
use zodiax\PracticeUtil;
use zodiax\utils\ParticleInformation;

class EnderPearl extends PMEnderPearl{

	protected $gravity = 0.065;
	protected $drag = 0.0085;
	protected ?Particle $projectile = null;
	/** @var Player[]|null */
	private ?array $players;

	/**
	 * @param Player[]|null $players
	 */
	public function __construct(Location $location, ?Human $thrower, ?CompoundTag $nbt = null, ?array $players = null){
		parent::__construct($location, $thrower, $nbt);
		$this->players = $players;
		if($this->players !== null){
			foreach($this->players as $p){
				$this->spawnTo($p);
			}
		}else{
			$this->spawnToAll();
		}
		if($thrower !== null){
			$this->setMotion($thrower->getDirectionVector()->multiply(2.35));
		}
		if($thrower instanceof Player && ($session = PlayerManager::getSession($thrower)) !== null){
			$this->projectile = ParticleInformation::getInformation((int) $session->getItemInfo()?->getProjectile(true))?->getParticle();
		}
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		$hasUpdate = parent::entityBaseTick($tickDiff);
		$owner = $this->getOwningEntity();
		if($owner === null || !$owner->isAlive() || $owner->isClosed() || $owner->getWorld() !== $this->getWorld() || $this->ticksLived > 60 || ($owner instanceof Player && ($session = PlayerManager::getSession($owner)) !== null && $session->isInHub())){
			$this->close();
		}
		if($this->projectile !== null && !$this->isFlaggedForDespawn() && $this->isAlive()){
			$this->getWorld()->addParticle($this->lastLocation->subtractVector($this->lastMotion), $this->projectile, $this->players);
		}
		return $hasUpdate;
	}

	public function canCollideWith(Entity $entity) : bool{
		$player = $this->getOwningEntity();
		if($player instanceof Player && $entity instanceof Player && $player->getName() !== $entity->getName() && ($session = PlayerManager::getSession($player)) !== null && ($esession = PlayerManager::getSession($entity)) !== null && ($esession->isVanish() || (($arena = $esession->getArena()) !== null && !$arena->canInterrupt() && (($esession->isInCombat() && !$session->isInCombat()) || ($session->isInCombat() && ($target = $session->getTarget()) !== null && $target->getName() !== $entity->getName()))) || (($partyDuel = $session->getPartyDuel()) !== null && ($team = $partyDuel->getTeam($player)) !== null && $team->isInTeam($entity)))){
			return false;
		}
		return parent::canCollideWith($entity);
	}

	protected function onHit(ProjectileHitEvent $event) : void{
		if(($owner = $this->getOwningEntity()) !== null){
			$session = null;
			if($owner instanceof Player && ($session = PlayerManager::getSession($owner)) !== null && $session->isInHub()){
				return;
			}
			if($event instanceof ProjectileHitEntityEvent){
				$session?->setAgroPearl();
			}
			$world = $this->getWorld();
			$world->addParticle($origin = $owner->getPosition(), new EndermanTeleportParticle(), $this->players);
			$world->addSound($origin, new EndermanTeleportSound(), $this->players);
			$target = $event->getRayTraceResult()->getHitVector();
			PracticeUtil::onChunkGenerated($world, $target->getFloorX() >> 4, $target->getFloorZ() >> 4, function() use ($world, $owner, $session, $target){
				if($owner instanceof Player && $session?->getSettingsInfo()?->isSmoothPearl()){
					$owner->setPosition($target);
					$owner->broadcastMovement(true);
					$owner->getNetworkSession()->syncMovement($location = $owner->getLocation(), $location->yaw, $location->pitch);
				}else{
					PracticeUtil::teleport($owner, $target);
				}
				$world->addSound($target, new EndermanTeleportSound(), $this->players);
			});
		}
	}
}
