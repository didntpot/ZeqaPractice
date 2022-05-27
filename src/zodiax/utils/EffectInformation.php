<?php

declare(strict_types=1);

namespace zodiax\utils;

use pocketmine\data\bedrock\EffectIdMap;
use pocketmine\data\bedrock\EffectIds;
use pocketmine\entity\effect\Effect;
use pocketmine\entity\effect\EffectInstance;
use function is_int;

class EffectInformation{

	private static array $information = [];
	private static bool $initialize = false;
	private string $texture;
	private string $name;
	private int $effectID;

	private function __construct(int $effectID, string $name, string $texture){
		$this->effectID = $effectID;
		$this->name = $name;
		$this->texture = $texture;
	}

	public static function getAll() : array{
		if(!self::$initialize){
			self::initialize();
		}
		return self::$information;
	}

	private static function initialize() : void{
		self::register(new EffectInformation(EffectIds::SPEED, "Speed", "textures/ui/speed_effect.png"));
		self::register(new EffectInformation(EffectIds::SLOWNESS, "Slowness", "textures/ui/slowness_effect.png"));
		self::register(new EffectInformation(EffectIds::HASTE, "Haste", "textures/ui/haste_effect.png"));
		self::register(new EffectInformation(EffectIds::MINING_FATIGUE, "Mining Fatigue", "textures/ui/mining_fatigue_effect.png"));
		self::register(new EffectInformation(EffectIds::STRENGTH, "Strength", "textures/ui/strength_effect.png"));
		self::register(new EffectInformation(EffectIds::INSTANT_HEALTH, "Instant Health", "textures/items/potion_bottle_splash_heal.png"));
		self::register(new EffectInformation(EffectIds::INSTANT_DAMAGE, "Instant Damage", "textures/items/potion_bottle_splash_harm.png"));
		self::register(new EffectInformation(EffectIds::JUMP_BOOST, "Jump Boost", "textures/ui/jump_boost_effect.png"));
		self::register(new EffectInformation(EffectIds::NAUSEA, "Nausea", "textures/ui/nausea_effect.png"));
		self::register(new EffectInformation(EffectIds::REGENERATION, "Regeneration", "textures/ui/regeneration_effect.png"));
		self::register(new EffectInformation(EffectIds::RESISTANCE, "Resistance", "textures/ui/resistance_effect.png"));
		self::register(new EffectInformation(EffectIds::FIRE_RESISTANCE, "Fire Resistance", "textures/ui/fire_resistance_effect.png"));
		self::register(new EffectInformation(EffectIds::WATER_BREATHING, "Water Breathing", "textures/ui/water_breathing_effect.png"));
		self::register(new EffectInformation(EffectIds::INVISIBILITY, "Invisibility", "textures/ui/invisibility_effect.png"));
		self::register(new EffectInformation(EffectIds::BLINDNESS, "Blindness", "textures/ui/blindness_effect.png"));
		self::register(new EffectInformation(EffectIds::NIGHT_VISION, "Night Vision", "textures/ui/night_vision_effect.png"));
		self::register(new EffectInformation(EffectIds::HUNGER, "Hunger", "textures/ui/hunger_effect_full.png"));
		self::register(new EffectInformation(EffectIds::WEAKNESS, "Weakness", "textures/ui/weakness_effect.png"));
		self::register(new EffectInformation(EffectIds::POISON, "Poison", "textures/ui/poison_effect.png"));
		self::register(new EffectInformation(EffectIds::WITHER, "Wither", "textures/ui/wither_effect.png"));
		self::register(new EffectInformation(EffectIds::HEALTH_BOOST, "Health Boost", "textures/ui/health_boost_effect.png"));
		self::register(new EffectInformation(EffectIds::ABSORPTION, "Absorption", "textures/ui/absorption_effect.png"));
		self::register(new EffectInformation(EffectIds::SATURATION, "Saturation", "textures/ui/hunger_effect.png"));
		self::register(new EffectInformation(EffectIds::LEVITATION, "Levitation", "textures/ui/levitation_effect.png"));
		self::register(new EffectInformation(EffectIds::FATAL_POISON, "Fatal Poison", "textures/ui/poison_effect.png"));
		self::register(new EffectInformation(EffectIds::CONDUIT_POWER, "Conduit Power", "textures/ui/conduit_power_effect.png"));
		self::$initialize = true;
	}

	private static function register(EffectInformation $information) : void{
		self::$information[$information->getEffect()->getName()->getText()] = $information;
	}

	public function getEffect() : ?Effect{
		return EffectIdMap::getInstance()->fromId($this->effectID);
	}

	public static function getInformation(EffectInstance|Effect|int $effect) : ?EffectInformation{
		if(!self::$initialize){
			self::initialize();
		}
		if($effect instanceof EffectInstance){
			return self::getInformation($effect->getType());
		}
		if($effect instanceof Effect && isset(self::$information[$effect->getName()->getText()])){
			return self::$information[$effect->getName()->getText()];
		}
		if(is_int($effect) && isset(self::$information[$effect])){
			return self::$information[$effect];
		}
		return null;
	}

	public function getName() : string{
		return $this->name;
	}

	public function createInstance() : ?EffectInstance{
		$effect = $this->getEffect();
		if($effect === null){
			return null;
		}
		return new EffectInstance($effect);
	}

	public function getFormTexture() : string{
		return $this->texture;
	}
}