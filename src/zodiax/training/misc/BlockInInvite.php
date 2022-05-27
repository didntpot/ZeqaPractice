<?php

declare(strict_types=1);

namespace zodiax\training\misc;

use pocketmine\player\Player;
use zodiax\training\types\BlockInPractice;

class BlockInInvite{

	private string $from;
	private string $to;
	private int $worldId;

	public function __construct(Player $from, Player $to, BlockInPractice $blockIn){
		$this->from = $from->getName();
		$this->to = $to->getName();
		$this->worldId = $blockIn->getWorldId();
	}

	public function getFrom() : string{
		return $this->from;
	}

	public function getTo() : string{
		return $this->to;
	}

	public function getWorldId() : int{
		return $this->worldId;
	}
}
