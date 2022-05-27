<?php

declare(strict_types=1);

namespace zodiax\commands\staff;

use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlThread;
use zodiax\commands\PracticeCommand;
use zodiax\data\database\DatabaseManager;
use zodiax\discord\DiscordUtil;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\ranks\RankHandler;
use function array_shift;
use function count;
use function str_replace;
use function strtolower;

class UnmuteCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("unmute", "Unmute player", "Usage: /unmute <player>", []);
		parent::setPermission("practice.permission.unmute");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender)){
			if(count($args) < 1){
				$sender->sendMessage(PracticeCore::PREFIX . $this->getUsage());
				return true;
			}
			$player = PlayerManager::getPlayerByPrefix($name = array_shift($args));
			if(($session = PlayerManager::getSession($player)) !== null){
				$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Unmuted {$player->getDisplayName()}");
				$session->getDurationInfo()->setMuted("0");
				DiscordUtil::sendBan("**Unmuted (" . PracticeCore::getRegionInfo() . ")**\nPlayer: {$player->getName()}\nStaff: {$sender->getName()}", true, 0x00FF00, "http://api.zeqa.net/api/players/avatars/" . str_replace(" ", "%20", $player->getName()));
				return true;
			}
			$lowerName = strtolower($name);
			$duration = "0";
			$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Unmuted $name");
			DatabaseManager::getMainDatabase()->executeImplRaw([0 => "UPDATE PlayerDuration SET lastmuted = '$duration' WHERE name = '$lowerName'"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);
			DiscordUtil::sendBan("**Unmuted (" . PracticeCore::getRegionInfo() . ")**\nPlayer: $name\nStaff: {$sender->getName()}", true, 0x00FF00, "http://api.zeqa.net/api/players/avatars/" . str_replace(" ", "%20", $name));
			return true;
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_HELPER;
	}
}