<?php

declare(strict_types=1);

namespace zodiax\game\hologram\tasks;

use zodiax\game\hologram\HologramHandler;
use zodiax\misc\AbstractRepeatingTask;
use zodiax\PracticeUtil;

class LoadHologramContentTask extends AbstractRepeatingTask{

	public function __construct(){
		parent::__construct(PracticeUtil::hoursToTicks(1));
	}

	public function onUpdate(int $tickDifference) : void{
		HologramHandler::loadHologramContent();
	}
}
