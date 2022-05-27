<?php

declare(strict_types=1);

namespace zodiax\party\misc;

use pocketmine\player\Player;
use zodiax\party\PracticeParty;

class PartyInvite{

	private string $from;
	private string $to;
	private string $party;

	public function __construct(Player $from, Player $to, PracticeParty $party){
		$this->from = $from->getName();
		$this->to = $to->getName();
		$this->party = $party->getName();
	}

	public function getFrom() : string{
		return $this->from;
	}

	public function getTo() : string{
		return $this->to;
	}

	public function getParty() : string{
		return $this->party;
	}
}
