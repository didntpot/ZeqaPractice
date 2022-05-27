<?php

declare(strict_types=1);

namespace zodiax\game\entity;

use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\item\Food;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\ItemTranslator;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\ActorEvent;
use pocketmine\network\mcpe\protocol\types\entity\Attribute as NetworkAttribute;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use zodiax\kits\info\KnockbackInfo;
use zodiax\player\PlayerManager;
use function abs;
use function array_map;
use function atan2;
use function fmod;
use function min;

/**
 * TODO:
 *    - Extends our own CustomEntity Class
 */
class CombatBot extends GenericHuman{

	const EASY = 0;
	const MEDIUM = 1;
	const HARD = 2;

	const DUEL = 0;
	const SUMO = 1;
	const COMBO = 2;
	const FIST = 3;
	const GAPPLE = 4;
	const NODEBUFF = 5;
	const SOUP = 6;

	private string $displayName;
	private string $target = "";
	private int $pot = -1;
	private int $gamemode;

	private ?Position $centerPosition;
	private float $arenaRadius;

	private float $distanceToTarget = 0;
	private float $distanceTargetToCenter = 0;

	private float $speed = 0;
	private float $hitRange = 0;
	private float $kiteRange = 0;
	private int $swingCooldownTick = 10;
	private int $jumpCooldownTick = 10;
	private int $refillPerSlotTick = 0;
	private int $chaseTargetDelay = 10;

	private int $gapple = 0; // 64
	private int $hotbarHeal = 0; // 7
	private int $inventoryHeal = 0; // 27
	private int $pearl = 0; // 16

	private int $eatingGappleTick = -1;
	private int $holdingSoupTick = -1;
	private int $throwingPotTick = -1;
	private int $refillingHotbarHealTick = -1;

	private int $currentTick = 0;
	private int $lastAttackedTick = 0;
	private int $lastJumpTick = 0;
	private int $gappleCooldown = 0;
	private int $pearlCooldown = 0;

	public function __construct(Location $location, Skin $skin, ?CompoundTag $nbt = null){
		parent::__construct($location, $skin, $nbt);
		$this->setImmobile();
	}

	public function initialize(int $mode, Position $centerPosition, Vector3 $firstPosition) : void{
		$this->centerPosition = $centerPosition;
		$this->arenaRadius = $this->centerPosition->distance($firstPosition);
		$this->gamemode = match ($this->getKitHolder()->getKit()?->getName()) {
			"Sumo" => self::SUMO,
			"Gapple" => self::GAPPLE,
			"Combo" => self::COMBO,
			"Soup" => self::SOUP,
			"Nodebuff" => self::NODEBUFF,
			default => self::DUEL
		};
		$this->displayName = match ($mode) {
			self::MEDIUM => TextFormat::GOLD . "Medium Bot",
			self::HARD => TextFormat::RED . "Hard Bot",
			default => TextFormat::GREEN . "Easy Bot"
		};
		$this->speed = match ($this->gamemode) {
			self::NODEBUFF => 0.5865,
			default => 0.595
		};
		$this->hitRange = match ($mode) {
			self::MEDIUM => 3.2,
			self::HARD => 3.4,
			default => 3
		};
		$this->kiteRange = match ($mode) {
			self::MEDIUM => 1.1,
			self::HARD => 1.2,
			default => 1
		};
		$this->swingCooldownTick = match ($mode) {
			self::MEDIUM => 6,
			self::HARD => 4,
			default => 8
		};
		$this->refillPerSlotTick = match ($mode) {
			self::MEDIUM => 4,
			self::HARD => 2,
			default => 6
		};
		$this->chaseTargetDelay = match ($mode) {
			self::MEDIUM => 4,
			self::HARD => 2,
			default => 6
		};
		switch($this->gamemode){
			case self::GAPPLE:
			case self::COMBO:
				$this->gapple = 64;
				break;
			case self::SOUP:
				$this->hotbarHeal = 8;
				$this->inventoryHeal = 27;
				break;
			case self::NODEBUFF:
				$this->hotbarHeal = 7;
				$this->inventoryHeal = 27;
				$this->pearl = 16;
				break;
		}
		$this->setNameTag($this->displayName);
		$this->setNameTagAlwaysVisible();
	}

