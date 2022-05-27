<?php

declare(strict_types=1);

namespace zodiax\player\misc\tasks;

use pocketmine\utils\TextFormat;
use zodiax\misc\AbstractRepeatingTask;
use zodiax\player\PracticePlayer;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function ceil;

class CombatTask extends AbstractRepeatingTask{

	private PracticePlayer $session;
	private int $cooldown;

	public function __construct(PracticePlayer $session){
		parent::__construct();
		$this->session = $session;
		$this->cooldown = PracticeUtil::secondsToTicks(10);
	}

	protected function onUpdate(int $tickDifference) : void{
		if(($player = $this->session->getPlayer()) === null || !$player->isOnline()){
			$this->getHandler()?->cancel();
			return;
		}
		$this->cooldown--;
		$this->session->getClicksInfo()->update();
		if($this->cooldown % 20 === 0){
			$this->session->getScoreboardInfo()->updateLineOfScoreboard(2, PracticeCore::COLOR . " Combat: " . TextFormat::WHITE . (int) ceil($this->cooldown / 20) . "s");
		}
		if($this->cooldown === 0){
			$this->session->setInCombat();
			$this->getHandler()?->cancel();
		}
	}

	public function resetTimer() : void{
		$this->cooldown = PracticeUtil::secondsToTicks(10);
	}
}