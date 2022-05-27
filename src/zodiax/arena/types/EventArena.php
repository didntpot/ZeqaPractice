<?php

declare(strict_types=1);

namespace zodiax\arena\types;

use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\world\World;
use zodiax\arena\ArenaManager;
use zodiax\event\EventHandler;
use zodiax\kits\DefaultKit;
use zodiax\kits\KitsManager;
use zodiax\PracticeUtil;

class EventArena extends Arena{

	private string $name;
	private string $kit;
	private int $worldId;
	private Vector3 $p1;
	private Vector3 $p2;
	private Vector3 $spec;

	public function __construct(string $name, DefaultKit $kit, World $world, Vector3 $p1, Vector3 $p2, Vector3 $spec){
		$this->name = $name;
		$this->kit = $kit->getName();
		$this->worldId = $world->getId();
		$this->p1 = $p1;
		$this->p2 = $p2;
		$this->spec = $spec;
		EventHandler::addEvent($this);
	}

	public function getName() : string{
		return $this->name;
	}

	public function getKit() : ?DefaultKit{
		return KitsManager::getKit($this->kit);
	}

	public function setKit(DefaultKit $kit) : void{
		$this->kit = $kit->getName();
		ArenaManager::saveArena($this);
	}

	public function getP1Spawn() : Vector3{
		return $this->p1;
	}

	public function getP2Spawn() : Vector3{
		return $this->p2;
	}

	public function getSpecSpawn() : Vector3{
		return $this->spec;
	}

	public function setP1Spawn(Vector3 $spawn) : void{
		$this->p1 = $spawn;
		ArenaManager::saveArena($this);
	}

	public function setP2Spawn(Vector3 $spawn) : void{
		$this->p2 = $spawn;
		ArenaManager::saveArena($this);
	}

	public function setSpecSpawn(Vector3 $spawn) : void{
		$this->spec = $spawn;
		ArenaManager::saveArena($this);
	}

	public function getWorld() : ?World{
		return Server::getInstance()->getWorldManager()->getWorld($this->worldId);
	}

	public function destroyCycles() : void{
		EventHandler::getEventFromArena($this->name)?->setEnded();
		EventHandler::removeEvent($this);
	}

	public function export() : array{
		return ["kit" => $this->getKit()?->getName() ?? "", "world" => $this->getWorld()?->getFolderName() ?? "", "p1" => PracticeUtil::posToArray($this->p1), "p2" => PracticeUtil::posToArray($this->p2), "spec" => PracticeUtil::posToArray($this->spec), "type" => ArenaManager::EVENT];
	}
}
