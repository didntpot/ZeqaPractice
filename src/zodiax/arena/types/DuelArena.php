<?php

declare(strict_types=1);

namespace zodiax\arena\types;

use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\world\World;
use Webmozart\PathUtil\Path;
use zodiax\arena\ArenaManager;
use zodiax\duel\BotHandler;
use zodiax\duel\DuelHandler;
use zodiax\duel\ReplayHandler;
use zodiax\duel\types\BotDuel;
use zodiax\duel\types\DuelReplay;
use zodiax\duel\types\PlayerDuel;
use zodiax\party\duel\PartyDuel;
use zodiax\party\duel\PartyDuelHandler;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function array_keys;
use function count;
use function mkdir;

class DuelArena extends Arena{

	private string $name;
	protected array $kits;
	private int $worldId;
	protected string $world;
	protected array $preWorlds;
	protected Vector3 $p1;
	protected Vector3 $p2;
	protected array $protections;
	protected int $maxHeight;
	private bool $available;

	public function __construct(string $name, array $kits, string $world, Vector3 $p1, Vector3 $p2, array $protections, int $maxHeight){
		$this->name = $name;
		$this->kits = $kits;
		$this->worldId = Server::getInstance()->getWorldManager()->getWorldByName($world)?->getId() ?? -1;
		$this->world = $world;
		$this->preWorlds = [];
		$this->p1 = $p1;
		$this->p2 = $p2;
		$this->protections = $protections;
		$this->maxHeight = $maxHeight;
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

	public function getProtections(int $index = 0) : array{
		if($index === 0){
			return $this->protections;
		}
		return [$this->protections[$index] ?? []];
	}

	public function setProtection(Vector3 $pos1, Vector3 $pos2, int $key = 0) : void{
		if(isset($this->protections[$key])){
			$this->protections[$key] = ["pos1" => $pos1, "pos2" => $pos2];
		}elseif($key === 0){
			$this->protections[(count($this->protections) + 1)] = ["pos1" => $pos1, "pos2" => $pos2];
		}
		ArenaManager::saveArena($this);
	}

	public function removeProtection(int $key) : void{
		if(isset($this->protections[$key])){
			unset($this->protections[$key]);
			$protections = $this->protections;
			$this->protections = [];
			$i = 1;
			foreach($protections as $protection){
				$this->protections[$i++] = $protection;
			}
			ArenaManager::saveArena($this);
		}
	}

	public function getMaxHeight() : int{
		return $this->maxHeight;
	}

	public function setMaxHeight(int $maxHeight) : void{
		$this->maxHeight = $maxHeight;
		ArenaManager::saveArena($this);
	}

	public function canBuild(Vector3 $pos) : bool{
		if($pos->getY() >= $this->maxHeight){
			return false;
		}
		foreach($this->protections as $protection){
			if(PracticeUtil::isWithinProtection($pos, $protection["pos1"], $protection["pos2"])){
				return false;
			}
		}
		return true;
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
					if($available && $worldManager->isWorldLoaded($name = "duel$worldId") && ($world = $worldManager->getWorldByName($name)) !== null && !DuelHandler::isDuelWorld($world) && !BotHandler::isDuelWorld($world) && !PartyDuelHandler::isDuelWorld($world) && !ReplayHandler::isReplayWorld($world)){
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
				if($worldManager->isWorldLoaded($name = "duel$worldId") && ($world = $worldManager->getWorldByName($name)) !== null && ($duel = DuelHandler::getDuelFromWorld($world) ?? BotHandler::getDuelFromWorld($world) ?? PartyDuelHandler::getDuelFromWorld($world) ?? ReplayHandler::getReplayFromWorld($world)) !== null){
					$duel->setEnded();
				}
			}
			PracticeUtil::removeDirectory(Path::join(PracticeCore::getDataFolderPath(), "arenas", $this->getWorld()));
		}else{
			foreach(DuelHandler::getDuels() as $duel){
				/** @var PlayerDuel $duel */
				if($duel->getArena() === $this->name){
					$duel->setEnded();
				}
			}
			foreach(BotHandler::getDuels() as $duel){
				/** @var BotDuel $duel */
				if($duel->getArena() === $this->name){
					$duel->setEnded();
				}
			}
			foreach(PartyDuelHandler::getDuels() as $duel){
				/** @var PartyDuel $duel */
				if($duel->getArena() === $this->name){
					$duel->setEnded();
				}
			}
			foreach(ReplayHandler::getReplays() as $replay){
				/** @var DuelReplay $replay */
				if($replay->getArena() === $this->name){
					$replay->setEnded();
				}
			}
		}
	}

	public function export() : array{
		$protections = [];
		foreach($this->protections as $key => $data){
			$protections[$key] = ["pos1" => PracticeUtil::posToArray($data["pos1"]), "pos2" => PracticeUtil::posToArray($data["pos2"])];
		}
		return ["kits" => array_keys($this->kits), "world" => ArenaManager::MAPS_MODE === ArenaManager::ADVANCE ? ($this->getWorld(ArenaManager::MAPS_MODE === ArenaManager::ADVANCE)?->getFolderName() ?? "") : $this->world, "p1" => PracticeUtil::posToArray($this->p1), "p2" => PracticeUtil::posToArray($this->p2), "protections" => $protections, "maxheight" => $this->maxHeight, "type" => ArenaManager::DUEL];
	}
}
