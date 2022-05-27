<?php

declare(strict_types=1);

namespace zodiax\commands\staff;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlThread;
use zodiax\commands\PracticeCommand;
use zodiax\data\database\DatabaseManager;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\ranks\RankHandler;
use function count;
use function strtolower;
use function substr;

class SetRankCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("setrank", "Set ranks", "Usage: /setrank <player> <ranks>", []);
		parent::setPermission("practice.permission.set-rank");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender)){
			$size = count($args);
			if($size > 1 && $size <= 6){
				$ranks = [];
				for($i = 1; $i < $size; $i++){
					if(($rank = RankHandler::getRank($args[$i])) !== null){
						if(($rank->getPermission() === RankHandler::PERMISSION_OWNER || $rank->getPermission() === RankHandler::PERMISSION_ADMIN) && $sender instanceof Player && !PlayerManager::getSession($sender)?->getRankInfo()->hasOwnerPermissions()){
							$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You do not have permission to set {$rank->getName()}");
							return true;
						}
						$ranks[] = $rank;
					}else{
						$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Failed to set the player's ranks as some of the ranks are not valid");
						return true;
					}
				}
				if(($session = PlayerManager::getSession($player = PlayerManager::getPlayerByPrefix($args[0]))) !== null){
					$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully set {$player->getDisplayName()}'s ranks");
					$session->getRankInfo()->setRanks($ranks);
					return true;
				}
				$bypass = false;
				$name = $args[0];
				$query = "";
				$i = 1;
				foreach($ranks as $rank){
					if(RankHandler::isBypassAbleRank($rank = $rank->getName())){
						$bypass = true;
					}
					$query .= "rank$i = '$rank', ";
					$i++;
				}
				$rank = "";
				for($j = $i; $j <= 5; $j++){
					$query .= "rank$j = '$rank', ";
				}
				$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully set $name's ranks");
				if($bypass){
					PlayerManager::setBypassAble($name, true);
				}
				$lowerName = strtolower($name);
				$query = substr($query, 0, -2);
				DatabaseManager::getMainDatabase()->executeImplRaw([0 => "UPDATE PlayerRanks SET $query WHERE name = '$lowerName'"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);
				return true;
			}
			$sender->sendMessage(PracticeCore::PREFIX . $this->getUsage());
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_ADMIN;
	}
}