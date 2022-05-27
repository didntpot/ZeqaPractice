<?php

declare(strict_types=1);

namespace zodiax\proxy;

use pocketmine\network\mcpe\protocol\DebugInfoPacket;
use zodiax\misc\AbstractRepeatingTask;
use zodiax\player\PlayerManager;
use zodiax\PracticeUtil;

class ProxyTask extends AbstractRepeatingTask{

	public function __construct(){
		parent::__construct(PracticeUtil::secondsToTicks(5));
	}

	protected function onUpdate(int $tickDifference) : void{
		$packet = DebugInfoPacket::create(0, "waterdog:ping");
		foreach(PlayerManager::getOnlinePlayers() as $player){
			$player->getNetworkSession()->sendDataPacket($packet);
		}
	}
}
