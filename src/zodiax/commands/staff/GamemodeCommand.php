<?php

declare(strict_types=1);

namespace zodiax\commands\staff;

use pocketmine\command\CommandSender;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\commands\PracticeCommand;
use zodiax\PracticeCore;
use zodiax\ranks\RankHandler;
use function count;

class GamemodeCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("gamemode", "Change game mode", "Usage: /gamemode <mode>", ["gm"]);
		parent::setPermission("practice.permission.gamemode");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player){
			if(count($args) === 1){
				if(($gameMode = GameMode::fromString($args[0])) instanceof GameMode){
					$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Set own game mode to {$gameMode->getEnglishName()}");
					$sender->setGamemode($gameMode);
					return true;
				}
				$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Unknown game mode");
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
