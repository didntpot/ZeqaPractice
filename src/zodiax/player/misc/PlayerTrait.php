<?php

declare(strict_types=1);

namespace zodiax\player\misc;

use pocketmine\player\Player;
use zodiax\player\PlayerManager;
use zodiax\player\PracticePlayer;

trait PlayerTrait{

	private string $player;

	private function getPlayer() : ?Player{
		return PlayerManager::getPlayerExact($this->player);
	}

	private function getSession() : ?PracticePlayer{
		return PlayerManager::getSession($this->getPlayer());
	}
}
