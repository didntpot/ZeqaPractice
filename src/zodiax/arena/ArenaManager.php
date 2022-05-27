<?php

declare(strict_types=1);

namespace zodiax\arena;

use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\world\World;
use Webmozart\PathUtil\Path;
use zodiax\arena\types\Arena;
use zodiax\arena\types\BlockInArena;
use zodiax\arena\types\BotArena;
use zodiax\arena\types\DuelArena;
use zodiax\arena\types\EventArena;
use zodiax\arena\types\FFAArena;
use zodiax\arena\types\TrainingArena;
use zodiax\kits\DefaultKit;
use zodiax\kits\KitsManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function array_merge;
use function array_rand;
use function file_exists;
use function is_dir;
use function mkdir;
use function str_contains;

class ArenaManager{

	const MAPS_MODE = self::ADVANCE;

	const NORMAL = 0;
	const HYBRID = 1;
	const ADVANCE = 2;

	const FFA = "FFA";
	const DUEL = "DUEL";
	const BOT = "BOT";
	const EVENT = "EVENT";
	const TRAINING = "TRAINING";
	const BLOCK_IN = "BLOCKIN";

	private static int $preWorld = 0;
	private static array $ffaArenas = [];
	private static array $duelArenas = [];
	private static array $eventArenas = [];
	private static array $trainingArenas = [];
	private static array $blockInArenas = [];
	private static Config $arenaConfig;

	public static function initialize() : void{
		@mkdir($arenaPath = Path::join(PracticeCore::getDataFolderPath(), "arenas"));
		self::$arenaConfig = new Config($configPath = Path::join($arenaPath, "arenas.yml"), Config::YAML, []);
		if(!file_exists($configPath)){
			self::$arenaConfig->save();
			return;
		}
		foreach(PracticeUtil::getWorldsFromFolder() as $world){
			if(str_contains($world, "duel")){
				PracticeUtil::removeDirectory(Path::join(Server::getInstance()->getDataPath(), "worlds", $world));
			}
		}
		foreach(self::$arenaConfig->getAll(true) as $name){
			$arena = self::load($name, self::$arenaConfig->get($name));
			if($arena instanceof FFAArena){
				self::$ffaArenas[$name] = $arena;
			}elseif($arena instanceof DuelArena){
				self::$duelArenas[$name] = $arena;
			}elseif($arena instanceof EventArena){
				self::$eventArenas[$name] = $arena;
			}elseif($arena instanceof TrainingArena){
				self::$trainingArenas[$name] = $arena;
			}elseif($arena instanceof BlockInArena){
				self::$blockInArenas[$name] = $arena;
			}
		}
		$defaultWorld = Server::getInstance()->getWorldManager()->getDefaultWorld();
		$defaultWorld->setTime(0);
		$defaultWorld->stopTime();
	}

	public static function nextPreWorldId() : int{
		return self::$preWorld++;
	}

