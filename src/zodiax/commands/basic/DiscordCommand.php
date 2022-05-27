<?php

declare(strict_types=1);

namespace zodiax\commands\basic;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\commands\PracticeCommand;
use zodiax\discord\DiscordUtil;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\ranks\RankHandler;
use function count;
use function implode;
use function str_contains;
use function trim;

class DiscordCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("discord", "Sync Discord roles", "Usage: /discord username#0001", []);
		parent::setPermission("practice.permission.discord");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player && ($session = PlayerManager::getSession($sender)) !== null){
			if(PracticeCore::isLobby()){
				$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You need to run this command in a region, Use the compass to navigate to one.");
				return true;
			}
			if(count($args) >= 1 && str_contains($id = trim(implode(" ", $args)), "#")){
				$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully sent the verification message. Please check your DMs. (Please make sure you have your DMs enabled, else the bot can not message you.)");
				DiscordUtil::sendSyncLogs("{$sender->getName()} {SPLIT} $id {SPLIT} " . implode(" ", $session->getRankInfo()->getRanks(true)));
				return true;
			}
			$sender->sendMessage(PracticeCore::PREFIX . $this->getUsage());
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_NONE;
	}
}
