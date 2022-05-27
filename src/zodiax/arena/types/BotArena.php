<?php

declare(strict_types=1);

namespace zodiax\arena\types;

use zodiax\arena\ArenaManager;
use zodiax\PracticeUtil;
use function array_keys;

class BotArena extends DuelArena{

	public function export() : array{
		$protections = [];
		foreach($this->protections as $key => $data){
			$protections[$key] = ["pos1" => PracticeUtil::posToArray($data["pos1"]), "pos2" => PracticeUtil::posToArray($data["pos2"])];
		}
		return ["kits" => array_keys($this->kits), "world" => ArenaManager::MAPS_MODE === ArenaManager::ADVANCE ? ($this->getWorld(ArenaManager::MAPS_MODE === ArenaManager::ADVANCE)?->getFolderName() ?? "") : $this->world, "p1" => PracticeUtil::posToArray($this->p1), "p2" => PracticeUtil::posToArray($this->p2), "protections" => $protections, "maxheight" => $this->maxHeight, "type" => ArenaManager::BOT];
	}
}
