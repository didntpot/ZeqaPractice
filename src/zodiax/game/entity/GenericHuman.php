<?php

declare(strict_types=1);

namespace zodiax\game\entity;

use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\animation\CriticalHitAnimation;
use pocketmine\entity\Attribute;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\entity\object\ItemEntity;
use pocketmine\entity\projectile\Arrow as PMArrow;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\MeleeWeaponEnchantment;
use pocketmine\item\PotionType;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\sound\BowShootSound;
use pocketmine\world\sound\EntityAttackNoDamageSound;
use pocketmine\world\sound\EntityAttackSound;
use pocketmine\world\sound\ThrowSound;
use zodiax\game\behavior\fishing\FishingBehavior;
use zodiax\game\behavior\fishing\IFishingBehaviorEntity;
use zodiax\game\behavior\kits\IKitHolderEntity;
use zodiax\game\behavior\kits\KitHolder;
use zodiax\game\entity\projectile\Arrow;
use zodiax\game\entity\projectile\EnderPearl;
use zodiax\game\entity\projectile\Snowball;
use zodiax\game\entity\projectile\SplashPotion;
use zodiax\game\entity\replay\ReplayArrow;
use zodiax\game\entity\replay\ReplayHuman;
use zodiax\game\entity\replay\ReplayPearl;
use zodiax\game\entity\replay\ReplayPotion;
use zodiax\game\entity\replay\ReplaySnowball;
use zodiax\kits\info\KnockbackInfo;
use function assert;
use function mt_getrandmax;
use function mt_rand;
use function sqrt;

/**
 * TODO:
 *    - Extends our own CustomEntity Class
 */
class GenericHuman extends Human implements IKitHolderEntity, IFishingBehaviorEntity{

	private FishingBehavior $fishingBehavior;
	private KitHolder $kitHolder;

	public function getFishingEntity() : Human{
		return $this;
	}

	public function getFishingBehavior() : FishingBehavior{
		return $this->fishingBehavior = $this->fishingBehavior ?? new FishingBehavior($this);
	}

	public function getKitHolderEntity() : Human{
		return $this;
	}

	public function getKitHolder() : KitHolder{
		return $this->kitHolder = $this->kitHolder ?? new KitHolder($this);
	}

	public function throwPearl() : bool{
		$location = $this->getLocation();
		$pearl = $this instanceof ReplayHuman ? new ReplayPearl(Location::fromObject($this->getEyePos(), $location->getWorld(), $location->getYaw(), $location->getPitch()), $this) : new EnderPearl(Location::fromObject($this->getEyePos(), $location->getWorld(), $location->getYaw(), $location->getPitch()), $this);
		($ev = new ProjectileLaunchEvent($pearl))->call();
		if($ev->isCancelled()){
			$pearl->flagForDespawn();
			return false;
		}
		$pearl->spawnToAll();
		$location->getWorld()->addSound($location, new ThrowSound());
		return true;
	}

	public function throwSnowball() : void{
		$location = $this->getLocation();
		$snowball = $this instanceof ReplayHuman ? new ReplaySnowball(Location::fromObject($this->getEyePos(), $location->getWorld(), $location->getYaw(), $location->getPitch()), $this) : new Snowball(Location::fromObject($this->getEyePos(), $location->getWorld(), $location->getYaw(), $location->getPitch()), $this);
		($ev = new ProjectileLaunchEvent($snowball))->call();
		if($ev->isCancelled()){
			$snowball->flagForDespawn();
			return;
		}
		$snowball->spawnToAll();
		$location->getWorld()->addSound($location, new ThrowSound());
	}

	public function throwPotion() : ?SplashPotion{
		$location = $this->getLocation();
		$pot = $this instanceof ReplayHuman ? new ReplayPotion(Location::fromObject($this->getEyePos(), $location->getWorld(), $location->getYaw(), $location->getPitch()), $this, PotionType::STRONG_HEALING()) : new SplashPotion(Location::fromObject($this->getEyePos(), $location->getWorld(), $location->getYaw(), $location->getPitch()), $this, PotionType::STRONG_HEALING());
		($ev = new ProjectileLaunchEvent($pot))->call();
		if($ev->isCancelled()){
			$pot->flagForDespawn();
			return null;
		}
		$pot->spawnToAll();
		$location->getWorld()->addSound($location, new ThrowSound());
		return $pot;
	}

	public function useRod(bool $fishing) : void{
		$fishingBehavior = $this->getFishingBehavior();
		if(!$fishing){
			$fishingBehavior->stopFishing();
		}else{
			$fishingBehavior->startFishing();
		}
	}

