<?php

declare(strict_types=1);

namespace zodiax\misc;

use pocketmine\scheduler\Task;
use zodiax\PracticeCore;

abstract class AbstractDelayedTask extends Task{

	public function __construct(int $ticksDelay){
		PracticeCore::getInstance()->getScheduler()->scheduleDelayedTask($this, $ticksDelay);
	}

	public function onRun() : void{
		$this->onUpdate(0);
	}

	abstract protected function onUpdate(int $tickDifference) : void;
}