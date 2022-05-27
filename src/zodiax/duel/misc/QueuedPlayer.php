<?php

declare(strict_types=1);

namespace zodiax\duel\misc;

use pocketmine\player\Player;

class QueuedPlayer/* extends AbstractRepeatingTask*/
{

	private string $player;
	private string $kit;
	private bool $ranked;
	private int $elo;

	public function __construct(Player $player, string $kit, bool $ranked = false){
		/*parent::__construct(PracticeUtil::secondsToTicks(10));*/
		$this->player = $player->getName();
		$this->kit = $kit;
		$this->ranked = $ranked;
		$this->elo = 0;
	}

	public function getPlayer() : string{
		return $this->player;
	}

	public function getKit() : string{
		return $this->kit;
	}

	public function isRanked() : bool{
		return $this->ranked;
	}

	public function getEloRange() : int{
		return $this->elo;
	}

	/*protected function onUpdate(int $tickDifference) : void{
		if(($player = PlayerManager::getPlayerExact($this->getPlayer())) === null){
			DuelHandler::removeFromQueue($this->getPlayer(), false);
		}elseif(DuelHandler::getQueueOf($player) === null){
			$this->getHandler()?->cancel();
		}elseif(!$this->isRanked()){
			$this->elo += 50;
			if(($matched = DuelHandler::findMatch($this)) !== null){
				DuelHandler::removeFromQueue($player, false);
				DuelHandler::removeFromQueue($matched = PlayerManager::getPlayerExact($matched->getPlayer()), false);
				DuelHandler::placeInDuel($player, $matched, $this->getKit(), $this->isRanked());
			}
		}
	}*/
}