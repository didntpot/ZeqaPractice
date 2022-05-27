<?php

declare(strict_types=1);

namespace zodiax\commands\basic;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\commands\PracticeCommand;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\ranks\RankHandler;

class HubCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("hub", "Back to hub", "Usage: /hub", ["spawn", "lobby"]);
		parent::setPermission("practice.permission.hub");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player && ($session = PlayerManager::getSession($sender)) !== null && $this->canUseCommand($sender)){
			$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "You have been teleported to the hub");
			$session->reset();
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_NONE;
	}
}
