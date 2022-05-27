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

class MuteForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null){
				$name = (string) $data[0] ?? "";
				if($name === ""){
					return;
				}
				$day = (int) $data[2];
				$hour = (int) $data[3];
				$min = (int) $data[4];
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
				if(($session = PlayerManager::getSession($target = PlayerManager::getPlayerByPrefix($name))) !== null){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Muted {$target->getDisplayName()}");
					$session->getDurationInfo()->setMuted($duration);
					DiscordUtil::sendBan("**Muted (" . PracticeCore::getRegionInfo() . ")**\nPlayer: {$target->getName()}\nDuration: $expires\nStaff: {$player->getName()}", true, 0xFF0000, 'http://api.zeqa.net/api/players/avatars/' . str_replace(' ', '%20', $target->getName()));
				}else{
					$lowerName = strtolower($name);
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Muted $name");
					DatabaseManager::getMainDatabase()->executeImplRaw([0 => "UPDATE PlayerDuration SET lastmuted = '$duration' WHERE name = '$lowerName'"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);
					DiscordUtil::sendBan("**Muted (" . PracticeCore::getRegionInfo() . ")**\nPlayer: $name\nDuration: $expires\nStaff: {$player->getName()}", true, 0xFF0000, 'http://api.zeqa.net/api/players/avatars/' . str_replace(' ', '%20', $name));
				}
				PlayerManager::getSession($player)?->getStaffStatsInfo()?->addMute();
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Mute " . TextFormat::WHITE . "Player"));
		$form->addInput("Enter name: ", "", $args[0]["name"]);
		$form->addLabel("Leave all with 0 for forever mute");
		$form->addSlider("Day/s", 0, 30, 1);
		$form->addSlider("Hour/s", 0, 24, 1);
		$form->addSlider("Minute/s", 0, 60, 5);
		$player->sendForm($form);
	}
}