	public function getDisplayName() : string{
		return $this->displayName;
	}

	public function setTarget(?Player $target = null) : void{
		$this->target = $target?->getName() ?? "";
		if($target !== null){
			$this->setImmobile(false);
			$this->setSprinting();
			$this->scheduleUpdate();
		}
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		$hasUpdate = parent::entityBaseTick($tickDiff);
		$target = PlayerManager::getPlayerExact($this->target);
		if(!$this->isAlive() || ($this->target !== "" && $target === null)){
			if(!$this->closed){
				$this->flagForDespawn();
			}
			return false;
		}
		if($target !== null){
			if(++$this->currentTick % 4 === 0){
				$this->updateBotDistance();
				if($this->currentTick % 20 === 0){
					if($this->gappleCooldown > 0){
						$this->gappleCooldown--;
					}
					if($this->pearlCooldown > 0){
						$this->pearlCooldown--;
					}
				}
			}
			$this->doBotBehavior();
		}
		return $hasUpdate;
	}

	private function updateBotDistance() : void{
		/** @var Player $target */
		$target = PlayerManager::getPlayerExact($this->target);
		$this->distanceToTarget = $this->getPosition()->distance($pos = $target->getPosition());
		$this->distanceTargetToCenter = $pos->distance($this->centerPosition); // @phpstan-ignore-line
	}

	private function doSumoBehavior() : void{
		/** @var Position $targetPosition */
		$targetPosition = PlayerManager::getPlayerExact($this->target)?->getPosition();
		$this->lookAt($targetPosition->add(0, 0.7, 0));
		if($this->distanceToTarget < $this->hitRange * 2 && $this->currentTick % $this->swingCooldownTick === 0){
			$this->getInventory()->setHeldItemIndex(0);
			Server::getInstance()->broadcastPackets($this->getWorld()->getPlayers(), [ActorEventPacket::create($this->getId(), ActorEvent::ARM_SWING, 0)]);
		}
		$this->attackTarget();
		if($this->currentTick % 2 == 0){
			return;
		}
		$botPosition = $this->getPosition();
		if($targetPosition->getFloorY() - $botPosition->getFloorY() > 1.5 && $this->currentTick - $this->lastJumpTick > $this->jumpCooldownTick){
			$this->jump();
		}
		if($botPosition->distance($this->centerPosition) > $this->distanceTargetToCenter){ // @phpstan-ignore-line
			$this->chaseTarget($this->centerPosition); // @phpstan-ignore-line
		}elseif($this->distanceTargetToCenter < $this->arenaRadius + 2){
			$this->chaseTarget($targetPosition);
		}
	}

	private function followPot() : void{
		if($this->pot !== -1){
			if(($pot = $this->getWorld()->getEntity($this->pot)) !== null){
				if($pot->isAlive()){
					if($this->currentTick - $this->lastAttackedTick > 4){
						$botPosition = $this->getPosition();
						$potPosition = $pot->getPosition();
						$x = $potPosition->x - $botPosition->getX();
						$z = $potPosition->z - $botPosition->getZ();
						if(abs($x) > 0 || abs($z) > 0){
							$this->motion->x = $this->speed * ($x / (abs($x) + abs($z)));
							$this->motion->z = $this->speed * ($z / (abs($x) + abs($z)));
						}
						$this->setMotion($this->motion);
					}
				}else{
					$this->pot = -1;
					$this->throwingPotTick = 0;
				}
			}
		}
	}

