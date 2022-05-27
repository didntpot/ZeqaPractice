<?php

declare(strict_types=1);

namespace zodiax\commands\staff;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\commands\PracticeCommand;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\ranks\RankHandler;

class FlyCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("fly", "Enable/Disable fly", "Usage: /fly", []);
		parent::setPermission("practice.permission.fly");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player && ($session = PlayerManager::getSession($sender)) !== null){
			if(!$sender->getAllowFlight()){
				$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Fly enabled");
				$session->getExtensions()->enableFlying(true);
				return true;
			}
			$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Fly disabled");
			$session->getExtensions()->enableFlying(false);
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_ADMIN;
	}
}
