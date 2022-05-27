<?php

declare(strict_types=1);

namespace zodiax\game\entity\replay;

use zodiax\game\entity\projectile\Arrow;

class ReplayArrow extends Arrow implements IReplayEntity{

	private bool $paused = false;

	public function onUpdate(int $currentTick) : bool{
		if($this->closed || $this->paused){
			return false;
		}
		return parent::onUpdate($currentTick);
	}

	public function setPaused(bool $paused) : void{
		$this->paused = $paused;
	}

	public function isPaused() : bool{
		return $this->paused;
	}
}