	private function doDuelBehavior() : void{
		if($this->eatingGappleTick >= 0){
			if($this->eatingGappleTick == 0){
				$this->eatGappleAction(true);
			}elseif($this->eatingGappleTick % 4 == 0){
				$this->eatGappleAction(false);
			}
			$this->eatingGappleTick--;
			return;
		}
		if($this->holdingSoupTick >= 0){
			if($this->holdingSoupTick == 0){
				$this->eatSoupAction();
			}
			$this->holdingSoupTick--;
			return;
		}
		if($this->refillingHotbarHealTick >= 0){
			$this->refillingHotbarHealTick--;
			return;
		}
		/** @var Position $targetPosition */
		$targetPosition = PlayerManager::getPlayerExact($this->target)?->getPosition();
		if($this->throwingPotTick >= 0){
			if($this->throwingPotTick === 18){
				$this->setRotation($this->getLocation()->yaw, -45);
				$this->pot = $this->throwPotion()?->getId() ?? 0;
			}elseif($this->throwingPotTick === 10){
				$this->lookAt($targetPosition->add(0, 0.7, 0));
			}
			$this->followPot();
			$this->throwingPotTick--;
			return;
		}
		$this->lookAt($targetPosition->add(0, 0.7, 0));
		if($this->distanceToTarget < $this->hitRange * 2 && $this->currentTick % $this->swingCooldownTick === 0){
			$this->getInventory()->setHeldItemIndex(0);
			Server::getInstance()->broadcastPackets($this->getWorld()->getPlayers(), [ActorEventPacket::create($this->getId(), ActorEvent::ARM_SWING, 0)]);
		}
		$this->attackTarget();
		if($this->currentTick % 2 == 0){
			return;
		}
		$botPosition = $this->getPosition();
		$chase = true;
		if($this->gapple > 0 && (($this->distanceToTarget > 12 && $this->getHealth() <= 20) || $this->getHealth() <= 8)){
			$chase = !$this->eatGapple();
		}
		if($this->hotbarHeal > 0 && ($this->getHealth() <= 8)){
			$chase = !$this->useHotbarHeal();
		}elseif($this->hotbarHeal <= 0 && $this->inventoryHeal > 0){
			$chase = !$this->refillHotbarHeal();
		}
		if($this->pearl > 0){
			if($this->distanceToTarget > 18){
				$chase = !$this->throwPearl(true, -25);
			}elseif($this->distanceToTarget > 14){
				$chase = !$this->throwPearl(true, -15);
			}elseif($this->distanceToTarget > 10){
				$chase = !$this->throwPearl(true, -8);
			}
		}

		if($chase){
			if($this->distanceToTarget > 14 || $targetPosition->getFloorY() > $botPosition->getFloorY() && $this->currentTick - $this->lastJumpTick > $this->jumpCooldownTick){
				$this->jump();
			}
			$this->chaseTarget($targetPosition);
		}
	}

	private function doBotBehavior() : void{
		if($this->gamemode === self::SUMO){
			$this->doSumoBehavior();
		}else{
			$this->doDuelBehavior();
		}
	}

	private function chaseTarget(Vector3 $pos) : void{
		if($this->onGround && $this->currentTick - $this->lastAttackedTick >= $this->chaseTargetDelay){
			$x = $pos->x - $this->getPosition()->getX() - $this->kiteRange;
			$z = $pos->z - $this->getPosition()->getZ() - $this->kiteRange;
			if(abs($x) > 0 || abs($z) > 0){
				$this->motion->x = $this->speed * ($x / (abs($x) + abs($z)));
				$this->motion->z = $this->speed * ($z / (abs($x) + abs($z)));
			}
			$this->setMotion($this->motion);
		}
	}

	public function turnBack() : void{
		/** @var Position $lastLocation */
		$lastLocation = PlayerManager::getPlayerExact($this->target)?->getLocation();
		$xDist = $lastLocation->x - $this->getPosition()->x;
		$zDist = $lastLocation->z - $this->getPosition()->z;
		$yaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
		if($yaw < 0){
			$yaw += 360.0;
		}
		$this->setRotation(fmod($yaw - 180, 360), $this->onGround ? 15 : -15);
	}

	private function attackTarget() : void{
		$this->getInventory()->setHeldItemIndex(0);
		$this->attackEntity(PlayerManager::getPlayerExact($this->target), $this->hitRange); // @phpstan-ignore-line
	}

	private function eatGapple() : bool{
		if($this->gappleCooldown === 0){
			$this->eatingGappleTick = 28;
			$this->gappleCooldown = 10;
			return true;
		}
		return false;
	}

	private function eatGappleAction(bool $effect) : void{
		$this->getInventory()->setHeldItemIndex(1);
		$item = $this->getInventory()->getItemInHand();
		if($item instanceof Food){
			[$netId, $netData] = ItemTranslator::getInstance()->toNetworkId($item->getId(), $item->getMeta());
			Server::getInstance()->broadcastPackets($this->getWorld()->getPlayers(), [ActorEventPacket::create($this->getId(), ActorEvent::EATING_ITEM, ($netId << 16) | $netData)]);
			if($effect){
				$effects = $item->getAdditionalEffects();
				foreach($effects as $effect){
					$this->getEffects()->add($effect);
				}
				$this->getInventory()->setHeldItemIndex(0);
			}
		}
	}

