<?php

declare(strict_types=1);

namespace zodiax\commands\staff;

use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use zodiax\commands\PracticeCommand;
use zodiax\data\log\LogMonitor;
use zodiax\PracticeCore;
use zodiax\ranks\RankHandler;

class DailychatCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("dailychat", "Log chat history to discord server", "Usage: /dailychat", ["dchat"]);
		parent::setPermission("practice.permission.dailychat");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender)){
			LogMonitor::dailyChatLog();
			$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully sent chat history to discord server");
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_HELPER;
	}
}
