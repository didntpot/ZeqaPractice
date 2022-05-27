<?php

declare(strict_types=1);

namespace zodiax\game\entity\replay;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use zodiax\game\entity\GenericHuman;
use zodiax\PracticeUtil;

/**
 * TODO:
 *    - Extends our own CustomEntity Class
 */
class ReplayHuman extends GenericHuman implements IReplayEntity{

	protected $gravityEnabled = false;
	private ?Vector3 $targetPosition = null;
	private ?Vector3 $lastPosition = null;
	private bool $paused = false;
	/** @var string[] */
	private array $potcolor;

	public function onUpdate(int $currentTick) : bool{
		if(!$this->isAlive() || $this->paused){
			return false;
		}
		if($this->lastPosition !== null && $this->targetPosition !== null && $this->lastPosition->distance($this->targetPosition) > 5){
			PracticeUtil::teleport($this, $this->targetPosition->asVector3());
		}
		if($this->targetPosition !== null){
			$this->lastPosition = $this->targetPosition;
			$x = ($this->targetPosition->x - $this->getPosition()->getX());
			$z = ($this->targetPosition->z - $this->getPosition()->getZ());
			$y = ($this->targetPosition->y - $this->getPosition()->getY());
			$this->move($x, $y, $z);
		}
		return parent::onUpdate($currentTick);
	}

	public function setTargetPosition(Vector3 $target) : void{
		$this->targetPosition = $target;
	}

	public function setPaused(bool $paused) : void{
		$this->paused = $paused;
	}

	public function isPaused() : bool{
		return $this->paused;
	}

	/**
	 * @param string[] $potcolor
	 */
	public function setPotColor(array $potcolor) : void{
		$this->potcolor = $potcolor;
	}

	/**
	 * @return string[]
	 */
	public function getPotColor() : array{
		return $this->potcolor;
	}

	public function attack(EntityDamageEvent $source) : void{
		$source->cancel();
	}
}
