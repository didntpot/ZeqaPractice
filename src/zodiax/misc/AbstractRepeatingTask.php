<?php

declare(strict_types=1);

namespace zodiax\misc;

use pocketmine\scheduler\Task;
use zodiax\PracticeCore;

abstract class AbstractRepeatingTask extends Task{

	private int $currentTick = 0;
	private int $currentTickPeriod;

	public function __construct(int $periodTicks = 1){
		PracticeCore::getInstance()->getScheduler()->scheduleRepeatingTask($this, $periodTicks);
		$this->currentTickPeriod = $periodTicks;
	}

	public function getCurrentTick() : int{
		return $this->currentTick;
	}

	public function onRun() : void{
		$this->onUpdate($this->currentTickPeriod);
		$this->currentTick += $this->currentTickPeriod;
	}

	abstract protected function onUpdate(int $tickDifference) : void;
}