<?php

declare(strict_types=1);

namespace zodiax\tasks;

use zodiax\data\log\LogMonitor;
use zodiax\game\world\BlockRemoverHandler;
use zodiax\misc\AbstractRepeatingTask;
use zodiax\player\misc\cosmetic\CosmeticManager;
use zodiax\player\misc\queue\QueueHandler;
use zodiax\PracticeUtil;

class GarbageCollectorTask extends AbstractRepeatingTask{

	public function __construct(){
		parent::__construct(PracticeUtil::hoursToTicks(1));
	}

	public function onUpdate(int $tickDifference) : void{
		if($this->getCurrentTick() !== 0){
			BlockRemoverHandler::triggerGarbageCollector();
			CosmeticManager::triggerGarbageCollector();
			QueueHandler::triggerGarbageCollector();
			LogMonitor::triggerGarbageCollector();
		}
	}
}