	private static function load(string $name, array $data) : ?Arena{
		if(isset($data["type"])){
			switch($data["type"]){
				case self::FFA:
					if(isset($data["kit"], $data["spawns"], $data["world"], $data["interrupt"])){
						if(($kit = KitsManager::getKit($data["kit"])) !== null){
							$worldManager = Server::getInstance()->getWorldManager();
							if(!$worldManager->isWorldLoaded($data["world"])){
								$worldManager->loadWorld($data["world"]);
								if(($world = $worldManager->getWorldByName($data["world"])) !== null){
									if($world->getDisplayName() === "OITC"){
										$world->setTime(13000);
									}else{
										$world->setTime(0);
									}
									$world->stopTime();
								}
							}
							if(($world = $worldManager->getWorldByName($data["world"])) !== null){
								$spawns = [];
								foreach($data["spawns"] as $key => $spawn){
									$spawns[$key] = new Vector3($spawn["x"], $spawn["y"], $spawn["z"]);
								}
								return new FFAArena($name, $kit, $world, $spawns, (bool) $data["interrupt"]);
							}
						}
					}
					break;
				case self::DUEL:
				case self::BOT:
				case self::TRAINING:
					if(isset($data["kits"], $data["p1"], $data["p2"], $data["world"])){
						if(self::MAPS_MODE === self::ADVANCE || is_dir(Path::join(PracticeCore::getDataFolderPath(), "arenas", $data["world"]))){
							$kits = [];
							foreach($data["kits"] as $kit){
								if(($kit = KitsManager::getKit($kit)) !== null){
									$kits[$kit->getName()] = true;
								}
							}
							if(!empty($kits)){
								$world = $data["world"];
								if(self::MAPS_MODE === self::ADVANCE){
									$worldManager = Server::getInstance()->getWorldManager();
									if(!$worldManager->isWorldLoaded($data["world"])){
										$worldManager->loadWorld($data["world"]);
										if(($world = $worldManager->getWorldByName($data["world"])) !== null){
											$world->setTime(0);
											$world->stopTime();
										}
									}
									if(($world = Server::getInstance()->getWorldManager()->getWorldByName($data["world"])) !== null){
										$world = $world->getFolderName();
									}
								}
								if($world !== null){
									switch($data["type"]){
										case self::DUEL:
											if(isset($data["protections"], $data["maxheight"])){
												$i = 1;
												$protections = [];
												foreach($data["protections"] as $protection){
													if(isset($protection["pos1"], $protection["pos2"])){
														$protections[$i++] = ["pos1" => new Vector3($protection["pos1"]["x"], $protection["pos1"]["y"], $protection["pos1"]["z"]), "pos2" => new Vector3($protection["pos2"]["x"], $protection["pos2"]["y"], $protection["pos2"]["z"])];
													}
												}
												return new DuelArena($name, $kits, $world, new Vector3($data["p1"]["x"], $data["p1"]["y"], $data["p1"]["z"]), new Vector3($data["p2"]["x"], $data["p2"]["y"], $data["p2"]["z"]), $protections, $data["maxheight"]);
											}
											break;
										case self::BOT:
											if(isset($data["protections"], $data["maxheight"])){
												$i = 1;
												$protections = [];
												foreach($data["protections"] as $protection){
													if(isset($protection["pos1"], $protection["pos2"])){
														$protections[$i++] = ["pos1" => new Vector3($protection["pos1"]["x"], $protection["pos1"]["y"], $protection["pos1"]["z"]), "pos2" => new Vector3($protection["pos2"]["x"], $protection["pos2"]["y"], $protection["pos2"]["z"])];
													}
												}
												return new BotArena($name, $kits, $world, new Vector3($data["p1"]["x"], $data["p1"]["y"], $data["p1"]["z"]), new Vector3($data["p2"]["x"], $data["p2"]["y"], $data["p2"]["z"]), $protections, $data["maxheight"]);
											}
											break;
										case self::TRAINING:
											return new TrainingArena($name, $kits, $world, new Vector3($data["p1"]["x"], $data["p1"]["y"], $data["p1"]["z"]), new Vector3($data["p2"]["x"], $data["p2"]["y"], $data["p2"]["z"]));
									}
								}
							}
						}
					}
					break;
				case self::EVENT:
					if(isset($data["kit"], $data["p1"], $data["p2"], $data["spec"], $data["world"])){
						if(($kit = KitsManager::getKit($data["kit"])) !== null){
							$worldManager = Server::getInstance()->getWorldManager();
							if(!$worldManager->isWorldLoaded($data["world"])){
								$worldManager->loadWorld($data["world"]);
								if(($world = $worldManager->getWorldByName($data["world"])) !== null){
									$world->setTime(0);
									$world->stopTime();
								}
							}
							if(($world = $worldManager->getWorldByName($data["world"])) !== null){
								return new EventArena($name, $kit, $world, new Vector3($data["p1"]["x"], $data["p1"]["y"], $data["p1"]["z"]), new Vector3($data["p2"]["x"], $data["p2"]["y"], $data["p2"]["z"]), new Vector3($data["spec"]["x"], $data["spec"]["y"], $data["spec"]["z"]));
							}
						}
					}
					break;
				case self::BLOCK_IN:
					if(isset($data["attackerKit"], $data["defenderKit"], $data["p1"], $data["p2"], $data["core"], $data["world"])){
						if(self::MAPS_MODE === self::ADVANCE || is_dir(Path::join(PracticeCore::getDataFolderPath(), "arenas", $data["world"]))){
							$attackerKit = KitsManager::getKit($data["attackerKit"]);
							$defenderKit = KitsManager::getKit($data["defenderKit"]);
							$world = $data["world"];
							if(self::MAPS_MODE === self::ADVANCE){
								$worldManager = Server::getInstance()->getWorldManager();
								if(!$worldManager->isWorldLoaded($data["world"])){
									$worldManager->loadWorld($data["world"]);
									if(($world = $worldManager->getWorldByName($data["world"])) !== null){
										$world->setTime(0);
										$world->stopTime();
									}
								}
								if(($world = Server::getInstance()->getWorldManager()->getWorldByName($data["world"])) !== null){
									$world = $world->getFolderName();
								}
							}
							if($world !== null){
								return new BlockInArena($name, $attackerKit, $defenderKit, $world, new Vector3($data["p1"]["x"], $data["p1"]["y"], $data["p1"]["z"]), new Vector3($data["p2"]["x"], $data["p2"]["y"], $data["p2"]["z"]), new Vector3($data["core"]["x"], $data["core"]["y"], $data["core"]["z"]));
							}
						}
					}
					break;
			}
		}
		return null;
	}

