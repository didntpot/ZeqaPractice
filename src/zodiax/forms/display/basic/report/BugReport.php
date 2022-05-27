<?php

declare(strict_types=1);

namespace zodiax\forms\display\basic\report;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\XpCollectSound;
use zodiax\discord\DiscordUtil;
use zodiax\forms\types\CustomForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class BugReport{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data != null){
				$msg = "**Bug Report (" . PracticeCore::getRegionInfo() . ")**\n";
				$msg .= "Bug: " . ($bug = ["Gameplay", "Shop/Cosmetic", "Map", "Others"][$data[0]]) . "\n";
				$msg .= "Description: " . ($description = ($data[1] ?? "")) . "\n";
				$msg .= "Reporter: " . ($reporter = $player->getDisplayName());
				DiscordUtil::sendLogs($msg, true, 0xFF0000, PracticeCore::getLogoInfo());
				$msg = TextFormat::RED . TextFormat::BOLD . "REPORT" . TextFormat::RESET . TextFormat::RED . " $bug ($description)" . PracticeCore::COLOR . " was reported by " . TextFormat::RED . $reporter;
				$xpSound = new XpCollectSound();
				foreach(PlayerManager::getOnlineStaffs() as $p){
					$p->sendMessage($msg);
					$p->broadcastSound($xpSound, [$p]);
				}
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Your report has been sent");
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Bug " . TextFormat::WHITE . "Report"));
		$form->addDropdown("Type:", ["Gameplay", "Shop/Cosmetic", "Map", "Others"]);
		$form->addInput("Please provide the information:");
		$player->sendForm($form);
	}
}