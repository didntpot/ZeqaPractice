<?php

declare(strict_types=1);

namespace zodiax\game\world;

use pocketmine\Server;
use Webmozart\PathUtil\Path;
use zodiax\data\queue\AsyncTaskQueue;
use zodiax\misc\AbstractAsyncTask;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class AsyncDeleteWorld extends AbstractAsyncTask{

	private string $path;

	public function __construct(string $path){
		$this->path = Path::join($path);
	}

	public function onRun() : void{
		PracticeUtil::removeDirectory($this->path);
	}

	protected function onTaskComplete(Server $server, PracticeCore $core) : void{
		AsyncTaskQueue::update();
	}
}
