<?php

declare(strict_types=1);

namespace zodiax\game\npc;

use JsonException;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use Webmozart\PathUtil\Path;
use zodiax\game\entity\NPCHuman;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use function file_exists;
use function round;

class NPCManager{

	/** @var NPCHuman[] $npcs */
	private static array $npcs = [];
	private static string $path;
	private static Config $npcConfig;

	/**
	 * @throws JsonException
	 */
	public static function initialize() : void{
		self::$npcConfig = new Config(self::$path = Path::join(PracticeCore::getInstance()->getDataFolder(), "npc.yml"), Config::YAML, []);
		if(!file_exists(self::$path)){
			self::$npcConfig->save();
		}else{
			/** @var string[] $npcs */
			$npcs = self::$npcConfig->getAll(true);
			foreach($npcs as $name){
				/** @var string[] $data */
				$data = self::$npcConfig->get($name);
				if(isset($data["format"], $data["skin"], $data["scale"], $data["animation"], $data["x"], $data["y"], $data["z"], $data["yaw"], $data["headYaw"], $data["pitch"], $data["world"]) && ($world = Server::getInstance()->getWorldManager()->getWorldByName($data["world"])) !== null){
					self::addNPC(new Location((float) $data["x"], (float) $data["y"], (float) $data["z"], $world, (float) $data["yaw"], (float) $data["pitch"]), (float) $data["headYaw"], $name, $data["format"], $data["skin"], (float) $data["scale"], $data["animation"]);
				}
			}
		}
	}

	/**
	 * @throws JsonException
	 */
	public static function addNPC(Location $location, float $headYaw, string $name, string $format, string $skin, float $scale, string $animation) : bool{
		if(file_exists(Path::join(PracticeCore::getResourcesFolder(), "npc", "$skin.png")) && file_exists(Path::join(PracticeCore::getResourcesFolder(), "npc", "$skin.json"))){
			if(!self::$npcConfig->exists($name)){
				self::$npcConfig->set($name, ["format" => $format, "skin" => $skin, "scale" => $scale, "animation" => $animation, "x" => round($location->x, 2), "y" => round($location->y, 2), "z" => round($location->z, 2), "yaw" => round($location->yaw, 2), "headYaw" => round($headYaw, 2), "pitch" => round($location->pitch, 2), "world" => $location->getWorld()->getFolderName()]);
				self::$npcConfig->save();
			}
			$npc = new NPCHuman($location, $headYaw, $name, $format, $skin, $scale, $animation);
			self::$npcs[$id = $npc->getId()] = $npc;
			foreach(PlayerManager::getOnlinePlayers() as $player){
				self::$npcs[$id]->spawnTo($player);
			}
			return true;
		}
		return false;
	}

	/**
	 * @throws JsonException
	 */
	public static function removeNPC(string $name) : bool{
		foreach(self::$npcs as $id => $npc){
			if($npc->getRealName() === $name){
				foreach(PlayerManager::getOnlinePlayers() as $player){
					self::$npcs[$id]->despawnFrom($player);
				}
				unset(self::$npcs[$id]);
				self::$npcConfig->remove($name);
				self::$npcConfig->save();
				return true;
			}
		}
		return false;
	}

	/**
	 * @throws JsonException
	 */
	public static function editNPC(Location $location, float $headYaw, string $name, string $format, string $skin, float $scale, string $animation) : void{
		if(self::$npcConfig->exists($name)){
			self::$npcConfig->set($name, ["format" => $format, "skin" => $skin, "scale" => $scale, "animation" => $animation, "x" => round($location->x, 2), "y" => round($location->y, 2), "z" => round($location->z, 2), "yaw" => round($location->yaw, 2), "headYaw" => round($headYaw, 2), "pitch" => round($location->pitch, 2), "world" => $location->getWorld()->getFolderName()]);
			self::$npcConfig->save();
		}
	}

	/**
	 * @return string[]|NPCHuman[]
	 */
	public static function getNPCs(bool $asString = false) : array{
		if($asString){
			$result = [];
			foreach(self::$npcs as $npc){
				$result[] = $npc->getRealName();
			}
			return $result;
		}else{
			return self::$npcs;
		}
	}

	public static function getNPCfromName(string $name) : ?NPCHuman{
		foreach(self::$npcs as $npc){
			if($npc->getRealName() === $name){
				return $npc;
			}
		}
		return null;
	}

	public static function getNPCfromEntityId(int $id) : ?NPCHuman{
		return self::$npcs[$id] ?? null;
	}

	public static function spawnNPCs(Player $player) : void{
		foreach(self::$npcs as $npc){
			$npc->spawnTo($player);
		}
	}

	public static function despawnNPCs(Player $player) : void{
		foreach(self::$npcs as $npc){
			$npc->despawnFrom($player);
		}
	}
}
