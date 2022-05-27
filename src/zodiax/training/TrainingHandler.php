<?php

declare(strict_types=1);

namespace zodiax\training;

use pocketmine\entity\Location;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use zodiax\arena\ArenaManager;
use zodiax\game\entity\BlockInEntity;
use zodiax\kits\KitsManager;
use zodiax\misc\AbstractRepeatingTask;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\training\types\BlockInPractice;
use zodiax\training\types\ClutchPractice;
use zodiax\training\types\ReducePractice;
use function count;
use function str_contains;
use function str_replace;

class TrainingHandler extends AbstractRepeatingTask{

	private static array $activeClutches = [];
	private static array $activeReduces = [];
	private static array $activeBlockIns = [];
	private static array $blockInEntities = [];

	public function __construct(){
		parent::__construct();
	}

	///////////////////////////////////////////////////////////////////Clutch///////////////////////////////////////////////////////

	public static function placeInClutch(Player $player) : void{
		if(($kit = KitsManager::getKit("Clutch")) === null || ($arena = ArenaManager::findTrainingArena($kit->getName())) === null){
			$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "No arenas are available for " . TextFormat::WHITE . "Clutch");
			return;
		}
		if(($pSession = PlayerManager::getSession($player)) !== null && $pSession->isInHub()){
			$pSession->getExtensions()->clearAll();
			$pSession->setInHub(false);
			self::$activeClutches[$worldId = $arena->getPreWorld()] = new ClutchPractice($worldId, $player, $kit, $arena);
		}
	}

	public static function getClutches(bool $asInt = false) : int|array{
		return $asInt ? count(self::$activeClutches) : self::$activeClutches;
	}

	public static function getClutch($player) : ?ClutchPractice{
		foreach(self::$activeClutches as $clutch){
			if($clutch->isPlayer($player)){
				return $clutch;
			}
		}
		return null;
	}

	public static function getClutchFromSpec($player) : ?ClutchPractice{
		foreach(self::$activeClutches as $clutch){
			if($clutch->isSpectator($player)){
				return $clutch;
			}
		}
		return null;
	}

	public static function removeClutch(int $key) : void{
		if(isset(self::$activeClutches[$key])){
			self::$activeClutches[$key]->destroyCycles();
			self::$activeClutches[$key] = null;
			unset(self::$activeClutches[$key]);
		}
	}

	public static function isClutchInWorld(?World $world) : bool{
		return self::getClutchFromWorld($world) !== null;
	}

	public static function getClutchFromWorld(?World $world) : ?ClutchPractice{
		if($world === null){
			return null;
		}
		if(str_contains($name = $world->getFolderName(), "duel")){
			return self::$activeClutches[(int) str_replace("duel", "", $name)] ?? null;
		}
		return null;
	}

	///////////////////////////////////////////////////////////////////Reduce///////////////////////////////////////////////////////

	public static function placeInReduce(Player $player) : void{
		if(($kit = KitsManager::getKit("Reduce")) === null || ($arena = ArenaManager::findTrainingArena($kit->getName())) === null){
			$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "No arenas are available for " . TextFormat::WHITE . "Reduce");
			return;
		}
		if(($pSession = PlayerManager::getSession($player)) !== null && $pSession->isInHub()){
			$pSession->getExtensions()->clearAll();
			$pSession->setInHub(false);
			self::$activeReduces[$worldId = $arena->getPreWorld()] = new ReducePractice($worldId, $player, $kit, $arena);
		}
	}

	public static function getReduces(bool $asInt = false) : int|array{
		return $asInt ? count(self::$activeReduces) : self::$activeReduces;
	}

	public static function getReduce($player) : ?ReducePractice{
		foreach(self::$activeReduces as $reduce){
			if($reduce->isPlayer($player)){
				return $reduce;
			}
		}
		return null;
	}

	public static function getReduceFromSpec($player) : ?ReducePractice{
		foreach(self::$activeReduces as $reduce){
			if($reduce->isSpectator($player)){
				return $reduce;
			}
		}
		return null;
	}

	public static function removeReduce(int $key) : void{
		if(isset(self::$activeReduces[$key])){
			self::$activeReduces[$key]->destroyCycles();
			self::$activeReduces[$key] = null;
			unset(self::$activeReduces[$key]);
		}
	}

	public static function isReduceInWorld(?World $world) : bool{
		return self::getReduceFromWorld($world) !== null;
	}

	public static function getReduceFromWorld(?World $world) : ?ReducePractice{
		if($world === null){
			return null;
		}
		if(str_contains($name = $world->getFolderName(), "duel")){
			return self::$activeReduces[(int) str_replace("duel", "", $name)] ?? null;
		}
		return null;
	}

	///////////////////////////////////////////////////////////////////Block-In/////////////////////////////////////////////////////

	public static function placeInBlockIn(Player $player) : void{
		if(($kit = KitsManager::getKit("Attacker")) === null || ($arena = ArenaManager::findBlockInArena($kit->getName())) === null){
			$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "No arenas are available for " . TextFormat::WHITE . "Block-In");
			return;
		}
		if(($pSession = PlayerManager::getSession($player)) !== null && $pSession->isInHub()){
			$pSession->getExtensions()->clearAll();
			$pSession->setInHub(false);
			self::$activeBlockIns[$worldId = $arena->getPreWorld()] = new BlockInPractice($worldId, $player, $kit, $arena);
			self::$activeBlockIns[$worldId]->addPlayer($player);
		}
	}

	public static function getBlockInById(int $id) : ?BlockInPractice{
		return self::$activeBlockIns[$id] ?? null;
	}

	public static function getBlockIns(bool $asInt = false) : int|array{
		return $asInt ? count(self::$activeBlockIns) : self::$activeBlockIns;
	}

	public static function getAvailableBlockIns() : array{
		$result = [];
		foreach(self::$activeBlockIns as $blockIn){
			if($blockIn->isAvailable()){
				$result[] = $blockIn;
			}
		}
		return $result;
	}

	public static function getBlockIn($player) : ?BlockInPractice{
		foreach(self::$activeBlockIns as $blockIn){
			if($blockIn->isPlayer($player)){
				return $blockIn;
			}
		}
		return null;
	}

	public static function getBlockInFromSpec($player) : ?BlockInPractice{
		foreach(self::$activeBlockIns as $blockIn){
			if($blockIn->isSpectator($player)){
				return $blockIn;
			}
		}
		return null;
	}

	public static function removeBlockIn(int $key) : void{
		if(isset(self::$activeBlockIns[$key])){
			self::$activeBlockIns[$key]->destroyCycles();
			self::$activeBlockIns[$key] = null;
			unset(self::$activeBlockIns[$key]);
		}
	}

	public static function createBlockInEntity(BlockInPractice $blockIn) : int{
		self::removeBlockInEntity($blockIn->getBlockInEntityID());
		$blockInEntity = new BlockInEntity(Location::fromObject(ArenaManager::getArena($blockIn->getArena())->getCoreSpawn()->add(0.5, 0, 0.5), $blockIn->getWorld()), 0, (string) $blockIn->getWorldId());
		self::$blockInEntities[$id = $blockInEntity->getId()] = $blockInEntity;
		return $id;
	}

	public static function removeBlockInEntity(int $id) : void{
		if(isset(self::$blockInEntities[$id])){
			self::$blockInEntities[$id]->despawnFromAll();
			self::$blockInEntities[$id] = null;
			unset(self::$blockInEntities[$id]);
		}
	}

	public static function getBlockInEntityfromEntityId(int $id) : ?BlockInEntity{
		return self::$blockInEntities[$id] ?? null;
	}

	public static function isBlockInWorld(?World $world) : bool{
		return self::getBlockInFromWorld($world) !== null;
	}

	public static function getBlockInFromWorld(?World $world) : ?BlockInPractice{
		if($world === null){
			return null;
		}
		if(str_contains($name = $world->getFolderName(), "duel")){
			return self::$activeBlockIns[(int) str_replace("duel", "", $name)] ?? null;
		}
		return null;
	}

	protected function onUpdate(int $tickDifference) : void{
		foreach(self::$activeClutches as $clutch){
			$clutch->update();
		}
		foreach(self::$activeReduces as $reduce){
			$reduce->update();
		}
		foreach(self::$activeBlockIns as $blockIn){
			$blockIn->update();
		}
	}
}
