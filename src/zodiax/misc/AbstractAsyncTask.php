<?php

declare(strict_types=1);

namespace zodiax\misc;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use zodiax\PracticeCore;

abstract class AbstractAsyncTask extends AsyncTask{

	public function onCompletion() : void{
		$server = Server::getInstance();
		$core = $server->getPluginManager()->getPlugin("Practice");
		if($core instanceof PracticeCore && $core->isEnabled()){
			$this->onTaskComplete($server, $core);
		}
	}

	protected function onTaskComplete(Server $server, PracticeCore $core) : void{
	}
}