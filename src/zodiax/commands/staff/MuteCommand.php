<?php

declare(strict_types=1);

namespace zodiax\commands\staff;

use DateTime;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlThread;
use zodiax\commands\PracticeCommand;
use zodiax\data\database\DatabaseManager;
use zodiax\discord\DiscordUtil;
use zodiax\forms\display\staff\punish\MuteForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\ranks\RankHandler;
use function array_shift;
use function count;
use function date_format;
use function preg_match;
use function str_ends_with;
use function str_replace;
use function strlen;
use function strtolower;
use function substr;

class MuteCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("mute", "Mute player", "Usage: /mute <player> <duration>", []);
		parent::setPermission("practice.permission.mute");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender)){
			if($sender instanceof Player && count($args) === 0){
				MuteForm::onDisplay($sender, ["name" => ""]);
				return true;
			}
			if($sender instanceof Player && count($args) === 1){
				MuteForm::onDisplay($sender, ["name" => array_shift($args)]);
				return true;
			}
			if(count($args) >= 2){
				$player = PlayerManager::getPlayerByPrefix($name = array_shift($args));
				$duration = array_shift($args);
				$matches = [];
				if(!preg_match("/^([0-9]+d)?([0-9]+h)?([0-9]+m)?$/", $duration, $matches)){
					$sender->sendMessage(PracticeCore::PREFIX . $this->getUsage());
					return true;
				}
				$day = 0;
				$hour = 0;
				$min = 0;
				foreach($matches as $index => $match){
					if($index !== 0 && strlen($match) !== 0){
						$n = substr($match, 0, -1);
						if(str_ends_with($match, "d")){
							$day = (int) $n;
						}elseif(str_ends_with($match, "h")){
							$hour = (int) $n;
						}elseif(str_ends_with($match, "m")){
							$min = (int) $n;
						}
					}
				}
				$expires = "Forever";
				$duration = "-1";
				if($day !== 0 || $hour !== 0 || $min !== 0){
					$expiresTime = new DateTime("NOW");
					$expiresTime->modify("+$day days");
					$expiresTime->modify("+$hour hours");
					$expiresTime->modify("+$min mins");
					$expires = "$day day(s) $hour hour(s) $min min(s)";
					$duration = date_format($expiresTime, "Y-m-d-H-i");
				}
				if(($session = PlayerManager::getSession($player)) !== null){
					$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Muted {$player->getDisplayName()}");
					$session->getDurationInfo()->setMuted($duration);
					DiscordUtil::sendBan("**Muted (" . PracticeCore::getRegionInfo() . ")**\nPlayer: {$player->getName()}\nDuration: $expires\nStaff: {$sender->getName()}", true, 0xFF0000, "http://api.zeqa.net/api/players/avatars/" . str_replace(" ", "%20", $player->getName()));
				}else{
					$lowerName = strtolower($name);
					$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Muted $name");
					DatabaseManager::getMainDatabase()->executeImplRaw([0 => "UPDATE PlayerDuration SET lastmuted = '$duration' WHERE name = '$lowerName'"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);
					DiscordUtil::sendBan("**Muted (" . PracticeCore::getRegionInfo() . ")**\nPlayer: $name\nDuration: $expires\nStaff: {$sender->getName()}", true, 0xFF0000, "http://api.zeqa.net/api/players/avatars/" . str_replace(" ", "%20", $name));
				}
				if($sender instanceof Player){
					PlayerManager::getSession($sender)?->getStaffStatsInfo()?->addMute();
				}
				return true;
			}
			$sender->sendMessage(PracticeCore::PREFIX . $this->getUsage());
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_HELPER;
	}
}