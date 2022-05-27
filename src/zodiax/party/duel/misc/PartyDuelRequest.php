<?php

declare(strict_types=1);

namespace zodiax\party\duel\misc;

use zodiax\party\PracticeParty;

class PartyDuelRequest{

	private string $from;
	private string $to;
	private string $kit;

	public function __construct(PracticeParty $from, PracticeParty $to, string $kit){
		$this->from = $from->getName();
		$this->to = $to->getName();
		$this->kit = $kit;
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
}
