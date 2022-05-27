<?php

declare(strict_types=1);

namespace zodiax\party\duel\misc;

use pocketmine\player\Player;
use zodiax\party\PracticeParty;
use function count;

class PracticeTeam{

	private array $players;
	private bool $eliminated;
	private string $teamColor;

	public function __construct(string $color){
		$this->players = [];
		$this->eliminated = false;
		$this->teamColor = $color;
	}

	public function addToTeam(Player $player) : void{
		$this->players[$name = $player->getName()] = $name;
	}

	public function addPartyToTeam(PracticeParty $party) : void{
		foreach($party->getPlayers() as $player){
			$this->players[$player] = $player;
		}
	}

	public function isEliminated() : bool{
		return $this->eliminated;
	}

	public function setEliminated() : void{
		$this->players = [];
		$this->eliminated = true;
	}

	public function getTeamColor() : string{
		return $this->teamColor;
	}

	public function getPlayers() : array{
		return $this->players;
	}

	public function isInTeam(string|Player $player) : bool{
		return isset($this->players[$player instanceof Player ? $player->getName() : $player]);
	}

	public function removeFromTeam(string|Player $player, bool $eliminate = true) : void{
		if(isset($this->players[$name = ($player instanceof Player ? $player->getName() : $player)])){
			unset($this->players[$name]);
			if($eliminate && count($this->players) === 0){
				$this->setEliminated();
			}
		}
	}
}
