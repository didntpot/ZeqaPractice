<?php

declare(strict_types=1);

namespace zodiax\party\duel;

use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use zodiax\arena\ArenaManager;
use zodiax\game\items\ItemHandler;
use zodiax\kits\KitsManager;
use zodiax\misc\AbstractRepeatingTask;
use zodiax\party\duel\misc\QueuedParty;
use zodiax\party\PartyManager;
use zodiax\party\PracticeParty;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\utils\ScoreboardUtil;
use function count;
use function str_contains;
use function str_replace;

class PartyDuelHandler extends AbstractRepeatingTask{

	private static array $queuedParties = [];
	private static array $activeDuels = [];

	public function __construct(){
		parent::__construct();
	}

	public static function placeInQueue(PracticeParty $party, string $kit) : void{
		$owner = PlayerManager::getPlayerExact($party->getOwner());
		$psize = $party->getPlayers(true);
		$theQueue = new QueuedParty($party, $kit);
		self::$queuedParties[$name = $party->getName()] = $theQueue;
		ItemHandler::giveLeaveItem($owner);
		$msg = PracticeCore::PREFIX . TextFormat::GRAY . "You have joined the queue for " . TextFormat::WHITE . "{$psize}vs$psize $kit";
		foreach($party->getPlayers() as $member){
			if(($msession = PlayerManager::getSession($member = PlayerManager::getPlayerExact($member))) !== null){
				$member->sendMessage($msg);
				$msession->getScoreboardInfo()->addQueueToPartyScoreboard($psize, $kit);
			}
		}
		if(($matched = self::findMatch($theQueue)) !== null && $matched instanceof QueuedParty){
			unset(self::$queuedParties[$name], self::$queuedParties[$matched->getParty()]);
			self::placeInDuel($party, PartyManager::getPartyFromName($matched->getParty()), $kit);
		}
		ScoreboardUtil::updateSpawnScoreboard(ScoreboardUtil::IN_QUEUES);
	}

	public static function findMatch(QueuedParty $queue) : ?QueuedParty{
		$party = $queue->getParty();
		$kitName = $queue->getKit();
		$size = $queue->getSize();
		foreach(self::$queuedParties as $queued){
			$queuedParty = $queued->getParty();
			$isMatch = false;
			if($party === $queuedParty || $size !== $queued->getSize()){
				continue;
			}
			if($kitName === $queued->getKit()){
				$isMatch = true;
			}
			if($isMatch){
				return $queued;
			}
		}
		return null;
	}

	public static function placeInDuel(PracticeParty $p1, PracticeParty $p2, string $kit) : void{
		if(($kit = KitsManager::getKit($kit)) === null || ($arena = ArenaManager::findDuelArena($kit->getName())) === null){
			self::removeFromQueue($p1);
			self::removeFromQueue($p2);
			$size = $p1->getPlayers(true);
			$msg = PracticeCore::PREFIX . TextFormat::RED . "No arenas are available for " . TextFormat::WHITE . $size . "vs" . $size . " " . $kit?->getName() ?? $kit;
			foreach($p1->getPlayers() as $member){
				if(($member = PlayerManager::getPlayerExact($member)) !== null){
					ItemHandler::spawnHubItems($member);
					$member->sendMessage($msg);
				}
			}
			foreach($p2->getPlayers() as $member){
				if(($member = PlayerManager::getPlayerExact($member)) !== null){
					ItemHandler::spawnHubItems($member);
					$member->sendMessage($msg);
				}
			}
			return;
		}
		$size = $p1->getPlayers(true);
		$msg = PracticeCore::PREFIX . TextFormat::GRAY . "Found a " . TextFormat::WHITE . $size . "vs" . $size . " " . $kit->getName() . TextFormat::GRAY . " match against " . TextFormat::RED;
		foreach($p1->getPlayers() as $member){
			if(($msession = PlayerManager::getSession($member = PlayerManager::getPlayerExact($member))) !== null){
				$member->sendMessage($msg . $p2->getName());
				$msession->getExtensions()->clearAll();
				$msession->setInHub(false);
			}
		}
		foreach($p2->getPlayers() as $member){
			if(($msession = PlayerManager::getSession($member = PlayerManager::getPlayerExact($member))) !== null){
				$member->sendMessage($msg . $p1->getName());
				$msession->getExtensions()->clearAll();
				$msession->setInHub(false);
			}
		}
		self::$activeDuels[$worldId = $arena->getPreWorld()] = new PartyDuel($worldId, $p1, $p2, $kit, $arena);
		ScoreboardUtil::updateSpawnScoreboard(ScoreboardUtil::IN_FIGHTS);
		ScoreboardUtil::updateSpawnScoreboard(ScoreboardUtil::IN_QUEUES);
	}

	public static function isInQueue(string|PracticeParty $party) : bool{
		return isset(self::$queuedParties[$party instanceof PracticeParty ? $party->getName() : $party]);
	}

	public static function removeFromQueue(PracticeParty $party) : void{
		if(!isset(self::$queuedParties[$name = $party->getName()])){
			return;
		}
		$queue = self::$queuedParties[$name];
		unset(self::$queuedParties[$name]);
		$size = $queue->getSize();
		$msg = PracticeCore::PREFIX . TextFormat::GRAY . "You have left the queue for " . TextFormat::WHITE . $size . "vs" . $size . " " . $queue->getKit();
		foreach($party->getPlayers() as $member){
			if(($msession = PlayerManager::getSession($member = PlayerManager::getPlayerExact($member))) !== null){
				$member->sendMessage($msg);
				$msession->getScoreboardInfo()->removeQueueFromPartyScoreboard();
			}
		}
		ScoreboardUtil::updateSpawnScoreboard(ScoreboardUtil::IN_QUEUES);
	}

	public static function getPartiesInQueue(string $kit) : int{
		$count = 0;
		foreach(self::$queuedParties as $pQueue){
			if($kit === $pQueue->getKit()){
				$count++;
			}
		}
		return $count;
	}

	public static function getQueueOf($party) : ?QueuedParty{
		return self::$queuedParties[$party instanceof PracticeParty ? $party->getName() : $party] ?? null;
	}

	public static function getEveryPartiesInQueues() : int{
		return count(self::$queuedParties);
	}

	/**
	 * @return PartyDuel[]|int
	 */
	public static function getDuels(bool $asInt = false) : array|int{
		return $asInt ? count(self::$activeDuels) : self::$activeDuels;
	}

	public static function getDuel($party) : ?PartyDuel{
		if($party === null){
			return null;
		}
		foreach(self::$activeDuels as $duel){
			if($duel->isParty($party)){
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

	public static function getDuelFromWorld(?World $world) : ?PartyDuel{
		if($world === null){
			return null;
		}
		if(str_contains($name = $world->getFolderName(), "duel")){
			return self::$activeDuels[(int) str_replace("duel", "", $name)] ?? null;
		}
		return null;
	}

	public static function getDuelFromSpec($player) : ?PartyDuel{
		foreach(self::$activeDuels as $duel){
			if($duel->isSpectator($player)){
				return $duel;
			}
		}
		return null;
	}

	protected function onUpdate(int $tickDifference) : void{
		foreach(self::$activeDuels as $duel){
			$duel->update();
		}
	}
}