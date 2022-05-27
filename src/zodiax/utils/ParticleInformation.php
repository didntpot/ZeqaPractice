<?php

declare(strict_types=1);

namespace zodiax\utils;

use pocketmine\color\Color;
use pocketmine\network\mcpe\protocol\types\ParticleIds;
use pocketmine\world\particle\AngryVillagerParticle;
use pocketmine\world\particle\CriticalParticle;
use pocketmine\world\particle\EnchantmentTableParticle;
use pocketmine\world\particle\EnchantParticle;
use pocketmine\world\particle\ExplodeParticle;
use pocketmine\world\particle\FlameParticle;
use pocketmine\world\particle\HappyVillagerParticle;
use pocketmine\world\particle\HeartParticle;
use pocketmine\world\particle\LavaParticle;
use pocketmine\world\particle\Particle;
use pocketmine\world\particle\RedstoneParticle;
use pocketmine\world\particle\SmokeParticle;
use pocketmine\world\particle\SplashParticle;
use pocketmine\world\particle\WaterParticle;

class ParticleInformation{

	private static array $information = [];
	private static bool $initialize = false;
	private string $name;
	private int $particleID;

	private function __construct(int $particleID, string $name){
		$this->particleID = $particleID;
		$this->name = $name;
	}

	public static function getAll() : array{
		if(!self::$initialize){
			self::initialize();
		}
		return self::$information;
	}

	private static function initialize() : void{
		self::register(new ParticleInformation(ParticleIds::EXPLODE, "Explode"));
		self::register(new ParticleInformation(ParticleIds::SPLASH, "Splash"));
		self::register(new ParticleInformation(ParticleIds::WATER_SPLASH, "Water"));
		self::register(new ParticleInformation(ParticleIds::CRITICAL, "Critical"));
		self::register(new ParticleInformation(ParticleIds::SMOKE, "Smoke"));
		self::register(new ParticleInformation(ParticleIds::MOB_SPELL, "Spell"));
		self::register(new ParticleInformation(ParticleIds::FLAME, "Flame"));
		self::register(new ParticleInformation(ParticleIds::LAVA, "Lava"));
		self::register(new ParticleInformation(ParticleIds::REDSTONE, "Redstone Dust"));
		self::register(new ParticleInformation(ParticleIds::HEART, "Heart"));
		self::register(new ParticleInformation(ParticleIds::ENCHANTMENT_TABLE, "Enchantment Table"));
		self::register(new ParticleInformation(ParticleIds::VILLAGER_HAPPY, "Happy Villager"));
		self::register(new ParticleInformation(ParticleIds::VILLAGER_ANGRY, "Angry Villager"));
		self::$initialize = true;
	}

	private static function register(ParticleInformation $information) : void{
		self::$information[$information->getID()] = $information;
	}

	public function getParticle() : ?Particle{
		return match ($this->particleID) {
			ParticleIds::EXPLODE => new ExplodeParticle(),
			ParticleIds::SPLASH => new SplashParticle(),
			ParticleIds::WATER_SPLASH => new WaterParticle(),
			ParticleIds::CRITICAL => new CriticalParticle(),
			ParticleIds::SMOKE => new SmokeParticle($data ?? 0),
			ParticleIds::MOB_SPELL => new EnchantParticle(new Color(255, 255, 255)),
			ParticleIds::FLAME => new FlameParticle(),
			ParticleIds::LAVA => new LavaParticle(),
			ParticleIds::REDSTONE => new RedstoneParticle(1),
			ParticleIds::HEART => new HeartParticle($data ?? 0),
			ParticleIds::ENCHANTMENT_TABLE => new EnchantmentTableParticle(),
			ParticleIds::VILLAGER_HAPPY => new HappyVillagerParticle(),
			ParticleIds::VILLAGER_ANGRY => new AngryVillagerParticle(),
			default => null,
		};
	}

	public static function getInformation(int $particle) : ?ParticleInformation{
		if(!self::$initialize){
			self::initialize();
		}
		if(isset(self::$information[$particle])){
			return self::$information[$particle];
		}
		return null;
	}

	public function getID() : int{
		return $this->particleID;
	}

	public function getName() : string{
		return $this->name;
	}
}