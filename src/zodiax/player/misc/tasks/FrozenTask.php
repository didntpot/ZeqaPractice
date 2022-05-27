<?php

declare(strict_types=1);

namespace zodiax\player\misc\tasks;

use pocketmine\utils\TextFormat;
use zodiax\misc\AbstractRepeatingTask;
use zodiax\player\PracticePlayer;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class FrozenTask extends AbstractRepeatingTask{

	private PracticePlayer $session;

	public function __construct(PracticePlayer $session){
		parent::__construct(PracticeUtil::secondsToTicks(1));
		$this->session = $session;
	}

	protected function onUpdate(int $tickDifference) : void{
		if(($player = $this->session->getPlayer()) === null || !$player->isOnline() || !$this->session->isFrozen()){
			$this->getHandler()?->cancel();
			return;
		}
		$player->sendTitle(PracticeCore::COLOR . "You are " . TextFormat::RED . "Frozen", PracticeCore::COLOR . "Logging out will result as a " . TextFormat::RED . "ban", -1, PracticeUtil::hoursToTicks(1), -1);
	}
}