	public static function createArena(string $name, DefaultKit $kit, World $world, string $type) : bool{
		if(!self::$arenaConfig->exists($name) && !isset(self::$ffaArenas[$name], self::$duelArenas[$name], self::$eventArenas[$name])){
			if($type === self::FFA){
				self::$arenaConfig->set($name, (self::$ffaArenas[$name] = new FFAArena($name, $kit, $world, [1 => $world->getSafeSpawn()]))->export());
				self::$arenaConfig->save();
				return true;
			}elseif($type === self::DUEL || $type === self::BOT || $type === self::TRAINING){
				$worldName = $world->getFolderName();
				$spawn = $world->getSafeSpawn();
				if(self::MAPS_MODE === self::ADVANCE){
					switch($type){
						case self::DUEL:
							self::$arenaConfig->set($name, (self::$duelArenas[$name] = new DuelArena($name, [$kit->getName() => true], $worldName, $spawn, $spawn, [], 255))->export());
							self::$arenaConfig->save();
							return true;
						case self::BOT:
							self::$arenaConfig->set($name, (self::$duelArenas[$name] = new BotArena($name, [$kit->getName() => true], $worldName, $spawn, $spawn, [], 255))->export());
							self::$arenaConfig->save();
							return true;
						case self::TRAINING:
							self::$arenaConfig->set($name, (self::$trainingArenas[$name] = new TrainingArena($name, [$kit->getName() => true], $worldName, $spawn, $spawn))->export());
							self::$arenaConfig->save();
							return true;
					}
				}else{
					if(!is_dir($to = Path::join(PracticeCore::getDataFolderPath(), "arenas", $worldName))){
						$worldManager = Server::getInstance()->getWorldManager();
						$worldManager->unloadWorld($world);
						PracticeUtil::copyDirectory(Path::join(Server::getInstance()->getDataPath(), "worlds" . $worldName), $to);
						$worldManager->loadWorld($worldName);
						switch($type){
							case self::DUEL:
								self::$arenaConfig->set($name, (self::$duelArenas[$name] = new DuelArena($name, [$kit->getName() => true], $worldName, $spawn, $spawn, [], 255))->export());
								self::$arenaConfig->save();
								return true;
							case self::BOT:
								self::$arenaConfig->set($name, (self::$duelArenas[$name] = new BotArena($name, [$kit->getName() => true], $worldName, $spawn, $spawn, [], 255))->export());
								self::$arenaConfig->save();
								return true;
							case self::TRAINING:
								self::$arenaConfig->set($name, (self::$trainingArenas[$name] = new TrainingArena($name, [$kit->getName() => true], $worldName, $spawn, $spawn))->export());
								self::$arenaConfig->save();
								return true;
						}
					}
				}
			}elseif($type === self::EVENT){
				$spawn = $world->getSafeSpawn();
				self::$arenaConfig->set($name, (self::$eventArenas[$name] = new EventArena($name, $kit, $world, $spawn, $spawn, $spawn))->export());
				self::$arenaConfig->save();
				return true;
			}elseif($type === self::BLOCK_IN){
				$worldName = $world->getFolderName();
				$spawn = $world->getSafeSpawn();
				self::$arenaConfig->set($name, (self::$blockInArenas[$name] = new BlockInArena($name, $kit, $kit, $worldName, $spawn, $spawn, $spawn))->export());
				self::$arenaConfig->save();
				return true;
			}
		}
		return false;
	}

