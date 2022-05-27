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
use zodiax\forms\display\staff\punish\BanForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\ranks\RankHandler;
use function array_shift;
use function count;
use function date_format;
use function implode;
use function preg_match;
use function str_ends_with;
use function str_replace;
use function strlen;
use function strtolower;
use function substr;
use function trim;

class BanCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("ban", "Ban a player", "Usage: /ban <player> <duration> <reason>", []);
		parent::setPermission("practice.permission.ban");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender)){
			if($sender instanceof Player && count($args) === 0){
				BanForm::onDisplay($sender, ["name" => ""]);
				return true;
			}
			if($sender instanceof Player && count($args) === 1){
				BanForm::onDisplay($sender, ["name" => array_shift($args)]);
				return true;
			}
			if(count($args) >= 3){
				$name = array_shift($args);
				$duration = array_shift($args);
				$reason = trim(implode(" ", $args));
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
					$banTime = new DateTime("NOW");
					$banTime->modify("+$day days");
					$banTime->modify("+$hour hours");
					$banTime->modify("+$min mins");
					$expires = "$day day(s) $hour hour(s) $min min(s)";
					$duration = date_format($banTime, "Y-m-d-H-i");
				}
				$staff = $sender->getName();
				if(($player = PlayerManager::getPlayerByPrefix($name)) !== null){
					$name = $player->getName();
					$theReason = TextFormat::BOLD . TextFormat::RED . "Network Ban" . "\n\n" . TextFormat::RESET;
					$theReason .= TextFormat::RED . "Reason " . TextFormat::DARK_GRAY . "» " . TextFormat::GRAY . $reason . "\n";
					$theReason .= TextFormat::RED . "Duration " . TextFormat::DARK_GRAY . "» " . TextFormat::GRAY . $expires . "\n";
					$theReason .= TextFormat::GRAY . "Appeal at: " . TextFormat::RED . "discord.gg/zeqa";
					$player->kick($theReason);
				}
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
			$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . $this->getUsage());
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_MOD;
	}
}
