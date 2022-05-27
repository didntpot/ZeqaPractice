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
use zodiax\forms\display\staff\punish\KickForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\ranks\RankHandler;
use function array_shift;
use function count;
use function date_format;
use function implode;
use function str_replace;
use function strtolower;
use function trim;

class KickCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("kick", "Kick a player", "Usage: /kick <player> <reason>", []);
		parent::setPermission("practice.permission.kick");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender)){
			if($sender instanceof Player && count($args) === 0){
				KickForm::onDisplay($sender, ["name" => ""]);
				return true;
			}
			if($sender instanceof Player && count($args) === 1){
				KickForm::onDisplay($sender, ["name" => array_shift($args)]);
				return true;
			}
			if(count($args) >= 2){
				$name = array_shift($args);
				$reason = trim(implode(" ", $args));
				if(($session = PlayerManager::getSession($player = PlayerManager::getPlayerByPrefix($name))) !== null){
					$name = $player->getName();
					$staff = $sender->getName();
					$durationInfo = $session->getDurationInfo();
					$durationInfo->addWarnedCount();
					if($durationInfo->getWarnedCount() >= 5){
						$durationInfo->resetWarnedCount();
						$banTime = new DateTime("NOW");
						$banTime->modify("+30 days");
						$expires = "30 day(s) 0 hour(s) 0 min(s)";
						$duration = date_format($banTime, "Y-m-d-H-i");
						$reason = "Auto Ban (5 Warned)";
						$theReason = TextFormat::BOLD . TextFormat::RED . "Network Ban" . "\n\n" . TextFormat::RESET;
						$theReason .= TextFormat::RED . "Reason " . TextFormat::DARK_GRAY . "» " . TextFormat::GRAY . $reason . "\n";
						$theReason .= TextFormat::RED . "Duration " . TextFormat::DARK_GRAY . "» " . TextFormat::GRAY . $expires . "\n";
						$theReason .= TextFormat::GRAY . "Appeal at: " . TextFormat::RED . "discord.gg/zeqa";
						$player->kick($theReason);
						$lowerName = strtolower($name);
						$announce = TextFormat::GRAY . "\n";
						$announce .= TextFormat::RED . "$staff banned $name\n";
						$announce .= TextFormat::RED . "Reason: " . TextFormat::WHITE . "$reason\n";
						$announce .= TextFormat::GRAY . "";
						foreach(PlayerManager::getOnlinePlayers() as $onlinePlayer){
							$onlinePlayer->sendMessage($announce);
						}
						if($sender instanceof Player){
							if($sender->isOnline()){
								$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Banned $name");
								PlayerManager::getSession($sender)->getStaffStatsInfo()?->addBan();
							}
						}else{
							$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Banned $name");
						}
						DatabaseManager::getMainDatabase()->executeImplRaw([0 => "INSERT INTO BansData (name, reason, duration, staff) VALUES ('$lowerName', '$reason', '$duration', '$staff') ON DUPLICATE KEY UPDATE reason = '$reason', duration = '$duration', staff = '$staff'"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);
						DiscordUtil::sendBan("**Banned (" . PracticeCore::getRegionInfo() . ")**\nPlayer: $name\nReason: $reason\nDuration: $expires\nStaff: $staff", true, 0xFF0000, "http://api.zeqa.net/api/players/avatars/" . str_replace(" ", "%20", $name));
						return true;
					}
					$theReason = TextFormat::BOLD . TextFormat::RED . "Network Kick" . "\n\n" . TextFormat::RESET;
					$theReason .= TextFormat::RED . "Reason " . TextFormat::DARK_GRAY . "» " . TextFormat::GRAY . $reason . "\n";
					$theReason .= TextFormat::RED . "Kicked by " . TextFormat::DARK_GRAY . "» " . TextFormat::GRAY . $staff;
					$player->kick($theReason);
					$announce = TextFormat::GRAY . "\n";
					$announce .= TextFormat::RED . "$staff kicked $name\n";
					$announce .= TextFormat::RED . "Reason: " . TextFormat::WHITE . "$reason\n";
					$announce .= TextFormat::GRAY . "";
					foreach(PlayerManager::getOnlinePlayers() as $onlinePlayer){
						$onlinePlayer->sendMessage($announce);
					}
					if($sender instanceof Player){
						if($sender->isOnline()){
							$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Kicked $name");
							PlayerManager::getSession($sender)->getStaffStatsInfo()?->addKick();
						}
					}else{
						$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Kicked $name");
					}
					DiscordUtil::sendBan("**Kicked (" . PracticeCore::getRegionInfo() . ")**\nPlayer: $name\nReason: $reason\nStaff: {$sender->getName()}", true, 0xFF0000, "http://api.zeqa.net/api/players/avatars/" . str_replace(" ", "%20", $name));
					return true;
				}
				$sender->sendMessage(PracticeCore::PREFIX . "Can not find player $name");
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
