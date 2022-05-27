<?php

declare(strict_types=1);

namespace zodiax\game\world;

use pocketmine\Server;
use Webmozart\PathUtil\Path;
use zodiax\data\queue\AsyncTaskQueue;
use zodiax\misc\AbstractAsyncTask;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function mkdir;

class AsyncCreateWorld extends AbstractAsyncTask{

	private string $path;
	private string $arena;
	private int $worldId;

	public function __construct(int $worldId, string $arena){
		$this->path = Path::join(Server::getInstance()->getDataPath(), "worlds", "duel$worldId");
		$this->arena = Path::join(PracticeCore::getDataFolderPath(), "arenas", $arena);
		$this->worldId = $worldId;
	}

	public function onRun() : void{
		$result = false;
		$status = mkdir($this->path);
		if($status === true){
			PracticeUtil::copyDirectory($this->arena, $this->path);
			$result = true;
		}
		$this->setResult(["result" => $result]);
	}

	protected function onTaskComplete(Server $server, PracticeCore $core) : void{
		AsyncTaskQueue::update();
		$result = $this->getResult();
		if($result !== null && $result["result"]){
			$server->getWorldManager()->loadWorld($name = "duel$this->worldId");
			$world = $server->getWorldManager()->getWorldByName($name);
			$world->setTime(0);
			$world->stopTime();
		}
	}
}
