<?php

declare(strict_types=1);

namespace zodiax\tasks;

use pocketmine\scheduler\BulkCurlTask;
use pocketmine\scheduler\BulkCurlTaskOperation;
use pocketmine\Server;
use pocketmine\timings\TimingsHandler;
use pocketmine\utils\InternetException;
use pocketmine\utils\Process;
use Webmozart\PathUtil\Path;
use zodiax\data\log\LogMonitor;
use zodiax\data\queue\AsyncTaskQueue;
use zodiax\misc\AbstractRepeatingTask;
use zodiax\PracticeUtil;
use function count;
use function date;
use function fclose;
use function fopen;
use function fseek;
use function fwrite;
use function http_build_query;
use function is_array;
use function json_decode;
use function mkdir;
use function number_format;
use function round;
use function stream_get_contents;

class DebugTask extends AbstractRepeatingTask{

	public function __construct(){
		parent::__construct(PracticeUtil::secondsToTicks(1));
	}

	public function onUpdate(int $tickDifference) : void{
		$server = Server::getInstance();
		$tps = $server->getTicksPerSecond();
		$load = $server->getTickUsage();
		if($tps < 20 || $load >= 100){
			$player = count($server->getOnlinePlayers());
			$worlds = $server->getWorldManager()->getWorlds();
			$debug = "";
			foreach($worlds as $world){
				$tickrate = round($world->getTickRateTime(), 2);
				$players = count($world->getPlayers());
				$entities = count($world->getEntities());
				$debug .= "{$world->getFolderName()}({$world->getDisplayName()}) = $tickrate ms ($players players, $entities entities) ";
			}
			$memory = number_format(round((Process::getAdvancedMemoryUsage()[1] / 1024) / 1024, 2), 2);
			LogMonitor::debugLog("LAG ({$this->getCurrentTick()}): tps=$tps load=$load memory=$memory mb players=$player $debug");
		}
		if($this->getCurrentTick() % PracticeUtil::hoursToTicks(1) === 0){
			$this->printTimings($this->getCurrentTick());
		}
	}

	private function printTimings(int $currentTick) : void{
		$server = Server::getInstance();
		@mkdir($timingFolder = Path::join($server->getDataPath(), "timings"));
		$fileTimings = fopen(Path::join($timingFolder, "[" . date("Y-m-d") . "] " . date("H-i-s") . " (" . PracticeUtil::ticksToHours($currentTick) . " hour(s)).log"), "a+b");
		foreach(TimingsHandler::printTimings() as $line){
			fwrite($fileTimings, $line . PHP_EOL);
		}
		fseek($fileTimings, 0);
		$data = ["browser" => $agent = $server->getName() . " " . $server->getPocketMineVersion(), "data" => stream_get_contents($fileTimings)];
		fclose($fileTimings);
		$host = $server->getConfigGroup()->getPropertyString("timings.host", "timings.pmmp.io");
		AsyncTaskQueue::addTaskToQueue(new BulkCurlTask(
			[new BulkCurlTaskOperation(
				"https://$host?upload=true",
				10,
				[],
				[
					CURLOPT_HTTPHEADER => [
						"User-Agent: $agent",
						"Content-Type: application/x-www-form-urlencoded"
					],
					CURLOPT_POST => true,
					CURLOPT_POSTFIELDS => http_build_query($data),
					CURLOPT_AUTOREFERER => false,
					CURLOPT_FOLLOWLOCATION => false
				]
			)],
			function(array $results) use ($host, $currentTick) : void{
				$result = $results[0];
				if($result instanceof InternetException){
					return;
				}
				if(is_array($response = json_decode($result->getBody(), true)) && isset($response["id"])){
					LogMonitor::debugLog("TIMINGS (" . PracticeUtil::ticksToHours($currentTick) . " hour(s)): https://$host/?id={$response["id"]}");
				}
			}
		));
		TimingsHandler::reload();
	}
}
