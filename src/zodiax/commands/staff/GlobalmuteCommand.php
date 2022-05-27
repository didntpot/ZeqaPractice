<?php

declare(strict_types=1);

namespace zodiax\commands\staff;

use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use zodiax\commands\PracticeCommand;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\ranks\RankHandler;
use function array_shift;
use function count;
use function implode;
use function strtolower;
use function trim;

class GlobalmuteCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("globalmute", "Global mute", "Usage: /globalmute <on:off:add:remove>", ["gmute"]);
		parent::setPermission("practice.permission.globalmute");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender)){
			if(count($args) >= 1){
				$arg = strtolower(array_shift($args));
				switch($arg){
					case "on":
						if(!PlayerManager::isGlobalMute()){
							PlayerManager::setGlobalMute(true);
							$msg = PracticeCore::PREFIX . TextFormat::RED . "Global mute enabled";
							foreach(PlayerManager::getOnlinePlayers() as $player){
								$player->sendMessage($msg);
							}
						}
						return true;
					case "off":
						if(PlayerManager::isGlobalMute()){
							PlayerManager::setGlobalMute(false);
							$msg = PracticeCore::PREFIX . TextFormat::GREEN . "Global mute disabled";
							foreach(PlayerManager::getOnlinePlayers() as $player){
								$player->sendMessage($msg);
							}
						}
						return true;
					case "add":
						if(count($args) >= 1){
							if(($player = PlayerManager::getPlayerByPrefix($name = trim(implode(" ", $args)))) !== null){
								PlayerManager::setGlobalMuteBypassAble($player->getName(), true);
								$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "Successfully added " . TextFormat::GREEN . $player->getDisplayName() . TextFormat::GRAY . " to global mute bypass");
								return true;
							}
							$sender->sendMessage(PracticeCore::PREFIX . "Can not find player $name");
							return true;
						}
						$sender->sendMessage(PracticeCore::PREFIX . "Usage: /globalmute add <player>");
						return true;
					case "remove":
						if(count($args) >= 1){
							if(($player = PlayerManager::getPlayerByPrefix($name = trim(implode(" ", $args)))) !== null){
								PlayerManager::setGlobalMuteBypassAble($player->getName(), false);
								$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "Successfully removed " . TextFormat::RED . $player->getDisplayName() . TextFormat::GRAY . " from global mute bypass");
								return true;
							}
							$sender->sendMessage(PracticeCore::PREFIX . "Can not find player $name");
							return true;
						}
						$sender->sendMessage(PracticeCore::PREFIX . "Usage: /globalmute remove <player>");
						return true;
				}
			}
			$sender->sendMessage(PracticeCore::PREFIX . $this->getUsage());
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_ADMIN;
	}
}