	public static function deleteArena(string $name) : bool{
		if(self::$arenaConfig->exists($name)){
			if(($arena = self::getArena($name)) !== null){
				$arena->destroyCycles();
				if($arena instanceof FFAArena){
					unset(self::$ffaArenas[$name]);
				}elseif($arena instanceof DuelArena){
					unset(self::$duelArenas[$name]);
				}elseif($arena instanceof EventArena){
					unset(self::$eventArenas[$name]);
				}elseif($arena instanceof TrainingArena){
					unset(self::$trainingArenas[$name]);
				}elseif($arena instanceof BlockInArena){
					unset(self::$blockInArenas[$name]);
				}
				self::$arenaConfig->remove($name);
				self::$arenaConfig->save();
				return true;
			}
		}
		return false;
	}

	public static function saveArena(Arena $arena) : bool{
		if($arena instanceof FFAArena){
			if(isset(self::$ffaArenas[$name = $arena->getName()])){
				self::$ffaArenas[$name] = $arena;
				self::$arenaConfig->set($name, $arena->export());
				self::$arenaConfig->save();
				return true;
			}
		}elseif($arena instanceof DuelArena){
			if(isset(self::$duelArenas[$name = $arena->getName()])){
				self::$duelArenas[$name] = $arena;
				self::$arenaConfig->set($name, $arena->export());
				self::$arenaConfig->save();
				return true;
			}
		}elseif($arena instanceof EventArena){
			if(isset(self::$eventArenas[$name = $arena->getName()])){
				self::$eventArenas[$name] = $arena;
				self::$arenaConfig->set($name, $arena->export());
				self::$arenaConfig->save();
				return true;
			}
		}elseif($arena instanceof TrainingArena){
			if(isset(self::$trainingArenas[$name = $arena->getName()])){
				self::$trainingArenas[$name] = $arena;
				self::$arenaConfig->set($name, $arena->export());
				self::$arenaConfig->save();
				return true;
			}
		}elseif($arena instanceof BlockInArena){
			if(isset(self::$blockInArenas[$name = $arena->getName()])){
				self::$blockInArenas[$name] = $arena;
				self::$arenaConfig->set($name, $arena->export());
				self::$arenaConfig->save();
				return true;
			}
		}
		return false;
	}

	public static function getArena(string $name) : ?Arena{
		return self::$ffaArenas[$name] ?? self::$duelArenas[$name] ?? self::$eventArenas[$name] ?? self::$trainingArenas[$name] ?? self::$blockInArenas[$name] ?? null;
	}

	public static function getArenas(bool $asString = false) : array{
		$arenas = array_merge(self::$ffaArenas, self::$duelArenas, self::$eventArenas, self::$trainingArenas, self::$blockInArenas);
		if($asString){
			$result = [];
			foreach($arenas as $arena){
				$result[] = $arena->getName();
			}
			return $result;
		}
		return $arenas;
	}

