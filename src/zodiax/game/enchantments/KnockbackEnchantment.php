<?php

declare(strict_types=1);

namespace zodiax\game\enchantments;

use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\item\enchantment\KnockbackEnchantment as PMKnockbackEnchantment;
use pocketmine\player\Player;
use pocketmine\Server;
use zodiax\game\GameplayListener;
use zodiax\kits\info\KnockbackInfo;
use zodiax\player\PlayerManager;
use zodiax\PracticeUtil;
use function in_array;
use function mt_getrandmax;
use function mt_rand;
use function sqrt;

class KnockbackEnchantment extends PMKnockbackEnchantment{

	public function onPostAttack(Entity $attacker, Entity $victim, int $enchantmentLevel) : void{
		if($victim instanceof Player && ($vsession = PlayerManager::getSession($victim)) !== null){
			if(($attackedKit = $vsession->getKitHolder()?->getKit()) !== null){
				$this->applyKnockback($victim, $attacker, $attackedKit->getKnockbackInfo(), $enchantmentLevel + 1);
				return;
			}
		}
		parent::onPostAttack($attacker, $victim, $enchantmentLevel);
	}

	private function applyKnockback(Entity $entity, Entity $damager, KnockbackInfo $info, int $enchantmentLevel) : void{
		$xzKb = $info->getHorizontalKb() * $enchantmentLevel;
		$yKb = $info->getVerticalKb();
		if(($maxHeight = $info->getMaxHeight()) > 0){
			[$max, $min] = PracticeUtil::maxMin($entity->getPosition()->getY(), $damager->getPosition()->getY());
			if($max - $min >= $maxHeight){
				$yKb *= 0.75;
				if($info->canRevert()){
					$yKb *= -1;
				}
			}
		}
		if($entity instanceof Player && ($session = PlayerManager::getSession($entity)) !== null && in_array($session->getKitHolder()?->getKit()?->getName(), ["MLGRush", "Reduce"], true)){
			if(Server::getInstance()->getTick() - GameplayListener::$cachedData[$entity->getName()]["lastAttackedActorTime"] <= 12){
				$xzKb = $info->getHorizontalKb() * 0.35;
			}else{
				$xzKb *= 2;
				$yKb *= 1.25;
				$session->getReduce()?->addExtraHitDelay();
			}
		}
		$x = $entity->getPosition()->getX() - $damager->getPosition()->getX();
		$z = $entity->getPosition()->getZ() - $damager->getPosition()->getZ();
		$f = sqrt($x * $x + $z * $z);
		if($f <= 0){
			return;
		}
		if(mt_rand() / mt_getrandmax() > $entity->getAttributeMap()->get(Attribute::KNOCKBACK_RESISTANCE)?->getValue()){
			$f = 1 / $f;
			$motion = clone $entity->getMotion();
			$motion->x /= 2;
			$motion->y /= 2;
			$motion->z /= 2;
			$motion->x += $x * $f * $xzKb;
			$motion->y += $yKb;
			$motion->z += $z * $f * $xzKb;
			if($motion->y > $yKb){
				$motion->y = $yKb;
			}
			$entity->setMotion($motion);
		}
	}
}
