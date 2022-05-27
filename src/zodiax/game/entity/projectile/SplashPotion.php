<?php

declare(strict_types=1);

namespace zodiax\game\entity\projectile;

use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\color\Color;
use pocketmine\entity\effect\InstantEffect;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\SplashPotion as PMSplashPotion;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\item\PotionType;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\world\particle\Particle;
use pocketmine\world\particle\PotionSplashParticle;
use pocketmine\world\sound\PotionSplashSound;
use zodiax\game\entity\replay\ReplayHuman;
use zodiax\player\PlayerManager;
use zodiax\utils\ParticleInformation;
use function count;
use function round;

class SplashPotion extends PMSplashPotion{

	protected $gravity = 0.065;
	protected $drag = 0.0025;
	protected ?Particle $projectile = null;
	/** @var Player[]|null */
	private ?array $players;

	/**
	 * @param Player[]|null $players
	 */
	public function __construct(Location $location, ?Human $thrower, PotionType $potionType, ?CompoundTag $nbt = null, ?array $players = null){
		parent::__construct($location, $thrower, $potionType, $nbt);
		$this->players = $players;
		if($this->players !== null){
			foreach($this->players as $p){
				$this->spawnTo($p);
			}
		}else{
			$this->spawnToAll();
		}
		if($thrower !== null){
			$this->setMotion($thrower->getDirectionVector()->multiply(0.5));
		}
		if($thrower instanceof Player && ($session = PlayerManager::getSession($thrower)) !== null){
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

	/** @noinspection PhpStatementHasEmptyBodyInspection */
	protected function onHit(ProjectileHitEvent $event) : void{
		$effects = $this->getPotionEffects();
		$hasEffects = true;
		if(count($effects) === 0){
			$particle = new PotionSplashParticle(PotionSplashParticle::DEFAULT_COLOR());
			$hasEffects = false;
		}else{
			$player = $this->getOwningEntity();
			if($player instanceof Player){
				/** @var string[] $potcolor */
				$potcolor = PlayerManager::getSession($player)?->getItemInfo()?->getPotColor() ?? ["255", "255", "255"];
				$colors = [new Color((int) $potcolor[0], (int) $potcolor[1], (int) $potcolor[2])];
			}elseif($player instanceof ReplayHuman){
				$potcolor = $player->getPotColor();
				$colors = [new Color((int) $potcolor[0], (int) $potcolor[1], (int) $potcolor[2])];
			}else{
				$colors = [];
				foreach($effects as $effect){
					$level = $effect->getEffectLevel();
					for($j = 0; $j < $level; ++$j){
						$colors[] = $effect->getColor();
					}
				}
			}
			$particle = new PotionSplashParticle(Color::mix(...$colors));
		}
		$this->getWorld()->addParticle($this->location, $particle, $this->players);
		$this->broadcastSound(new PotionSplashSound(), $this->players);
		if($hasEffects){
			if(!$this->willLinger()){
				$player = $this->getOwningEntity();
				if($player instanceof Player){
					PlayerManager::getSession($player)?->getDuel()?->addProjectileHit($player, "potsHit");
				}
				$entityHit = null;
				if($event instanceof ProjectileHitEntityEvent){
					$entityHit = $event->getEntityHit()->getId();
				}
				foreach($this->getWorld()->getNearbyEntities($this->boundingBox->expandedCopy(1.75, 3, 1.75), $this) as $entity){
					if($entity instanceof Living && $entity->isAlive()){
						if($player instanceof Player && $entity instanceof Player && $entity->getName() !== $player->getName() && ($esession = PlayerManager::getSession($entity)) !== null && $esession->isInCombat() && ($target = $esession->getTarget()) !== null && $target->getName() !== $player->getName()){
							continue;
						}
						$totalMultiplier = ($entityHit === $entity->getId() ? 1.0325 : 0.9025);
						foreach($this->getPotionEffects() as $effect){
							if(!($effect->getType() instanceof InstantEffect)){
								$newDuration = (int) round($effect->getDuration() * 0.75 * $totalMultiplier);
								if($newDuration < 20){
									continue;
								}
								$effect->setDuration($newDuration);
								$entity->getEffects()->add($effect);
							}else{
								$effect->getType()->applyEffect($entity, $effect, $totalMultiplier, $this);
							}
						}
					}
				}
			}else{
				//TODO: lingering potions
			}
		}elseif($event instanceof ProjectileHitBlockEvent && $this->getPotionType()->equals(PotionType::WATER())){
			if(($blockIn = $event->getBlockHit()->getSide($event->getRayTraceResult()->getHitFace()))->getId() === BlockLegacyIds::FIRE){
				$this->getWorld()->setBlock($blockIn->getPosition(), VanillaBlocks::AIR(), false);
			}
			foreach($blockIn->getHorizontalSides() as $horizontalSide){
				if($horizontalSide->getId() === BlockLegacyIds::FIRE){
					$this->getWorld()->setBlock($horizontalSide->getPosition(), VanillaBlocks::AIR(), false);
				}
			}
		}
	}
}