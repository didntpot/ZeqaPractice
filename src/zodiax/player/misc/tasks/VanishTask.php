<?php

declare(strict_types=1);

namespace zodiax\player\misc\tasks;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\misc\AbstractRepeatingTask;
use zodiax\player\misc\VanishHandler;
use zodiax\PracticeUtil;

class VanishTask extends AbstractRepeatingTask{

	private Player $player;

	public function __construct(Player $player){
		parent::__construct(PracticeUtil::secondsToTicks(1));
		$this->player = $player;
	}

	protected function onUpdate(int $tickDifference) : void{
		if(!$this->player->isOnline() || !VanishHandler::isVanishStaff($this->player)){
			$this->getHandler()?->cancel();
			return;
		}
		$this->player->sendPopup(TextFormat::GREEN . "You are in vanish");
	}
}