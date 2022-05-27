<?php

declare(strict_types=1);

namespace zodiax\commands\staff;

use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use zodiax\commands\PracticeCommand;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\ranks\RankHandler;
use function count;
use function implode;
use function trim;

class PlayerInfoCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("pinfo", "Gets the player's information", "Usage: /pinfo <player>", []);
		parent::setPermission("practice.permission.pinfo");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender)){
			if(count($args) >= 1){
				if(($player = PlayerManager::getPlayerByPrefix($name = trim(implode(" ", $args)))) !== null){
					$sender->sendMessage(PlayerManager::getSession($player)?->getInfo() ?? "");
					return true;
				}
				$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . $name . " is not online");
				return true;
			}
			$sender->sendMessage(PracticeCore::PREFIX . $this->getUsage());
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_HELPER;
	}
}