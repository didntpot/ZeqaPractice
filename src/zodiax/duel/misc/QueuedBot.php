<?php

declare(strict_types=1);

namespace zodiax\duel\misc;

use pocketmine\player\Player;

class QueuedBot{

	private string $player;
	private string $kit;
	private int $mode;

	public function __construct(Player $player, string $kit, int $mode){
		$this->player = $player->getName();
		$this->kit = $kit;
		$this->mode = $mode;
	}

	public function getPlayer() : string{
		return $this->player;
	}

	public function getKit() : string{
		return $this->kit;
	}

	public function getMode() : int{
		return $this->mode;
	}
}