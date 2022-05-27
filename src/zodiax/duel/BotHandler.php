<?php

declare(strict_types=1);

namespace zodiax\duel;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use zodiax\arena\ArenaManager;
use zodiax\duel\misc\QueuedBot;
use zodiax\duel\types\BotDuel;
use zodiax\game\entity\CombatBot;
use zodiax\game\items\ItemHandler;
use zodiax\kits\KitsManager;
use zodiax\misc\AbstractRepeatingTask;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use function array_shift;
use function count;
use function str_contains;
use function str_replace;

class BotHandler extends AbstractRepeatingTask{

	private static array $queuedPlayers = [];
	private static array $activeDuels = [];

	public function __construct(){
		parent::__construct();
	}

	public static function placeInQueue(Player $player, string $kit, int $mode = CombatBot::EASY) : void{
		if(count(self::$activeDuels) < 3){
			self::placeInDuel($player, $kit, $mode);
		}else{
			ItemHandler::giveLeaveItem($player);
			PlayerManager::getSession($player)->getScoreboardInfo()->addBotQueueToScoreboard($mode, $kit);
			$modeName = match ($mode) {
				CombatBot::EASY => "Easy",
				CombatBot::MEDIUM => "Medium",
				CombatBot::HARD => "Hard"
			};
			$player->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "You have joined the bot duel queue for " . TextFormat::WHITE . "$modeName $kit");
			self::$queuedPlayers[$player->getName()] = new QueuedBot($player, $kit, $mode);
		}
	}

	public static function placeInDuel(Player $player, string $kit, int $mode = CombatBot::EASY) : void{
		if(($kit = KitsManager::getKit($kit)) === null || ($arena = ArenaManager::findBotArena($kit->getName())) === null){
			self::removeFromQueue($player, false);
			ItemHandler::spawnHubItems($player);
			$modeName = match ($mode) {
				CombatBot::EASY => "Easy",
				CombatBot::MEDIUM => "Medium",
				CombatBot::HARD => "Hard"
			};
			$msg = PracticeCore::PREFIX . TextFormat::RED . "No arenas are available for " . TextFormat::WHITE . $modeName . " " . $kit?->getName() ?? $kit;
			$player->sendMessage($msg);
			return;
		}
		if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub()){
			$session->getExtensions()->clearAll();
			$session->setInHub(false);
			self::$activeDuels[$worldId = $arena->getPreWorld()] = new BotDuel($worldId, $player, $kit, $mode, $arena);
		}
	}

	public static function getQueueOf(string|Player $player) : ?QueuedBot{
		return self::$queuedPlayers[$player instanceof Player ? $player->getName() : $player] ?? null;
	}

	public static function removeFromQueue(Player $player, bool $sendMessage = true) : void{
		if(!isset(self::$queuedPlayers[$name = $player->getName()])){
			return;
		}
		$queue = self::$queuedPlayers[$name];
		unset(self::$queuedPlayers[$name]);
		if(($session = PlayerManager::getSession($player)) !== null){
			$session->getScoreboardInfo()->removeQueueFromScoreboard();
			if($sendMessage){
				$modeName = match ($queue->getMode()) {
					CombatBot::EASY => "Easy",
					CombatBot::MEDIUM => "Medium",
					CombatBot::HARD => "Hard"
				};
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "You have left the queue for " . TextFormat::WHITE . TextFormat::WHITE . "$modeName {$queue->getKit()}");
			}
		}
	}

	public static function getDuels(bool $asInt = false) : int|array{
		return $asInt ? count(self::$activeDuels) : self::$activeDuels;
	}

	public static function getDuel($player) : ?BotDuel{
		foreach(self::$activeDuels as $duel){
			if($duel->isPlayer($player)){
				return $duel;
			}
		}
		return null;
	}

	public static function getDuelFromSpec($player) : ?BotDuel{
		foreach(self::$activeDuels as $duel){
			if($duel->isSpectator($player)){
				return $duel;
			}
		}
		return null;
	}

	public static function removeDuel(int $key) : void{
		if(isset(self::$activeDuels[$key])){
			self::$activeDuels[$key]->destroyCycles();
			self::$activeDuels[$key] = null;
			unset(self::$activeDuels[$key]);
			self::putQueueInDuel();
		}
	}

	private static function putQueueInDuel() : void{
		if(!empty(self::$queuedPlayers)){
			$queue = array_shift(self::$queuedPlayers);
			if(($player = PlayerManager::getPlayerExact($queue->getPlayer())) !== null){
				self::placeInDuel($player, $queue->getKit(), $queue->getMode());
			}else{
				self::putQueueInDuel();
			}
		}
	}

	public static function isDuelWorld(?World $world) : bool{
		return self::getDuelFromWorld($world) !== null;
	}

	public static function getDuelFromWorld(?World $world) : ?BotDuel{
		if($world === null){
			return null;
		}
		if(str_contains($name = $world->getFolderName(), "duel")){
			return self::$activeDuels[(int) str_replace("duel", "", $name)] ?? null;
		}
		return null;
	}

	protected function onUpdate(int $tickDifference) : void{
		foreach(self::$activeDuels as $duel){
			$duel->update();
		}
	}
}