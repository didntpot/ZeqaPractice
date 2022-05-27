<?php

declare(strict_types=1);

namespace zodiax\commands\staff;

use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlThread;
use zodiax\commands\PracticeCommand;
use zodiax\data\database\DatabaseManager;
use zodiax\discord\DiscordUtil;
use zodiax\PracticeCore;
use zodiax\ranks\RankHandler;
use function count;
use function str_replace;
use function strtolower;

class UnbanCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("unban", "Unban a player", "Usage: /unban <player>", []);
		parent::setPermission("practice.permission.unban");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender)){
			if(count($args) < 1){
				$sender->sendMessage(PracticeCore::PREFIX . $this->getUsage());
				return true;
			}
			$lowerName = strtolower($name = $args[0]);
			$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Unbanned $name");
			DatabaseManager::getMainDatabase()->executeImplRaw([0 => "DELETE FROM BansData WHERE name = '$lowerName'"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);
			DiscordUtil::sendBan("**Unbanned (" . PracticeCore::getRegionInfo() . ")**\nPlayer: $name\nStaff: {$sender->getName()}", true, 0x00FF00, "http://api.zeqa.net/api/players/avatars/" . str_replace(" ", "%20", $name));
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_MOD;
	}
}
