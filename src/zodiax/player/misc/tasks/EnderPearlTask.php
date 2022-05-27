<?php

declare(strict_types=1);

namespace zodiax\player\misc\tasks;

use zodiax\misc\AbstractRepeatingTask;
use zodiax\player\PracticePlayer;
use zodiax\PracticeUtil;
use function ceil;

class EnderPearlTask extends AbstractRepeatingTask{

	private PracticePlayer $session;
	private int $cooldown;

	public function __construct(PracticePlayer $session){
		parent::__construct();
		$this->session = $session;
		$this->cooldown = PracticeUtil::secondsToTicks(10);
	}

	protected function onUpdate(int $tickDifference) : void{
		if($this->session->canPearl() || ($player = $this->session->getPlayer()) === null || !$player->isOnline()){
			$this->getHandler()?->cancel();
			return;
		}
		$tick = $this->cooldown - $this->getCurrentTick();
		if($this->session->getPlayer()->isOnline()){
			$this->session->getExtensions()->setXpAndProgress((int) ceil($tick / 20), (float) ($tick / $this->cooldown));
		}
		if($this->getCurrentTick() === $this->cooldown){
			$this->session->setThrowPearl();
			$this->getHandler()?->cancel();
		}
	}
}