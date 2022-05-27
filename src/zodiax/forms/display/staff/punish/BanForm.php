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

class BanForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null){
				$name = (string) $data[0] ?? "";
				$reason = (string) $data[1] ?? "";
				if($name === ""){
					return;
				}
				if($reason === ""){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You can not ban player for no reason");
					return;
				}
				$day = (int) $data[3];
				$hour = (int) $data[4];
				$min = (int) $data[5];
				$banTime = null;
				if($day !== 0 || $hour !== 0 || $min !== 0){
					$banTime = new DateTime("NOW");
					$banTime->modify("+$day days");
					$banTime->modify("+$hour hours");
					$banTime->modify("+$min mins");
				}
				$banTime === null ? $expires = "Forever" : $expires = "$day day(s) $hour hour(s) $min min(s)";
				$theReason = TextFormat::BOLD . TextFormat::RED . "Network Ban" . "\n\n" . TextFormat::RESET;
				$theReason .= TextFormat::RED . "Reason " . TextFormat::DARK_GRAY . "» " . TextFormat::GRAY . $reason . "\n";
				$theReason .= TextFormat::RED . "Duration " . TextFormat::DARK_GRAY . "» " . TextFormat::GRAY . $expires . "\n";
				$theReason .= TextFormat::GRAY . "Appeal at: " . TextFormat::RED . "discord.gg/zeqa";
				$staff = $player->getName();
				$target = PlayerManager::getPlayerByPrefix($name);
				if($target instanceof Player){
					$name = $target->getName();
					$target->kick($theReason);
				}
				$lowername = strtolower($name);
				$duration = ($banTime === null) ? "-1" : date_format($banTime, "Y-m-d-H-i");
				DatabaseManager::getMainDatabase()->executeImplRaw([0 => "INSERT INTO BansData (name, reason, duration, staff) VALUES ('$lowername', '$reason', '$duration', '$staff') ON DUPLICATE KEY UPDATE reason = '$reason', duration = '$duration', staff = '$staff'"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);
				if($data[6]){
					$announce = TextFormat::GRAY . "\n" . TextFormat::RED . "$staff banned $name\n" . "Reason: " . TextFormat::WHITE . $reason . TextFormat::GRAY . "\n";
					foreach(PlayerManager::getOnlinePlayers() as $online){
						$online->sendMessage($announce);
					}
				}elseif($player->isOnline()){
					$player->sendMessage(TextFormat::RED . "Banned $name");
				}
				PlayerManager::getSession($player)?->getStaffStatsInfo()?->addBan();
				DiscordUtil::sendBan("**Banned (" . PracticeCore::getRegionInfo() . ")**\nPlayer: $name\nReason: $reason\nDuration: $expires\nStaff: $staff", true, 0xFF0000, "http://api.zeqa.net/api/players/avatars/" . str_replace(" ", "%20", $name));
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Ban " . TextFormat::WHITE . "Player"));
		$form->addInput("Enter name: ", "", $args[0]["name"]);
		$form->addInput("Reason: ");
		$form->addLabel("Leave all with 0 for forever ban");
		$form->addSlider("Day/s", 0, 30, 1);
		$form->addSlider("Hour/s", 0, 24, 1);
		$form->addSlider("Minute/s", 0, 60, 5);
		$form->addToggle("Ban Announcement", true);
		$player->sendForm($form);
	}
}