<?php

declare(strict_types=1);

namespace zodiax\forms\display\arena\edit\protection;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class ManageProtectionForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($arena = $args[0]) === null){
			return;
		}
		if(!isset($args[1]) || ($type = $args[1]) === null){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null){
				$arena = $extraData["arena"];
				$type = $extraData["type"];
				if($type === "manage"){
					EditProtectionForm::onDisplay($player, $arena, $data + 1, "edit");
				}elseif($type === "delete"){
					$arena->removeProtection($data + 1);
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Successfully deleted {$arena->getName()}'s protection");
				}
			}
		});

		$title = ($type === "manage" ? "Manage " : "Delete ");
		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . $title . TextFormat::WHITE . "Protection"));
		$form->setContent("Choose whether to $type the {$arena->getName()}'s protections");
		foreach($arena->getProtections() as $index => $protection){
			$pos1 = $protection["pos1"];
			$pos2 = $protection["pos2"];
			$form->addButton("#$index  pos1X: {$pos1->getX()} pos1Y: {$pos1->getY()} pos1Z: {$pos1->getZ()}\npos2X: {$pos2->getX()} pos2Y: {$pos2->getY()} pos2Z: {$pos2->getZ()}");
		}
		$form->addExtraData("arena", $arena);
		$form->addExtraData("type", $type);
		$player->sendForm($form);
	}
}