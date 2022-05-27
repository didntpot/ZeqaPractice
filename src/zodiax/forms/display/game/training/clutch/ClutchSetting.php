<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\training\clutch;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\training\types\ClutchPractice;

class ClutchSetting{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($clutch = $args[0]) === null || !$clutch instanceof ClutchPractice){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInClutch() && $data !== null && isset($extraData["clutch"])){
				$clutch = $extraData["clutch"];
				if($clutch instanceof ClutchPractice){
					$clutch->setKnockbackInfo((float) $data[0], (float) $data[1], (int) $data[2]);
					$clutch->setHitStacks((int) $data[3]);
					$clutch->setHitDelaySeconds((int) $data[4]);
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Clutch" . TextFormat::WHITE . " Settings"));
		$kbInfo = $clutch->getKnockbackInfo();
		$form->addInput("Horizontal (X, Z) Knockback", "", (string) $kbInfo->getHorizontalKb());
		$form->addInput("Vertical (Y) Knockback", "", (string) $kbInfo->getVerticalKb());
		$form->addSlider("Attack Delay", 0, 20, 1, $kbInfo->getSpeed());
		$form->addSlider("Hit Stacks", 1, 10, 1, $clutch->getHitStacks());
		$form->addSlider("Hit Cooldown", 0, 5, 1, $clutch->getHitDelaySeconds());
		$form->addExtraData("clutch", $clutch);
		$player->sendForm($form);
	}
}