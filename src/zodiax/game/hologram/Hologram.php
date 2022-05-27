<?php

declare(strict_types=1);

namespace zodiax\game\hologram;

use pocketmine\math\Vector3;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\world\World;
use function count;

abstract class Hologram{

	protected Vector3 $vec3;
	protected World $world;
	/** @var string[] */
	protected array $hologramKey;
	protected ?FloatingTextParticle $floatingText;
	protected int $currentKey;

	public function __construct(Vector3 $vec3, World $world){
		$this->world = $world;
		$this->vec3 = $vec3;
		$this->hologramKey = [];
		$this->floatingText = null;
		$this->currentKey = 0;
	}

	public function updateHologram() : void{
		$this->placeFloatingHologram();
	}

	abstract protected function placeFloatingHologram(bool $updateKey = true) : void;

	public function moveHologram(Vector3 $position, World $world) : void{
		$originalWorld = $this->world;
		$this->world = $world;
		$this->vec3 = $position;
		if($this->floatingText !== null){
			$this->floatingText->setInvisible();
			$pkts = $this->floatingText->encode($this->vec3);
			if(count($pkts) > 0){
				foreach($pkts as $pkt){
					$originalWorld->broadcastPacketToViewers($this->vec3, $pkt);
				}
			}
		}
		$this->placeFloatingHologram(false);
	}

	public function placedHologram() : bool{
		return $this->floatingText !== null;
	}
}
