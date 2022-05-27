<?php

declare(strict_types=1);

namespace zodiax\game\entity;

use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\entity\object\ItemEntity;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\MeleeWeaponEnchantment;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\world\sound\EntityAttackNoDamageSound;
use pocketmine\world\sound\EntityAttackSound;
use zodiax\kits\info\KnockbackInfo;
use zodiax\player\misc\cosmetic\CosmeticManager;
use function assert;

class DummyBot extends GenericHuman{

	public function __construct(Location $location, Skin $skin, ?CompoundTag $nbt = null){
		parent::__construct($location, CosmeticManager::getServerDefaultSkin($skin), $nbt);
	}

	public function attackEntity(Entity $entity, float $mexReach = PHP_INT_MAX) : bool{
		if(!$entity->isAlive()){
			return false;
		}
		if($entity instanceof ItemEntity || $entity instanceof Arrow){
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

		$entity->attack($ev);
		$this->broadcastAnimation(new ArmSwingAnimation($this), $this->getViewers());

		$soundPos = $entity->getPosition()->add(0, $entity->size->getHeight() / 2, 0);
		if($ev->isCancelled()){
			$this->getWorld()->addSound($soundPos, new EntityAttackNoDamageSound());
			return false;
		}
		$this->getWorld()->addSound($soundPos, new EntityAttackSound());

		foreach($meleeEnchantments as $enchantment){
			$type = $enchantment->getType();
			assert($type instanceof MeleeWeaponEnchantment);
			$type->onPostAttack($this, $entity, $enchantment->getLevel());
		}

		return true;
	}

	public function actuallyDoknockBack(Entity $entity, KnockbackInfo $info) : void{
	}
}
