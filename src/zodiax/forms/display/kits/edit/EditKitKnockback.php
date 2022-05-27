<?php

declare(strict_types=1);

namespace zodiax\forms\display\kits\edit;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\kits\DefaultKit;
use zodiax\kits\KitsManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class EditKitKnockback{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($kit = $args[0]) === null || !$kit instanceof DefaultKit){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["kit"])){
				$kit = $extraData["kit"];
				$knockbackInfo = $kit->getKnockbackInfo();
				$knockbackInfo->setHorizontalKb((float) $data[1]);
				$knockbackInfo->setVerticalKb((float) $data[2]);
				$knockbackInfo->setMaxHeight((float) $data[3]);
				$knockbackInfo->setCanRevert((bool) $data[4]);
				$knockbackInfo->setSpeed((int) $data[5]);
				KitsManager::saveKit($kit);
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully edited {$kit->getName()}'s knockback " . TextFormat::GRAY . $kit->getName());
			}
		});

		$knockbackInfo = $kit->getKnockbackInfo();
		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Edit " . TextFormat::WHITE . "Knockback"));
		$form->addLabel("Edit the knockback information of the {$kit->getName()} kit");
		$xz = (string) $knockbackInfo->getHorizontalKb();
		$y = (string) $knockbackInfo->getVerticalKb();
		$maxHeight = (string) $knockbackInfo->getMaxHeight();
		$speed = (string) $knockbackInfo->getSpeed();
		$form->addInput("Horizontal (X, Z) Knockback", $xz, $xz);
		$form->addInput("Vertical (Y) Knockback", $y, $y);
		$form->addInput("Max Height", $maxHeight, $maxHeight);
		$form->addToggle("Can Revert (Revert knockback once it hit height limit)", $knockbackInfo->canRevert());
		$form->addInput("Attack Delay", $speed, $speed);
		$form->addExtraData("kit", $kit);
		$player->sendForm($form);
	}
}