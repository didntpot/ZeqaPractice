<?php

declare(strict_types=1);

namespace zodiax\duel;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use zodiax\arena\ArenaManager;
use zodiax\duel\types\DuelReplay;
use zodiax\misc\AbstractRepeatingTask;
use zodiax\player\info\duel\ReplayInfo;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use function str_contains;
use function str_replace;

class ReplayHandler extends AbstractRepeatingTask{

	private static array $activeReplays = [];

	public function __construct(){
		parent::__construct();
	}

	public static function startReplay(Player $player, ReplayInfo $info) : void{
		if(($arena = ArenaManager::findDuelArena($kit = $info->getKit())) !== null && ($session = PlayerManager::getSession($player)) !== null && $session->isInHub()){
			$session->setInHub(false);
			self::$activeReplays[$worldId = $arena->getPreWorld()] = new DuelReplay($worldId, $player, $info);
		}else{
			$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "No arenas are available for " . TextFormat::WHITE . $kit);
		}
	}

	public static function getReplays() : array{
		return self::$activeReplays;
	}

	public static function deleteReplay(int $worldId) : void{
		if(isset(self::$activeReplays[$worldId])){
			self::$activeReplays[$worldId]->destroyCycles();
			self::$activeReplays[$worldId] = null;
			unset(self::$activeReplays[$worldId]);
		}
	}

	public static function getReplayFrom(string|Player $player) : ?DuelReplay{
		$name = $player instanceof Player ? $player->getName() : $player;
		foreach(self::$activeReplays as $replay){
			if($replay->getSpectator() === $name){
				return $replay;
			}
		}
		return null;
	}

	public static function isReplayWorld(?World $world) : bool{
		return self::getReplayFromWorld($world) !== null;
	}

	public static function getReplayFromWorld(?World $world) : ?DuelReplay{
		if($world === null){
			return null;
		}
		if(str_contains($name = $world->getFolderName(), "duel")){
			return self::$activeReplays[(int) str_replace("duel", "", $name)] ?? null;
		}
		return null;
	}

	protected function onUpdate(int $tickDifference) : void{
		foreach(self::$activeReplays as $replay){
			$replay->update();
		}
	}
}