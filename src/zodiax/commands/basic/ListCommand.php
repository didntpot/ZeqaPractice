<?php

declare(strict_types=1);

namespace zodiax\commands\basic;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\commands\PracticeCommand;
use zodiax\player\misc\queue\QueueHandler;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\ranks\RankHandler;
use function count;
use function implode;
use function str_replace;
use function strtolower;
use function trim;

class ListCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("list", "Show players list of target server", "Usage: /list <server>", []);
		parent::setPermission("practice.permission.list");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender)){
			if(count($args) === 0){
				$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "[" . PracticeCore::COLOR . PracticeCore::getRegionInfo() . TextFormat::DARK_GRAY . "] " . TextFormat::YELLOW . count($players = PlayerManager::getListDisplayNames()) . TextFormat::GRAY . " Online player(s): " . implode(", ", $players));
				return true;
			}
			if(count($args) >= 1){
				$lower = strtolower($name = trim(implode(" ", $args)));
				$flag = false;
				$players = [];
				$queryResults = QueueHandler::getQueryResults();
				foreach($queryResults as $servers){
					foreach($servers as $server => $data){
						if(strtolower($server = str_replace(" Practice", "", $server)) === $lower){
							$name = $server;
							if($data["isonline"]){
								$players = $data["list"];
								$flag = true;
								break;
							}else{
								$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "$name is offline");
								return true;
							}
						}
					}
				}
				if($flag){
					if($lower !== strtolower(PracticeCore::getRegionInfo()) && $sender instanceof Player && !PlayerManager::getSession($sender)?->getRankInfo()->hasHelperPermissions()){
						$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You do not have permission to view players list of $name");
						return true;
					}
					$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "[" . PracticeCore::COLOR . $name . TextFormat::DARK_GRAY . "] " . TextFormat::YELLOW . count($players) . TextFormat::GRAY . " Online player(s): " . implode(", ", $players));
					return true;
				}
				$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Can not find server $name");
				return true;
			}
			$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . $this->getUsage());
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_NONE;
	}
}