<?php

declare(strict_types=1);

namespace zodiax\commands\staff;

use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use zodiax\commands\PracticeCommand;
use zodiax\player\misc\queue\QueueHandler;
use zodiax\PracticeCore;
use zodiax\ranks\RankHandler;
use function count;
use function implode;
use function stripos;
use function strlen;
use function strtolower;
use function trim;

class FindPlayerCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("findplayer", "Find the server which target player is on", "Usage: /findplayer <player>", ["find"]);
		parent::setPermission("practice.permission.findplayer");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender)){
			if(count($args) >= 1){
				$lower = strtolower($name = trim(implode(" ", $args)));
				$found = null;
				$onserver = null;
				$delta = PHP_INT_MAX;
				$queryResults = QueueHandler::getQueryResults();
				foreach($queryResults as $servers){
					foreach($servers as $server => $data){
						if($data["isonline"]){
							foreach($data["list"] as $player){
								if(stripos((string) $player, $lower) === 0){
									$curDelta = strlen($player) - strlen($lower);
									if($curDelta < $delta){
										$found = $player;
										$onserver = $server;
										$delta = $curDelta;
									}
									if($curDelta === 0){
										break;
									}
								}
							}
						}
					}
				}
				if($found !== null && $onserver !== null){
					$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "Found " . TextFormat::GREEN . $found . TextFormat::GRAY . " is on $onserver");
					return true;
				}
				$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . $name . TextFormat::GRAY . " is not online on any server");
				return true;
			}
			$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . $this->getUsage());
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_HELPER;
	}
}
