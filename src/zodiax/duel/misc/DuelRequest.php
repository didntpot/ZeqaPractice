<?php

declare(strict_types=1);

namespace zodiax\duel\misc;

use pocketmine\player\Player;

class DuelRequest{

	private string $from;
	private string $to;
	private string $kit;
	private bool $ranked;

	public function __construct(Player $from, Player $to, string $kit, bool $ranked){
		$this->from = $from->getName();
		$this->to = $to->getName();
		$this->kit = $kit;
		$this->ranked = $ranked;
	}

	public function getFrom() : string{
		return $this->from;
	}

	public function getTo() : string{
		return $this->to;
	}

	public function getKit() : string{
		return $this->kit;
	}

	public function isRanked() : bool{
		return $this->ranked;
	}
}
