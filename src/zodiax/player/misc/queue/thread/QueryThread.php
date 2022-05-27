<?php

declare(strict_types=1);

namespace zodiax\player\misc\queue\thread;

use mmm545\libgamespyquery\GameSpyQuery;
use mmm545\libgamespyquery\GameSpyQueryException;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\thread\Thread;
use Threaded;
use zodiax\player\misc\queue\QueueHandler;
use zodiax\PracticeCore;
use function gc_collect_cycles;
use function gc_enable;
use function gc_mem_caches;
use function igbinary_serialize;
use function igbinary_unserialize;
use function str_replace;

class QueryThread extends Thread{

	public int $busy_score = 0;
	private SleeperNotifier $notifier;
	private Threaded $actionQueue;
	private Threaded $actionResults;
	private bool $running;
	private string $pluginFolder;
	private string $serversInfo;
	private string $libAutoLoadPath;
	private string $exceptionAutoLoadPath;

	public function __construct(SleeperNotifier $notifier){
		$this->notifier = $notifier;
		$this->actionQueue = new Threaded();
		$this->actionResults = new Threaded();
		$this->pluginFolder = PracticeCore::getPluginFolder();
		$this->serversInfo = igbinary_serialize(PracticeCore::getServersInfo());
		$this->libAutoLoadPath = str_replace("zodiax", "mmm545", $this->pluginFolder) . "/libgamespyquery/GameSpyQuery.php";
		$this->exceptionAutoLoadPath = str_replace("zodiax", "mmm545", $this->pluginFolder) . "/libgamespyquery/GameSpyQueryException.php";
	}

	public function start(int $options = PTHREADS_INHERIT_ALL) : bool{
		$this->running = true;
		return parent::start($options);
	}

	public function sleep() : void{
		$this->synchronized(function() : void{
			if($this->running){
				$this->wait();
			}
		});
	}

	public function stop() : void{
		$this->running = false;
		$this->synchronized(function() : void{
			$this->notify();
		});
	}

	public function queue() : void{
		$this->synchronized(function() : void{
			$this->actionQueue[] = igbinary_serialize("query");
			++$this->busy_score;
			$this->notifyOne();
		});
	}

	public function triggerGarbageCollector() : void{
		$this->synchronized(function() : void{
			$this->actionQueue[] = igbinary_serialize("garbage_collector");
			$this->notifyOne();
		});
	}

	public function onRun() : void{
		while($this->running){
			while(($queue = $this->actionQueue->shift()) !== null){
				$queue = igbinary_unserialize($queue);
				$queryResults = [];
				if($queue === "query"){
					require_once $this->libAutoLoadPath;
					require_once $this->exceptionAutoLoadPath;
					$serversInfo = igbinary_unserialize($this->serversInfo);
					foreach($serversInfo as $region => $servers){
						$queryResults[$region] = [];
						foreach($servers as $server => $data){
							$ip = $data["ip"];
							$port = $data["port"];
							try{
								$query = GameSpyQuery::query($ip, (int) $port);
								$queryResults[$region][$server] = ["ip" => $ip, "port" => $port];
								$queryResults[$region][$server]["isonline"] = true;
								$queryResults[$region][$server]["players"] = $query->get("numplayers");
								$queryResults[$region][$server]["maxplayers"] = $query->get("maxplayers");
								$queryResults[$region][$server]["list"] = $query->get("players");
							}catch(GameSpyQueryException $e){
								$queryResults[$region][$server] = ["isonline" => false, "players" => 0, "maxplayers" => 0, "list" => [], "ip" => $ip, "port" => $port];
							}
						}
					}
					$this->actionResults[] = igbinary_serialize($queryResults);
					$this->notifier->wakeupSleeper();
				}elseif($queue === "garbage_collector"){
					gc_enable();
					gc_collect_cycles();
					gc_mem_caches();
				}
			}
			$this->sleep();
		}
	}

	public function collectActionResults() : void{
		while(($result = $this->actionResults->shift()) !== null){
			QueueHandler::updateQueryResults(igbinary_unserialize($result));
			--$this->busy_score;
		}
	}
}
