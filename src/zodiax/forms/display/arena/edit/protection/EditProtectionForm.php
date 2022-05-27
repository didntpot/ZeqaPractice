<?php

declare(strict_types=1);

namespace zodiax\forms\display\arena\edit\protection;

use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function round;

class EditProtectionForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($arena = $args[0]) === null){
			return;
		}
		if(!isset($args[1]) || ($index = $args[1]) === null){
			return;
		}
		if(!isset($args[2]) || ($type = $args[2]) === null){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["arena"]) && isset($extraData["index"])){
				$arena = $extraData["arena"];
				$arena->setProtection(new Vector3((float) $data[1], (float) $data[2], (float) $data[3]), new Vector3((float) $data[4], (float) $data[5], (float) $data[6]), $extraData["index"]);
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully edited {$arena->getName()}'s protection");
			}
		});

		$pos = $player->getPosition();
		$protection = ["pos1" => $pos, "pos2" => $pos];
		if($type === "edit"){
			$temp = $arena->getProtections($index);
			if(!empty($temp)){
				$protection = $temp[0];
			}
		}

		$title = ($type === "edit" ? "Edit " : "Create ");
		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . $title . TextFormat::WHITE . "Protection"));
		$form->addLabel("Your position x: {$pos->getX()} y: {$pos->getY()} z: {$pos->getZ()}");
		$pos1X = (string) round($protection["pos1"]->getX());
		$pos1Y = (string) round($protection["pos1"]->getY());
		$pos1Z = (string) round($protection["pos1"]->getZ());
		$pos2X = (string) round($protection["pos2"]->getX());
		$pos2Y = (string) round($protection["pos2"]->getY());
		$pos2Z = (string) round($protection["pos2"]->getZ());
		$form->addInput("pos1X", $pos1X, $pos1X);
		$form->addInput("pos1Y", $pos1Y, $pos1Y);
		$form->addInput("pos1Z", $pos1Z, $pos1Z);
		$form->addInput("pos2X", $pos2X, $pos2X);
		$form->addInput("pos2Y", $pos2Y, $pos2Y);
		$form->addInput("pos2Z", $pos2Z, $pos2Z);
		$form->addExtraData("arena", $arena);
		$form->addExtraData("index", $index);
		$player->sendForm($form);
	}
}