	private function eatSoupAction() : void{
		$this->setHealth($this->getHealth() + 8);
		$this->getInventory()->setHeldItemIndex(0);
	}

	public function throwPearl(bool $target = true, int $yaw_value = -25) : bool{
		if($this->pearlCooldown === 0){
			if($target){
				$this->lookAt(PlayerManager::getPlayerExact($this->target)->getPosition()->add(0, 0.7, 0)); // @phpstan-ignore-line
			}
			$this->getInventory()->setHeldItemIndex(1);
			$this->setRotation($this->getLocation()->yaw, $yaw_value);
			parent::throwPearl();
			$this->getInventory()->setHeldItemIndex(0);
			$this->pearl--;
			$this->pearlCooldown = 10;
			return true;
		}
		return false;
	}

	private function useHotbarHeal() : bool{
		if($this->gamemode === self::NODEBUFF){
			$this->turnBack();
			$this->getInventory()->setHeldItemIndex(2);
			$this->throwingPotTick = 18;
			$this->hotbarHeal--;
			return true;
		}elseif($this->gamemode === self::SOUP){
			$this->getInventory()->setHeldItemIndex(1);
			$this->holdingSoupTick = 8;
			$this->hotbarHeal--;
			return true;
		}
		return false;
	}

	private function refillHotbarHeal() : bool{
		if($this->pearl > 0){
			$this->turnBack();
			$this->throwPearl(false);
			$maxRefill = 7;
		}else{
			$maxRefill = 8;
		}
		$maxRefill = min($maxRefill, $this->inventoryHeal);
		$this->refillingHotbarHealTick = $this->refillPerSlotTick * $maxRefill;
		$this->hotbarHeal += $maxRefill;
		$this->inventoryHeal -= $maxRefill;
		return true;
	}

	public function jump() : void{
		if($this->onGround){
			$this->lastJumpTick = $this->currentTick;
			parent::jump();
		}
	}

	public function attack(EntityDamageEvent $source) : void{
		if($source->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK || $source->getCause() === EntityDamageEvent::CAUSE_PROJECTILE){
			parent::attack($source);
		}else{
			$source->cancel();
			if($source->getCause() === EntityDamageEvent::CAUSE_VOID){
				$this->kill();
			}
		}
	}

	protected function onDeath() : void{
		$ev = new EntityDeathEvent($this);
		$ev->call();
		$this->startDeathAnimation();
		if(($session = PlayerManager::getSession($target = PlayerManager::getPlayerExact($this->target))) !== null){
			$vec3 = $this->getPosition()->asVector3();
			$settingInfo = $session->getSettingsInfo();
			if($settingInfo?->isBlood()){
				$target->getServer()->broadcastPackets([$target], [LevelEventPacket::create(LevelEvent::PARTICLE_DESTROY, RuntimeBlockMapping::getInstance()->toRuntimeId(VanillaBlocks::REDSTONE()->getFullId(), $target->getNetworkSession()->getProtocolId()), $vec3)]); // @phpstan-ignore-line
			}
			if($settingInfo?->isLightning()){
				$target->getServer()->broadcastPackets([$target], [AddActorPacket::create($id = Entity::nextRuntimeId(), $id, "minecraft:lightning_bolt", $vec3, new Vector3(0, 0, 0), 0, 0, 0, array_map(function(Attribute $attr) : NetworkAttribute{ // @phpstan-ignore-line
					return new NetworkAttribute($attr->getId(), $attr->getMinValue(), $attr->getMaxValue(), $attr->getValue(), $attr->getDefaultValue());
				}, $target->getAttributeMap()->getAll()), [], [])]); // @phpstan-ignore-line
			}
		}
	}

	public function actuallyDoknockBack(Entity $entity, KnockbackInfo $info) : void{
		parent::actuallyDoknockBack($entity, $info);
		$this->lastAttackedTick = $this->currentTick;
	}
}