<?php

declare(strict_types=1);

namespace zodiax\misc;

use pocketmine\event\Listener;
use pocketmine\Server;
use zodiax\PracticeCore;

abstract class AbstractListener implements Listener{

	public function __construct(){
		Server::getInstance()->getPluginManager()->registerEvents($this, PracticeCore::getInstance());
	}
}