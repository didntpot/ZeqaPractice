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
use function count;
use function implode;
use function trim;

class StatsCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("stats", "Get the player's stats", "Usage: /stats <player>");
		parent::setPermission("practice.permission.stats");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender)){
			if(count($args) === 0){
				if($sender instanceof Player){
					$sender->sendMessage(PlayerManager::getSession($sender)?->getStats());
					return true;
				}
				$sender->sendMessage(PracticeCore::PREFIX . $this->getUsage());
				return true;
			}
			if(($player = PlayerManager::getPlayerByPrefix($name = trim(implode(" ", $args)))) !== null){
				$sender->sendMessage(PlayerManager::getSession($player)?->getStats());
				return true;
			}
			$sender->sendMessage(PracticeCore::PREFIX . PracticeCore::PREFIX . TextFormat::RED . "Can not find player $name");
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_NONE;
	}
}
