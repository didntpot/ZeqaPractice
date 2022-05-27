<?php

declare(strict_types=1);

namespace zodiax\utils;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\world\particle\Particle;

class CustomParticle implements Particle{

	private int $actorUniqueId;
	private string $particle;

	public function __construct(int $actorUniqueId, string $particle){
		$this->actorUniqueId = $actorUniqueId;
		$this->particle = $particle;
	}

	public function encode(Vector3 $pos) : array{
		return [SpawnParticleEffectPacket::create(DimensionIds::OVERWORLD, $this->actorUniqueId, $pos, $this->particle, "")];
	}
}