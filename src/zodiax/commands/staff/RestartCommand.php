<?php

declare(strict_types=1);

namespace zodiax\commands\staff;

use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use zodiax\commands\PracticeCommand;
use zodiax\PracticeCore;
use zodiax\ranks\RankHandler;
use function count;
use function strtolower;

class RestartCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("restart", "Restart the server", "Usage: /restart", ["rs"]);
		parent::setPermission("practice.permission.restart");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender)){
			if(count($args) === 1 && strtolower($args[0]) === "confirm"){
				$sender->sendMessage(PracticeCore::PREFIX . TextFormat::YELLOW . "Restarting...");
				PracticeCore::setRestart(true);
				return true;
			}
			$sender->sendMessage(PracticeCore::PREFIX . $this->getUsage() . " confirm");
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_OWNER;
	}
}