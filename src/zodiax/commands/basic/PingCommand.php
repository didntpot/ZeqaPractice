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

class PingCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("ping", "Get the player's ping", "Usage: /ping <player>");
		parent::setPermission("practice.permission.ping");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender)){
			if(count($args) === 0){
				if($sender instanceof Player && ($session = PlayerManager::getSession($sender)) !== null){
					$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "{$sender->getDisplayName()}'s ping: {$session->getPing()} ms");
					return true;
				}
				$sender->sendMessage(PracticeCore::PREFIX . $this->getUsage());
				return true;
			}
			if(($session = PlayerManager::getSession($player = PlayerManager::getPlayerByPrefix($name = trim(implode(" ", $args))))) !== null){
				$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "{$player->getDisplayName()}'s ping: {$session->getPing()} ms");
				return true;
			}
			$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Can not find player $name");
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_NONE;
	}
}