	public static function getFFAArenas(bool $available = false) : array{
		if($available){
			$result = [];
			foreach(self::$ffaArenas as $arena){
				/** @var FFAArena $arena */
				if($arena->getKit()?->getMiscKitInfo()->isFFAEnabled()){
					$result[] = $arena;
				}
			}
			return $result;
		}
		return self::$ffaArenas;
	}

	public static function getEventArenas(bool $available = false) : array{
		if($available){
			$result = [];
			foreach(self::$eventArenas as $arena){
				/** @var EventArena $arena */
				if($arena->getKit()?->getMiscKitInfo()->isEventEnabled()){
					$result[] = $arena;
				}
			}
			return $result;
		}
		return self::$eventArenas;
	}

	public static function getDuelArenas(bool $available = false) : array{
		if($available){
			$result = [];
			foreach(self::$duelArenas as $arena){
				/** @var DuelArena $arena */
				foreach($arena->getKits() as $kit){
					$miscInfo = KitsManager::getKit($kit)?->getMiscKitInfo();
					if($miscInfo?->isDuelsEnabled() || $miscInfo?->isBotEnabled()){
						$result[] = $arena;
					}
				}
			}
			return $result;
		}
		return self::$duelArenas;
	}

	public static function getTrainingArenas(bool $available = false) : array{
		if($available){
			$result = [];
			foreach(self::$trainingArenas as $arena){
				/** @var TrainingArena $arena */
				foreach($arena->getKits() as $kit){
					if(KitsManager::getKit($kit)?->getMiscKitInfo()?->isTrainingEnabled()){
						$result[] = $arena;
					}
				}
			}
			return $result;
		}
		return self::$trainingArenas;
	}

	public static function getBlockInArenas(bool $available = false) : array{
		if($available){
			$result = [];
			foreach(self::$blockInArenas as $arena){
				/** @var BlockInArena $arena */
				if($arena->getAttackerKit()?->getMiscKitInfo()->isTrainingEnabled()){
					$result[] = $arena;
				}
			}
			return $result;
		}
		return self::$blockInArenas;
	}

	public static function findDuelArena(string $kit) : ?DuelArena{
		$result = [];
		foreach(self::$duelArenas as $arena){
			/** @var DuelArena $arena */
			if(self::MAPS_MODE === self::ADVANCE && ($arena->getWorld(true) === null || !$arena->isAvailable())){
				continue;
			}
			if(isset($arena->getKits()[$kit])){
				$result[] = $arena;
			}
		}
		return empty($result) ? null : $result[array_rand($result)];
	}

	public static function findBotArena(string $kit) : ?BotArena{
		$result = [];
		foreach(self::$duelArenas as $arena){
			if(self::MAPS_MODE === self::ADVANCE && ($arena->getWorld(true) === null || !$arena->isAvailable())){
				continue;
			}
			if($arena instanceof BotArena){
				if(isset($arena->getKits()[$kit])){
					$result[] = $arena;
				}
			}
		}
		return empty($result) ? null : $result[array_rand($result)];
	}

	public static function findTrainingArena(string $kit) : ?TrainingArena{
		$result = [];
		foreach(self::$trainingArenas as $arena){
			if(self::MAPS_MODE === self::ADVANCE && ($arena->getWorld(true) === null || !$arena->isAvailable())){
				continue;
			}
			if(isset($arena->getKits()[$kit])){
				$result[] = $arena;
			}
		}
		return empty($result) ? null : $result[array_rand($result)];
	}

	public static function findBlockInArena(string $kit) : ?BlockInArena{
		$result = [];
		foreach(self::$blockInArenas as $arena){
			if(self::MAPS_MODE === self::ADVANCE && ($arena->getWorld(true) === null || !$arena->isAvailable())){
				continue;
			}
			if($arena->getAttackerKit()?->getName() === $kit){
				$result[] = $arena;
			}
		}
		return empty($result) ? null : $result[array_rand($result)];
	}
}
