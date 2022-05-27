<?php

declare(strict_types=1);

namespace zodiax\tasks;

use DateTime;
use DateTimeZone;
use zodiax\misc\AbstractRepeatingTask;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function substr;

class AutoRestartTask extends AbstractRepeatingTask{

	public function __construct(){
		parent::__construct(PracticeUtil::hoursToTicks(1));
	}

	public function onUpdate(int $tickDifference) : void{
		if($this->getCurrentTick() !== 0){
			$now = match (substr(PracticeCore::getRegionInfo(), 0, -1)) {
				"NA" => new DateTime("NOW", new DateTimeZone("America/New_York")),
				"EU" => new DateTime("NOW", new DateTimeZone("Europe/London")),
				"AS" => new DateTime("NOW", new DateTimeZone("Asia/Singapore")),
				default => new DateTime("NOW"),
			};
			if($now->format("H") === "04"){
				PracticeCore::setRestart(true);
			}
		}
	}
}
