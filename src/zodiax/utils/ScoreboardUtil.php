<?php

declare(strict_types=1);

namespace zodiax\utils;

use pocketmine\utils\TextFormat;
use zodiax\duel\DuelHandler;
use zodiax\party\duel\PartyDuelHandler;
use zodiax\player\info\scoreboard\ScoreboardInfo;
use zodiax\player\misc\queue\QueueHandler;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use function count;

class ScoreboardUtil{

	const ONLINE_PLAYERS = 0;
	const IN_QUEUES = 1;
	const IN_FIGHTS = 2;

	public static function updateSpawnScoreboard(int $type) : void{
		switch($type){
			case self::ONLINE_PLAYERS:
				self::updateOnlinePlayers();
				break;
			case self::IN_QUEUES:
				self::updateInQueues();
				break;
			case self::IN_FIGHTS:
				self::updateInFights();
				break;
		}
	}

	private static function updateOnlinePlayers() : void{
		$sessions = PlayerManager::getAllSessions();
		$online = count($sessions);
		if(PracticeCore::isLobby()){
			foreach(QueueHandler::getQueryResults() as $servers){
				foreach($servers as $server){
					$online += $server["players"];
				}
			}
		}
		foreach($sessions as $session){
			$info = $session->getScoreboardInfo();
			switch($info->getScoreboardType()){
				case ScoreboardInfo::SCOREBOARD_SPAWN:
				case ScoreboardInfo::SCOREBOARD_LOBBY:
					$info->updateLineOfScoreboard(1, PracticeCore::COLOR . " Online: " . TextFormat::WHITE . $online);
					break;
			}
		}
	}

	private static function updateInQueues() : void{
		$sessions = PlayerManager::getAllSessions();
		$numInQueues = DuelHandler::getEveryoneInQueues();
		$numInPartyQueues = PartyDuelHandler::getEveryPartiesInQueues();
		foreach($sessions as $session){
			$info = $session->getScoreboardInfo();
			switch($info->getScoreboardType()){
				case ScoreboardInfo::SCOREBOARD_SPAWN:
					$info->updateLineOfScoreboard(7, PracticeCore::COLOR . " In-Queues: " . TextFormat::WHITE . $numInQueues);
					break;
				case ScoreboardInfo::SCOREBOARD_PARTY:
					$info->updateQueueToPartyScoreboard($numInPartyQueues);
					break;
			}
		}
	}

	private static function updateInFights() : void{
		$sessions = PlayerManager::getAllSessions();
		$numInFights = DuelHandler::getDuels(true) * 2;
		$numInPartyFights = PartyDuelHandler::getDuels(true) * 2;
		foreach($sessions as $session){
			$info = $session->getScoreboardInfo();
			switch($info->getScoreboardType()){
				case ScoreboardInfo::SCOREBOARD_SPAWN:
					$info->updateLineOfScoreboard(6, PracticeCore::COLOR . " In-Fights: " . TextFormat::WHITE . $numInFights);
					break;
				case ScoreboardInfo::SCOREBOARD_PARTY:
					$info->updateFightToPartyScoreboard($numInPartyFights);
					break;
			}
		}
	}
}