<?php

declare(strict_types=1);

namespace zodiax\forms\display\staff\punish;

use DateTime;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlThread;
use zodiax\data\database\DatabaseManager;
use zodiax\discord\DiscordUtil;
use zodiax\forms\types\CustomForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function date_format;
use function str_replace;
use function strtolower;

class KickForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null){
				$name = (string) $data[0] ?? "";
				$reason = (string) $data[1] ?? "";
				if($name === ""){
					return;
				}
				if($reason === ""){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You can not kick player for no reason");
					return;
				}
				if(($session = PlayerManager::getSession($target = PlayerManager::getPlayerByPrefix($name))) !== null){
					$name = $target->getName();
					$staff = $player->getName();
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
						$target->kick($theReason);
						$lowerName = strtolower($name);
						if($data[2]){
							$announce = TextFormat::GRAY . "\n" . TextFormat::RED . "$staff banned $name\n" . TextFormat::RED . "Reason: " . TextFormat::WHITE . "$reason\n" . TextFormat::GRAY . "";
							foreach(PlayerManager::getOnlinePlayers() as $onlinePlayer){
								$onlinePlayer->sendMessage($announce);
							}
						}elseif($player->isOnline()){
							$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Banned $name");
						}
						PlayerManager::getSession($player)?->getStaffStatsInfo()?->addBan();
						DatabaseManager::getMainDatabase()->executeImplRaw([0 => "INSERT INTO BansData (name, reason, duration, staff) VALUES ('$lowerName', '$reason', '$duration', '$staff') ON DUPLICATE KEY UPDATE reason = '$reason', duration = '$duration', staff = '$staff'"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);
						DiscordUtil::sendBan("**Banned (" . PracticeCore::getRegionInfo() . ")**\nPlayer: $name\nReason: $reason\nDuration: $expires\nStaff: $staff", true, 0xFF0000, "http://api.zeqa.net/api/players/avatars/" . str_replace(" ", "%20", $name));
						return;
					}
					$theReason = TextFormat::BOLD . TextFormat::RED . "Network Kick" . "\n\n" . TextFormat::RESET;
					$theReason .= TextFormat::RED . "Reason " . TextFormat::DARK_GRAY . "» " . TextFormat::GRAY . $reason . "\n";
					$theReason .= TextFormat::RED . "Kicked by " . TextFormat::DARK_GRAY . "» " . TextFormat::GRAY . $staff;
					$target->kick($theReason);
					if($data[2]){
						$announce = TextFormat::GRAY . "\n" . TextFormat::RED . "$staff kicked $name\n" . TextFormat::RED . "Reason: " . TextFormat::WHITE . "$reason\n" . TextFormat::GRAY . "";
						foreach(PlayerManager::getOnlinePlayers() as $onlinePlayer){
							$onlinePlayer->sendMessage($announce);
						}
					}elseif($player->isOnline()){
						$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Kicked $name");
					}
					PlayerManager::getSession($player)?->getStaffStatsInfo()?->addKick();
					DiscordUtil::sendBan("**Kicked (" . PracticeCore::getRegionInfo() . ")**\nPlayer: $name\nReason: $reason\nStaff: $staff", true, 0xFF0000, "http://api.zeqa.net/api/players/avatars/" . str_replace(" ", "%20", $name));
					return;
				}
				$player->sendMessage(PracticeCore::PREFIX . "Can not find player $name");
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Kick " . TextFormat::WHITE . "Player"));
		$form->addInput("Enter name: ", "", $args[0]["name"]);
		$form->addInput("Reason: ");
		$form->addToggle("Kick Announcement", true);
		$player->sendForm($form);
	}
}