	public function useBow(float $force) : void{
		$location = $this->getLocation();
		$arrow = $this instanceof ReplayHuman ? new ReplayArrow(Location::fromObject($this->getEyePos(), $location->getWorld(), ($location->yaw > 180 ? 360 : 0) - $location->yaw, -$location->pitch), $this, ($force / 3) >= 1) : new Arrow(Location::fromObject($this->getEyePos(), $location->getWorld(), ($location->yaw > 180 ? 360 : 0) - $location->yaw, -$location->pitch), $this, ($force / 3) >= 1);
		$arrow->setMotion($arrow->getMotion()->multiply($force));
		($ev = new ProjectileLaunchEvent($arrow))->call();
		if($ev->isCancelled()){
			$arrow->flagForDespawn();
			return;
		}
		$arrow->spawnToAll();
		$location->getWorld()->addSound($location, new BowShootSound());
	}

	public function attackEntity(Entity $entity, float $mexReach = PHP_INT_MAX) : bool{
		if(!$entity->isAlive()){
			return false;
		}
		if($entity instanceof ItemEntity || $entity instanceof PMArrow){
			return false;
		}

		$heldItem = $this->inventory->getItemInHand();

		$ev = new EntityDamageByEntityEvent($this, $entity, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $heldItem->getAttackPoints());
		if($mexReach !== PHP_INT_MAX && !$this->canInteract($entity->getLocation(), $mexReach)){
			$ev->cancel();
		}elseif(($entity instanceof Player && !$this->server->getConfigGroup()->getConfigBool("pvp"))){
			$ev->cancel();
		}

		$meleeEnchantmentDamage = 0;
		/** @var EnchantmentInstance[] $meleeEnchantments */
		$meleeEnchantments = [];
		foreach($heldItem->getEnchantments() as $enchantment){
			$type = $enchantment->getType();
			if($type instanceof MeleeWeaponEnchantment && $type->isApplicableTo($entity)){
				$meleeEnchantmentDamage += $type->getDamageBonus($enchantment->getLevel());
				$meleeEnchantments[] = $enchantment;
			}
		}
		$ev->setModifier($meleeEnchantmentDamage, EntityDamageEvent::MODIFIER_WEAPON_ENCHANTMENTS);

		if(!$this->isSprinting() && $this->fallDistance > 0 && !$this->effectManager->has(VanillaEffects::BLINDNESS()) && !$this->isUnderwater()){
			$ev->setModifier($ev->getFinalDamage() / 2, EntityDamageEvent::MODIFIER_CRITICAL);
		}

		$entity->attack($ev);
		$this->broadcastAnimation(new ArmSwingAnimation($this), $this->getViewers());

		$soundPos = $entity->getPosition()->add(0, $entity->size->getHeight() / 2, 0);
		if($ev->isCancelled()){
			$this->getWorld()->addSound($soundPos, new EntityAttackNoDamageSound());
			return false;
		}
		$this->getWorld()->addSound($soundPos, new EntityAttackSound());

		if($ev->getModifier(EntityDamageEvent::MODIFIER_CRITICAL) > 0 && $entity instanceof Living){
			$entity->broadcastAnimation(new CriticalHitAnimation($entity));
		}

		foreach($meleeEnchantments as $enchantment){
			$type = $enchantment->getType();
			assert($type instanceof MeleeWeaponEnchantment);
			$type->onPostAttack($this, $entity, $enchantment->getLevel());
		}

		return true;
	}

	public function actuallyDoknockBack(Entity $entity, KnockbackInfo $info) : void{
		$xzKb = $info->getHorizontalKb();
		$yKb = $info->getVerticalKb();
		$x = $this->getPosition()->getX() - $entity->getPosition()->getX();
		$z = $this->getPosition()->getZ() - $entity->getPosition()->getZ();
		$f = sqrt($x * $x + $z * $z);
		if($f <= 0){
			return;
		}
		if(mt_rand() / mt_getrandmax() > $this->getAttributeMap()->get(Attribute::KNOCKBACK_RESISTANCE)?->getValue()){
			$f = 1 / $f;
			$motion = clone $this->getMotion();
			$motion->x /= 2;
			$motion->y /= 2;
			$motion->z /= 2;
			$motion->x += $x * $f * $xzKb;
			$motion->y += $yKb;
			$motion->z += $z * $f * $xzKb;
			$modifiedY = $yKb * 1.222;
			if($motion->y > $yKb){
				$motion->y = $modifiedY;
			}else{
				$motion->y *= 1.222;
				if(!$this->onGround){
					$motion->x *= 0.6;
					$motion->z *= 0.6;
					$motion->y *= 1.8;
					if(!$entity->onGround){
						$motion->y *= 1.05;
					}
				}
				if($motion->y > $modifiedY){
					$motion->y = $modifiedY;
				}
			}
			$this->setMotion($motion);
		}
	}

	public function knockBack(float $x, float $z, float $force = 0.4, ?float $verticalLimit = 0.4) : void{
	}

	public function canInteract(Vector3 $pos, float $maxDistance, float $maxDiff = M_SQRT3 / 2) : bool{
		$eyePos = $this->getEyePos();
		if($eyePos->distanceSquared($pos) > $maxDistance ** 2){
			return false;
		}

		$dV = $this->getDirectionVector();
		$eyeDot = $dV->dot($eyePos);
		$targetDot = $dV->dot($pos);
		return ($targetDot - $eyeDot) >= -$maxDiff;
	}
}