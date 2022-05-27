<?php

declare(strict_types=1);

namespace zodiax\party\duel\misc;

use zodiax\party\PracticeParty;

class QueuedParty{

	private string $party;
	private string $kit;
	private int $size;

	public function __construct(PracticeParty $party, string $kit){
		$this->party = $party->getName();
		$this->kit = $kit;
		$this->size = $party->getPlayers(true);
	}

	public function getParty() : string{
		return $this->party;
	}

	public function getKit() : string{
		return $this->kit;
	}

	public function getSize() : int{
		return $this->size;
	}
}