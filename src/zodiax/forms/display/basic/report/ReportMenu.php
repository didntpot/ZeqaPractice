<?php

declare(strict_types=1);

namespace zodiax\forms\display\basic\report;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class ReportMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null){
				switch($data){
					case 0:
						HackerReport::onDisplay($player);
						break;
					case 1:
						BugReport::onDisplay($player);
						break;
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Report " . TextFormat::WHITE . "Menu"));
		$form->setContent("");
		$form->addButton(PracticeCore::COLOR . "Hacker " . TextFormat::WHITE . "Report");
		$form->addButton(PracticeCore::COLOR . "Bug " . TextFormat::WHITE . "Report");
		$player->sendForm($form);
	}
}