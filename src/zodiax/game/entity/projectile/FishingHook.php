<?php

declare(strict_types=1);

namespace zodiax\game\entity\projectile;

use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Projectile;
use pocketmine\item\ItemIds;
use pocketmine\math\RayTraceResult;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\types\ActorEvent;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Random;
use pocketmine\world\particle\Particle;
use pocketmine\world\sound\XpCollectSound;
use zodiax\game\behavior\fishing\IFishingBehaviorEntity;
use zodiax\player\PlayerManager;
use zodiax\utils\ParticleInformation;

class FishingHook extends Projectile{

	protected $gravity = 0.09;
	protected $drag = 0.05;
	protected bool $caught = false;
	protected ?Entity $attachedEntity = null;
	protected ?Particle $projectile = null;
	/** @var Player[]|null */
	private ?array $players;

	public static function getNetworkTypeId() : string{
		return EntityIds::FISHING_HOOK;
	}

	protected function getInitialSizeInfo() : EntitySizeInfo{
		return new EntitySizeInfo(0.25, 0.25);
	}

	/**
	 * @param Player[]|null $players
	 */
	public function __construct(Location $location, ?Entity $shootingEntity, ?CompoundTag $nbt = null, ?array $players = null){
		parent::__construct($location, $shootingEntity, $nbt);
		$this->players = $players;
		if($this->players !== null){
			foreach($this->players as $p){
				$this->spawnTo($p);
			}
		}else{
			$this->spawnToAll();
		}
		if($shootingEntity !== null){
			$this->setMotion($shootingEntity->getDirectionVector()->multiply(0.4));
		}
		$this->handleHookCasting($this->motion->x, $this->motion->y, $this->motion->z, 2.5, 1);
		if($shootingEntity instanceof Player && ($session = PlayerManager::getSession($shootingEntity)) !== null){
			$this->projectile = ParticleInformation::getInformation((int) $session->getItemInfo()?->getProjectile(true))?->getParticle();
		}
	}

	public function handleHookCasting(float $x, float $y, float $z, float $f1, float $f2) : void{
		$rand = new Random();
		$f = sqrt($x * $x + $y * $y + $z * $z);
		$x /= $f;
		$y /= $f;
		$z /= $f;
		$x = $x + $rand->nextSignedFloat() * 0.007499999832361937 * $f2;
		$y = $y + $rand->nextSignedFloat() * 0.007499999832361937 * $f2;
		$z = $z + $rand->nextSignedFloat() * 0.007499999832361937 * $f2;
		$x *= $f1;
		$y *= 1.5;
		$z *= $f1;
		$this->motion->x = $x;
		$this->motion->y = $y;
		$this->motion->z = $z;
	}

	public function onUpdate(int $currentTick) : bool{
		$hasUpdate = parent::onUpdate($currentTick);
		if(!$this->isFlaggedForDespawn() && $this->isAlive()){
			if(($owner = $this->getOwningEntity()) === null || ($owner instanceof Human && ($owner->getPosition()->distance($this->getPosition()) > 35 || $owner->getInventory()->getItemInHand()->getId() !== ItemIds::FISHING_ROD))){
				if(($owner instanceof Player && ($owner = PlayerManager::getSession($owner)) !== null) || $owner instanceof IFishingBehaviorEntity){
					$owner->getFishingBehavior()?->stopFishing();
				}
				if(!$this->closed){
					$this->close();
				}
			}
		}
		if($this->projectile !== null && !$this->isFlaggedForDespawn() && $this->isAlive() && !$this->isCollided){
			$this->getWorld()->addParticle($this->lastLocation->subtractVector($this->lastMotion), $this->projectile, $this->players);
		}
		return $hasUpdate;
	}

	public function reelLine() : void{
		$owner = $this->getOwningEntity();
		if($owner instanceof Human && $this->attachedEntity !== null){
			Server::getInstance()->broadcastPackets($this->players, [ActorEventPacket::create($this->getId(), ActorEvent::FISH_HOOK_TEASE, 0)]); // @phpstan-ignore-line
		}
		if(!$this->closed){
			$this->close();
		}
	}

	public function canCollideWith(Entity $entity) : bool{
		$player = $this->getOwningEntity();
		if($player instanceof Player && $entity instanceof Player && $player->getName() !== $entity->getName() && ($session = PlayerManager::getSession($player)) !== null && ($esession = PlayerManager::getSession($entity)) !== null && ($esession->isVanish() || (($arena = $esession->getArena()) !== null && !$arena->canInterrupt() && (($esession->isInCombat() && !$session->isInCombat()) || ($session->isInCombat() && ($target = $session->getTarget()) !== null && $target->getName() !== $entity->getName()))) || (($partyDuel = $session->getPartyDuel()) !== null && ($team = $partyDuel->getTeam($player)) !== null && $team->isInTeam($entity)))){
			return false;
		}
		return parent::canCollideWith($entity);
	}

	protected function onHitEntity(Entity $entityHit, RayTraceResult $hitResult) : void{
		parent::onHitEntity($entityHit, $hitResult);
		$this->attachedEntity = $entityHit;
		if(($owner = $this->getOwningEntity()) !== null && !$entityHit->isSilent()){
			if(($owner instanceof Player && ($owner = PlayerManager::getSession($owner)) !== null) || $owner instanceof IFishingBehaviorEntity){
				$owner->getFishingBehavior()?->stopFishing();
			}
			if($owner instanceof Player && $owner->getId() !== $entityHit->getId()){
				$owner->broadcastSound(new XpCollectSound(), [$owner]);
				PlayerManager::getSession($owner)?->getDuel()?->addProjectileHit($owner, "rodHit");
			}
		}
	}
}
