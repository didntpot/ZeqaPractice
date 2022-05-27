<?php

declare(strict_types=1);

namespace zodiax\player\misc\queue;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use RuntimeException;
use Webmozart\PathUtil\Path;
use zodiax\game\items\ItemHandler;
use zodiax\player\info\scoreboard\ScoreboardInfo;
use zodiax\player\misc\queue\thread\QueryThread;
use zodiax\player\misc\queue\thread\QueryThreadPool;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use function count;
use function method_exists;

class QueueHandler{

	private static array $queryResults = [];
	private static array $queuedInfos = [];
	private static QueryThreadPool $query;

	public static function initialize() : void{
		foreach(PracticeCore::getServersInfo() as $region => $servers){
			self::$queryResults[$region] = [];
			foreach($servers as $server => $data){
				self::$queryResults[$region][$server] = ["isonline" => false, "players" => 0, "maxplayers" => 0, "list" => [], "ip" => $data["ip"], "port" => $data["port"]];
				self::$queuedInfos[$server] = [];
			}
		}

		$workers = (new Config(Path::join(PracticeCore::getDataFolderPath(), "settings.yml")))->get("database")["worker-limit"];
		$class_loaders = [];
		$devirion = Server::getInstance()->getPluginManager()->getPlugin("DEVirion");
		if($devirion !== null){
			if(!method_exists($devirion, "getVirionClassLoader")){
				throw new RuntimeException();
			}
			$class_loaders[] = Server::getInstance()->getLoader();
			$class_loaders[] = $devirion->getVirionClassLoader();
		}

		self::$query = new QueryThreadPool();
		$workers = 1;
		for($i = 0; $i < $workers; $i++){
			$thread = new QueryThread(self::$query->getNotifier());
			if(count($class_loaders) > 0){
				$thread->setClassLoaders($class_loaders);
			}
			self::$query->addWorker($thread);
		}
		self::$query->start();
		self::$query->getLeastBusyWorker()->queue();
	}

	public static function addPlayer(Player $player, string $server) : void{
		if(!self::isInQueue($player)){
			if(($session = PlayerManager::getSession($player)) !== null){
				self::$queuedInfos[$server][$player->getName()] = $queue = count(self::$queuedInfos[$server]) + 1;
				if(PracticeCore::isLobby()){
					$session->getScoreboardInfo()->addQueueToLobbyScoreboard($server, $queue);
				}
				ItemHandler::giveLeaveItem($player);
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "You have joined the queue for " . TextFormat::WHITE . "$server #$queue");
			}
		}else{
			self::removePlayer($player, false);
			self::addPlayer($player, $server);
		}
	}

	public static function removePlayer(Player $player, bool $sendMessage = true) : void{
		if(!empty($queue = self::getQueueOf($player))){
			unset(self::$queuedInfos[$server = $queue["server"]][$player->getName()]);
			if(($session = PlayerManager::getSession($player)) !== null){
				if(PracticeCore::isLobby()){
					$session->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_LOBBY);
				}
				ItemHandler::spawnHubItems($player);
				if($sendMessage){
					$session->getPlayer()->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "You have left the queue for " . TextFormat::WHITE . "$server");
				}
			}
			$i = 1;
			$j = 0;
			foreach(self::$queuedInfos[$server] as $player => $queue){
				if(($session = PlayerManager::getSession(PlayerManager::getPlayerExact($player))) !== null){
					self::$queuedInfos[$server][$player] = $queue = $i - $j;
					if(PracticeCore::isLobby()){
						$session->getScoreboardInfo()->addQueueToLobbyScoreboard($server, $queue);
					}
					$session->getPlayer()->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "You have joined the queue for " . TextFormat::WHITE . "$server #$queue");
					$i++;
				}else{
					unset(self::$queuedInfos[$server][$player]);
					$j++;
				}
			}
		}
	}

	public static function getQueueOf(Player $player) : array{
		$name = $player->getName();
		foreach(self::$queuedInfos as $server => $players){
			if(isset($players[$name])){
				return ["server" => $server, "queue" => $players[$name]];
			}
		}
		return [];
	}

	public static function isInQueue(Player $player) : bool{
		return !empty(self::getQueueOf($player));
	}

	public static function getQueryResults() : array{
		return self::$queryResults;
	}

	public static function updateQueryResults(array $queryResults) : void{
		$regions = [];
		foreach($queryResults as $region => $servers){
			$regions[$region] = 0;
			foreach($servers as $server => $data){
				$ip = $data["ip"];
				$port = $data["port"];
				if($data["isonline"]){
					$regions[$region] += $data["players"];
					if(($slots = $data["maxplayers"] - $data["players"]) > 0){
						$i = 0;
						foreach(self::$queuedInfos[$server] as $player => $queue){
							if(($p = PlayerManager::getPlayerExact($player)) !== null){
								$p->transfer($ip, $port);
								$i++;
							}
							unset(self::$queuedInfos[$server][$player]);
							if($i === $slots){
								break;
							}
						}
						$i = 1;
						$j = 0;
						foreach(self::$queuedInfos[$server] as $player => $queue){
							if(($session = PlayerManager::getSession(PlayerManager::getPlayerExact($player))) !== null){
								self::$queuedInfos[$server][$player] = $queue = $i - $j;
								if(PracticeCore::isLobby()){
									$session->getScoreboardInfo()->addQueueToLobbyScoreboard($server, $queue);
								}
								$session->getPlayer()->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "You have joined the queue for " . TextFormat::WHITE . "$server #$queue");
								$i++;
							}else{
								unset(self::$queuedInfos[$server][$player]);
								$j++;
							}
						}
					}
				}
			}
		}
		if(PracticeCore::isLobby()){
			foreach(PlayerManager::getAllSessions() as $session){
				$session->getScoreboardInfo()->updateOnlinePlayersToLobbyScoreboard($regions);
			}
		}
		self::$queryResults = $queryResults;
		self::$query->getLeastBusyWorker()->queue();
	}

	public static function triggerGarbageCollector() : void{
		self::$query->triggerGarbageCollector();
	}

	public static function shutdown() : void{
		self::$query->shutdown();
	}
}
