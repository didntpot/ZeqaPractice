<?php

declare(strict_types=1);

namespace zodiax\game\entity\projectile;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Arrow as PMArrow;
use pocketmine\math\RayTraceResult;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\Particle;
use pocketmine\world\sound\XpCollectSound;
use zodiax\player\PlayerManager;
use zodiax\utils\ParticleInformation;
use function round;

class Arrow extends PMArrow{

	protected $damage = 1.0;
	protected ?Particle $projectile = null;
	/** @var Player[]|null */
	private ?array $players;

	/**
	 * @param Player[]|null $players
	 */
	public function __construct(Location $location, ?Entity $shootingEntity, bool $critical, ?CompoundTag $nbt = null, ?array $players = null){
		parent::__construct($location, $shootingEntity, $critical, $nbt);
		$this->players = $players;
		if($this->players !== null){
			foreach($this->players as $p){
				$this->spawnTo($p);
			}
		}else{
			$this->spawnToAll();
		}
		if($shootingEntity !== null){
			$this->setMotion($shootingEntity->getDirectionVector());
		}
		if($shootingEntity instanceof Player && ($session = PlayerManager::getSession($shootingEntity)) !== null){
			$this->projectile = ParticleInformation::getInformation((int) $session->getItemInfo()?->getProjectile(true))?->getParticle();
		}
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		$hasUpdate = parent::entityBaseTick($tickDiff);
		$owner = $this->getOwningEntity();
		if($owner === null || !$owner->isAlive() || $owner->isClosed() || $owner->getWorld() !== $this->getWorld() || $this->ticksLived > 60){
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

	protected function onHitBlock(Block $blockHit, RayTraceResult $hitResult) : void{
		parent::onHitBlock($blockHit, $hitResult);
		$this->flagForDespawn();
	}

	protected function onHitEntity(Entity $entityHit, RayTraceResult $hitResult) : void{
		parent::onHitEntity($entityHit, $hitResult);
		if(($owner = $this->getOwningEntity()) !== null && $owner instanceof Player && !$entityHit->isSilent()){
			if($entityHit instanceof Player && ($session = PlayerManager::getSession($entityHit)) !== null && ($kit = $session->getKitHolder()?->getKit()) !== null && $kit->getName() !== "OITC" && $kit->getName() !== "Knock"){
				if($owner->getId() !== $entityHit->getId()){
					$owner->broadcastSound(new XpCollectSound(), [$owner]);
					$owner->sendMessage(TextFormat::RED . $entityHit->getDisplayName() . TextFormat::YELLOW . " is now on " . TextFormat::RED . round($entityHit->getHealth() / 2, 1) . TextFormat::YELLOW . " HP!");
				}
				PlayerManager::getSession($owner)?->getDuel()?->addProjectileHit($owner, "arrowsHit");
			}
		}
	}
}