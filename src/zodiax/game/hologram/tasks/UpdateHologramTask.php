<?php

declare(strict_types=1);

namespace zodiax\game\hologram\tasks;

use zodiax\game\hologram\HologramHandler;
use zodiax\misc\AbstractRepeatingTask;
use zodiax\PracticeUtil;

class UpdateHologramTask extends AbstractRepeatingTask{

	public function __construct(){
		parent::__construct(PracticeUtil::secondsToTicks(10));
	}

	public function onUpdate(int $tickDifference) : void{
		HologramHandler::updateHolograms();
	}
}
