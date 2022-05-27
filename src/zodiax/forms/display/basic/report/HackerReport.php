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
use function count;
use function str_replace;

class HackerReport{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data != null && isset($extraData["players"])){
				$msg = "**Hacker Report (" . PracticeCore::getRegionInfo() . ")**\n";
				$msg .= "Reported: " . ($reported = $extraData["players"][$data[0]]) . "\n";
				$msg .= "Description: " . ($description = ($data[1] ?? "")) . "\n";
				$msg .= "Reporter: " . ($reporter = $player->getDisplayName());
				DiscordUtil::sendLogs($msg, true, 0xFF0000, "http://api.zeqa.net/api/players/avatars/" . str_replace(" ", "%20", $reported));
				$msg = TextFormat::RED . TextFormat::BOLD . "REPORT" . TextFormat::RESET . TextFormat::RED . " $reported" . PracticeCore::COLOR . " was reported by " . TextFormat::RED . $reporter . PracticeCore::COLOR . " for " . TextFormat::RED . $description;
				$xpSound = new XpCollectSound();
				foreach(PlayerManager::getOnlineStaffs() as $p){
					$p->sendMessage($msg);
					$p->broadcastSound($xpSound, [$p]);
				}
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Your report has been sent");
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Hacker " . TextFormat::WHITE . "Report"));
		if(count($dropdownArr = PlayerManager::getListDisplayNames($player->getDisplayName())) > 0){
			$form->addDropdown("Hacker:", $dropdownArr);
			$form->addInput("Please provide the information:");
			$form->addExtraData("players", $dropdownArr);
		}else{
			$form->addLabel(TextFormat::RED . "Nobody online");
		}
		$player->sendForm($form);
	}
}