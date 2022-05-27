<?php

declare(strict_types=1);

namespace zodiax\commands\staff;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\commands\PracticeCommand;
use zodiax\player\misc\tasks\VanishTask;
use zodiax\player\misc\VanishHandler;
use zodiax\PracticeCore;
use zodiax\ranks\RankHandler;

class VanishCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("vanish", "Enable/Disable vanish", "Usage: /vanish", ["v"]);
		parent::setPermission("practice.permission.vanish");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player){
			if(!VanishHandler::isVanish($sender)){
				$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Vanish enabled");
				VanishHandler::addToVanish($sender, true);
				new VanishTask($sender);
				return true;
			}
			$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Vanish disabled");
			VanishHandler::removeFromVanish($sender);
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_HELPER;
	}
}