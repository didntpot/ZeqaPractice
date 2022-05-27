<?php

declare(strict_types=1);

namespace zodiax\forms\display\arena\edit\spawn;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\arena\types\BlockInArena;
use zodiax\arena\types\DuelArena;
use zodiax\arena\types\EventArena;
use zodiax\arena\types\FFAArena;
use zodiax\arena\types\TrainingArena;
use zodiax\forms\types\SimpleForm;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class ManageSpawnForm{

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
					EditSpawnForm::onDisplay($player, $arena, $data + 1, "edit");
				}elseif($type === "delete"){
					if($arena instanceof FFAArena){
						$arena->removeSpawn($data + 1);
						$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Successfully deleted {$arena->getName()}'s spawnpoint");
					}
				}
			}
		});

		$title = ($type === "manage" ? "Manage " : "Delete ");
		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . $title . TextFormat::WHITE . "Spawnpoint"));
		$form->setContent("Choose whether to $type the {$arena->getName()}'s spawns");
		if($arena instanceof FFAArena){
			foreach($arena->getSpawns() as $index => $spawn){
				$form->addButton("#$index\nx: {$spawn->getX()} y: {$spawn->getY()} z: {$spawn->getZ()}");
			}
		}elseif($arena instanceof DuelArena || $arena instanceof TrainingArena){
			$p1spawn = $arena->getP1Spawn();
			$p2spawn = $arena->getP2Spawn();
			$form->addButton("#1\nx: {$p1spawn->getX()} y: {$p1spawn->getY()} z: {$p1spawn->getZ()}");
			$form->addButton("#2\nx: {$p2spawn->getX()} y: {$p2spawn->getY()} z: {$p2spawn->getZ()}");
		}elseif($arena instanceof EventArena){
			$p1spawn = $arena->getP1Spawn();
			$p2spawn = $arena->getP2Spawn();
			$specspawn = $arena->getSpecSpawn();
			$form->addButton("#1\nx: {$p1spawn->getX()} y: {$p1spawn->getY()} z: {$p1spawn->getZ()}");
			$form->addButton("#2\nx: {$p2spawn->getX()} y: {$p2spawn->getY()} z: {$p2spawn->getZ()}");
			$form->addButton("#spec\nx: {$specspawn->getX()} y: {$specspawn->getY()} z: {$specspawn->getZ()}");
		}elseif($arena instanceof BlockInArena){
			$p1spawn = $arena->getP1Spawn();
			$p2spawn = $arena->getP2Spawn();
			$corespawn = $arena->getCoreSpawn();
			$form->addButton("#1\nx: {$p1spawn->getX()} y: {$p1spawn->getY()} z: {$p1spawn->getZ()}");
			$form->addButton("#2\nx: {$p2spawn->getX()} y: {$p2spawn->getY()} z: {$p2spawn->getZ()}");
			$form->addButton("#Core\nx: {$corespawn->getX()} y: {$corespawn->getY()} z: {$corespawn->getZ()}");
		}
		$form->addExtraData("arena", $arena);
		$form->addExtraData("type", $type);
		$player->sendForm($form);
	}
}