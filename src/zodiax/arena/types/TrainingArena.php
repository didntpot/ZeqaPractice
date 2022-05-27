<?php

declare(strict_types=1);

namespace zodiax\arena\types;

use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\world\World;
use Webmozart\PathUtil\Path;
use zodiax\arena\ArenaManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\training\TrainingHandler;
use zodiax\training\types\ClutchPractice;
use zodiax\training\types\ReducePractice;
use function array_keys;
use function mkdir;

class TrainingArena extends Arena{

	private string $name;
	protected array $kits;
	private int $worldId;
	protected string $world;
	protected array $preWorlds;
	protected Vector3 $p1;
	protected Vector3 $p2;
	private bool $available;

	public function __construct(string $name, array $kits, string $world, Vector3 $p1, Vector3 $p2){
		$this->name = $name;
		$this->kits = $kits;
		$this->worldId = Server::getInstance()->getWorldManager()->getWorldByName($world)?->getId() ?? -1;
		$this->world = $world;
		$this->preWorlds = [];
		$this->p1 = $p1;
		$this->p2 = $p2;
		if(ArenaManager::MAPS_MODE !== ArenaManager::ADVANCE){
			$worldPath = Path::join(PracticeCore::getDataFolderPath(), "arenas", $this->getWorld());
			$dataPath = Path::join(Server::getInstance()->getDataPath(), "worlds");
			$worldManager = Server::getInstance()->getWorldManager();
			for($i = 0; $i < 10; $i++){
				$worldId = ArenaManager::nextPreWorldId();
				if(mkdir($to = Path::join($dataPath, "duel$worldId")) === true){
					PracticeUtil::copyDirectory($worldPath, $to);
					$this->preWorlds[$worldId] = true;
					if(ArenaManager::MAPS_MODE === ArenaManager::HYBRID){
						$worldManager->loadWorld($name = "duel$worldId");
						$world = $worldManager->getWorldByName($name);
						$world->setTime(0);
						$world->stopTime();
					}
				}
			}
		}
		$this->available = true;
	}

	public function getName() : string{
		return $this->name;
	}

	public function getKits() : array{
		return $this->kits;
	}

	public function setKits(array $kits) : void{
		$this->kits = $kits;
		ArenaManager::saveArena($this);
	}

	public function getP1Spawn() : Vector3{
		return $this->p1;
	}

	public function getP2Spawn() : Vector3{
		return $this->p2;
	}

	public function setP1Spawn(Vector3 $spawn) : void{
		$this->p1 = $spawn;
		ArenaManager::saveArena($this);
	}

	public function setP2Spawn(Vector3 $spawn) : void{
		$this->p2 = $spawn;
		ArenaManager::saveArena($this);
	}

	public function getWorld($asWorld = false) : string|null|World{
		return $asWorld ? Server::getInstance()->getWorldManager()->getWorld($this->worldId) : $this->world;
	}

	public function setPreWorldAsAvailable(int $worldId) : void{
		if(isset($this->preWorlds[$worldId])){
			if(ArenaManager::MAPS_MODE === ArenaManager::NORMAL){
				$worldManager = Server::getInstance()->getWorldManager();
				if($worldManager->isWorldLoaded($world = "duel$worldId")){
					$worldManager->unloadWorld($worldManager->getWorldByName($world));
				}
			}
			$this->preWorlds[$worldId] = true;
		}
		$this->available = true;
	}

	public function getPreWorld() : int{
		if(ArenaManager::MAPS_MODE !== ArenaManager::ADVANCE){
			$worldManager = Server::getInstance()->getWorldManager();
			foreach($this->preWorlds as $worldId => $available){
				if(ArenaManager::MAPS_MODE === ArenaManager::NORMAL){
					if(!$worldManager->isWorldLoaded($world = "duel$worldId")){
						$worldManager->loadWorld($world);
						$world = $worldManager->getWorldByName($world);
						$world->setTime(0);
						$world->stopTime();
						$this->preWorlds[$worldId] = false;
						return $worldId;
					}
				}elseif(ArenaManager::MAPS_MODE === ArenaManager::HYBRID){
					if($available && $worldManager->isWorldLoaded($name = "duel$worldId") && ($world = $worldManager->getWorldByName($name)) !== null && !TrainingHandler::isClutchInWorld($world) && !TrainingHandler::isReduceInWorld($world)){
						$this->preWorlds[$worldId] = false;
						return $worldId;
					}
				}
			}
			$worldPath = Path::join(PracticeCore::getDataFolderPath(), "arenas", $this->getWorld());
			$dataPath = Path::join(Server::getInstance()->getDataPath(), "worlds");
			$worldId = ArenaManager::nextPreWorldId();
			if(mkdir($to = Path::join($dataPath, "duel$worldId")) === true){
				PracticeUtil::copyDirectory($worldPath, $to);
				$worldManager->loadWorld($name = "duel$worldId");
				$world = $worldManager->getWorldByName($name);
				$world->setTime(0);
				$world->stopTime();
				$this->preWorlds[$worldId] = false;
				return $worldId;
			}else{
				return $this->getPreWorld();
			}
		}else{
			$this->available = false;
		}
		return ArenaManager::nextPreWorldId();
	}

	public function isAvailable() : bool{
		return $this->available;
	}

	public function destroyCycles() : void{
		if(ArenaManager::MAPS_MODE !== ArenaManager::ADVANCE){
			$worldManager = Server::getInstance()->getWorldManager();
			foreach($this->preWorlds as $worldId => $available){
				if($worldManager->isWorldLoaded($name = "duel$worldId") && ($world = $worldManager->getWorldByName($name)) !== null && ($training = TrainingHandler::getClutchFromWorld($world) ?? TrainingHandler::getReduceFromWorld($world)) !== null){
					$training->setEnded();
				}
			}
			PracticeUtil::removeDirectory(Path::join(PracticeCore::getDataFolderPath(), "arenas", $this->getWorld()));
		}else{
			foreach(TrainingHandler::getClutches() as $clutch){
				/** @var ClutchPractice $duel */
				if($clutch->getArena() === $this->name){
					$clutch->setEnded();
				}
			}
			foreach(TrainingHandler::getReduces() as $reduce){
				/** @var ReducePractice $duel */
				if($reduce->getArena() === $this->name){
					$reduce->setEnded();
				}
			}
		}
	}

	public function export() : array{
		return ["kits" => array_keys($this->kits), "world" => ArenaManager::MAPS_MODE === ArenaManager::ADVANCE ? ($this->getWorld(ArenaManager::MAPS_MODE === ArenaManager::ADVANCE)?->getFolderName() ?? "") : $this->world, "p1" => PracticeUtil::posToArray($this->p1), "p2" => PracticeUtil::posToArray($this->p2), "type" => ArenaManager::TRAINING];
	}
}
