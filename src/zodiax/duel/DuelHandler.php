<?php

declare(strict_types=1);

namespace zodiax\duel;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use zodiax\arena\ArenaManager;
use zodiax\duel\misc\QueuedPlayer;
use zodiax\duel\types\PlayerDuel;
use zodiax\game\items\ItemHandler;
use zodiax\kits\KitsManager;
use zodiax\misc\AbstractRepeatingTask;
use zodiax\player\info\EloInfo;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\utils\ScoreboardUtil;
use function abs;
use function count;
use function str_contains;
use function str_replace;

class DuelHandler extends AbstractRepeatingTask{

	private static array $queuedPlayers = [];
	private static array $activeDuels = [];

	public function __construct(){
		parent::__construct();
	}

	public static function placeInQueue(Player $player, string $kit, bool $ranked = false) : void{
		self::removeFromQueue($player, false);
		ItemHandler::giveLeaveItem($player);
		PlayerManager::getSession($player)->getScoreboardInfo()->addQueueToScoreboard($ranked, $kit);
		$player->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "You have joined the queue for " . TextFormat::WHITE . ($ranked ? "Ranked " : "Unranked ") . $kit);
		self::$queuedPlayers[$name = $player->getName()] = new QueuedPlayer($player, $kit, $ranked);
		if(/*!$ranked && */ ($matched = self::findMatch(self::$queuedPlayers[$name])) !== null){
			self::removeFromQueue($name, false);
			self::removeFromQueue($matched = $matched->getPlayer(), false);
			self::placeInDuel($player, PlayerManager::getPlayerExact($matched), $kit, $ranked);
		}
		ScoreboardUtil::updateSpawnScoreboard(ScoreboardUtil::IN_QUEUES);
	}

	public static function findMatch(QueuedPlayer $queue) : ?QueuedPlayer{
		if(($pSession = PlayerManager::getSession($player = PlayerManager::getPlayerExact($pName = $queue->getPlayer()))) !== null){
			$ping = $pSession->getPing();
			$deviceOnly = $pSession->getSettingsInfo()->isFairQueue();
			$pingRange = $pSession->getSettingsInfo()->isPingRange();
			$isPE = $pSession->getClientInfo()->isPE();
			$kitName = $queue->getKit();
			$isRanked = $queue->isRanked();
			//$eloRange = $queue->getEloRange();
			$playerElo = $pSession->getEloInfo()->getEloFromKit($kitName);
			$playerInfo = $pSession->getClientInfo();
			/*$min = max($playerElo - $eloRange, 500);
			$max = min($playerElo + $eloRange, 2000);
			if($isRanked){
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "Searching for " . TextFormat::WHITE . "Ranked $kitName " . TextFormat::GRAY . "[" . TextFormat::YELLOW . "$min - $max" . TextFormat::GRAY . "]");
			}*/
			foreach(self::$queuedPlayers as $key => $queued){
				if(($qSession = PlayerManager::getSession(PlayerManager::getPlayerExact($qName = $queued->getPlayer()))) !== null){
					$isMatch = false;
					if($pName === $qName){
						continue;
					}
					if($isRanked === $queued->isRanked() && $kitName === $queued->getKit()){
						$isMatch = true;
						if((($deviceOnly || $qSession->getSettingsInfo()->isFairQueue()) && $isPE !== $qSession->getClientInfo()->isPE()) || (($pingRange || $qSession->getSettingsInfo()->isPingRange()) && abs($ping - $qSession->getPing()) > 50)){
							$isMatch = false;
						}
						if($isMatch && $isRanked){
							//$eloRange = $queued->getEloRange();
							$queuedElo = $qSession->getEloInfo()->getEloFromKit($kitName);
							$queuedInfo = $qSession->getClientInfo();
							$firstResult = EloInfo::calculateElo($playerElo, $queuedElo, $playerInfo, $queuedInfo);
							$secondResult = EloInfo::calculateElo($queuedElo, $playerElo, $queuedInfo, $playerInfo);
							if(/*$queuedElo < $min || $queuedElo > $max || $playerElo < max($queuedElo - $eloRange, 500) || $playerElo > min($queuedElo + $eloRange, 2000) || */ $firstResult->winnerEloChange <= 0 || $firstResult->loserEloChange <= 0 || $secondResult->winnerEloChange <= 0 || $secondResult->loserEloChange <= 0){
								$isMatch = false;
							}
						}
					}
					if($isMatch){
						return $queued;
					}
				}else{
					self::removeFromQueue($key, false);
				}
			}
		}
		return null;
	}

	public static function placeInDuel(Player $player1, Player $player2, string $kit, bool $ranked = false) : void{
		if(($kit = KitsManager::getKit($kit)) === null || ($arena = ArenaManager::findDuelArena($kit->getName())) === null){
			ItemHandler::spawnHubItems($player1);
			ItemHandler::spawnHubItems($player2);
			PlayerManager::getSession($player1)?->getScoreboardInfo()->removeQueueFromScoreboard();
			PlayerManager::getSession($player2)?->getScoreboardInfo()->removeQueueFromScoreboard();
			$msg = PracticeCore::PREFIX . TextFormat::RED . "No arenas are available for " . TextFormat::WHITE . ($ranked ? "Ranked" : "Unranked") . " " . $kit?->getName() ?? $kit;
			$player1->sendMessage($msg);
			$player2->sendMessage($msg);
			return;
		}
		if(($p1Session = PlayerManager::getSession($player1)) !== null && ($p2Session = PlayerManager::getSession($player2)) !== null && $p1Session->isInHub() && $p2Session->isInHub()){
			$msg = PracticeCore::PREFIX . TextFormat::GRAY . "Found a " . TextFormat::WHITE . ($ranked ? "Ranked " : "Unranked ") . $kit->getName() . TextFormat::GRAY . " match against " . TextFormat::RED;
			$p1Session->getScoreboardInfo()->removeQueueFromScoreboard();
			$p2Session->getScoreboardInfo()->removeQueueFromScoreboard();
			$player1->sendMessage($msg . $player2->getDisplayName() . ($ranked ? TextFormat::GRAY . " (" . TextFormat::WHITE . "{$p2Session->getEloInfo()->getEloFromKit($kit->getName())} Elo" . TextFormat::GRAY . ")" : ""));
			$p1Session->getExtensions()->clearAll();
			$p1Session->setInHub(false);
			$player2->sendMessage($msg . $player1->getDisplayName() . ($ranked ? TextFormat::GRAY . " (" . TextFormat::WHITE . "{$p1Session->getEloInfo()->getEloFromKit($kit->getName())} Elo" . TextFormat::GRAY . ")" : ""));
			$p2Session->getExtensions()->clearAll();
			$p2Session->setInHub(false);
			self::$activeDuels[$worldId = $arena->getPreWorld()] = new PlayerDuel($worldId, $player1, $player2, $kit, $ranked, $arena);
			ScoreboardUtil::updateSpawnScoreboard(ScoreboardUtil::IN_FIGHTS);
			ScoreboardUtil::updateSpawnScoreboard(ScoreboardUtil::IN_QUEUES);
		}
	}

	public static function getPlayersInQueue(bool $ranked, string $kit) : int{
		$count = 0;
		foreach(self::$queuedPlayers as $pQueue){
			if($kit === $pQueue->getKit() && $pQueue->isRanked() === $ranked){
				$count++;
			}
		}
		return $count;
	}

	public static function getEveryoneInQueues() : int{
		return count(self::$queuedPlayers);
	}

	public static function getQueueOf(string|Player $player) : ?QueuedPlayer{
		return self::$queuedPlayers[$player instanceof Player ? $player->getName() : $player] ?? null;
	}

	public static function removeFromQueue(string|Player $player, bool $sendMessage = true) : void{
		if(($queue = self::getQueueOf($player)) !== null){
			//self::$queuedPlayers[$name = $queue->getPlayer()]->getHandler()?->cancel();
			unset(self::$queuedPlayers[$name = $queue->getPlayer()]);
			if(($session = PlayerManager::getSession($player = PlayerManager::getPlayerExact($name))) !== null){
				$session->getScoreboardInfo()->removeQueueFromScoreboard();
				if($sendMessage){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "You have left the queue for " . TextFormat::WHITE . ($queue->isRanked() ? "Ranked" : "Unranked") . " " . $queue->getKit());
				}
			}
			ScoreboardUtil::updateSpawnScoreboard(ScoreboardUtil::IN_QUEUES);
		}
	}

	/**
	 * @return PlayerDuel[]|int
	 */
	public static function getDuels(bool $asInt = false) : array|int{
		return $asInt ? count(self::$activeDuels) : self::$activeDuels;
	}

	public static function getDuel($player) : ?PlayerDuel{
		foreach(self::$activeDuels as $duel){
			if($duel->isPlayer($player)){
				return $duel;
			}
		}
		return null;
	}

	public static function getDuelFromSpec($player) : ?PlayerDuel{
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
			ScoreboardUtil::updateSpawnScoreboard(ScoreboardUtil::IN_FIGHTS);
		}
	}

	public static function isDuelWorld(?World $world) : bool{
		return self::getDuelFromWorld($world) !== null;
	}

	public static function getDuelFromWorld(?World $world) : ?PlayerDuel{
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