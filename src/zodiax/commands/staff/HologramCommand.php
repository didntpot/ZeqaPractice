<?php

declare(strict_types=1);

namespace zodiax\commands\staff;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\commands\PracticeCommand;
use zodiax\game\hologram\HologramHandler;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\ranks\RankHandler;
use function count;
use function in_array;
use function strtolower;

class HologramCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("hologram", "Place the hologram", "Usage: /hologram <elo:stats:rank:rule>", []);
		parent::setPermission("practice.permission.hologram");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player && ($session = PlayerManager::getSession($sender)) !== null){
			if($session->isInHub()){
				if(count($args) === 1 && in_array($type = strtolower($args[0]), ["elo", "stats", "rank", "rule"], true)){
					$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully placed $type hologram");
					HologramHandler::setLeaderboardHologram($type, $sender->getPosition());
					return true;
				}
				$sender->sendMessage(PracticeCore::PREFIX . $this->getUsage());
				return true;
			}
			$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You can not set hologram in this world");
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_OWNER;
	}
}