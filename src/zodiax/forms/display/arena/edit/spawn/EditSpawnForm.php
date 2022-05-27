<?php

declare(strict_types=1);

namespace zodiax\forms\display\arena\edit\spawn;

use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\arena\types\BlockInArena;
use zodiax\arena\types\DuelArena;
use zodiax\arena\types\EventArena;
use zodiax\arena\types\FFAArena;
use zodiax\arena\types\TrainingArena;
use zodiax\forms\types\CustomForm;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function round;

class EditSpawnForm{

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
				$edit = false;
				$arena = $extraData["arena"];
				$index = $extraData["index"];
				$vec3 = new Vector3((float) $data[1], (float) $data[2], (float) $data[3]);
				if($arena instanceof FFAArena){
					$arena->setSpawn($vec3, $index);
					$edit = true;
				}elseif($arena instanceof DuelArena || $arena instanceof TrainingArena){
					if($index === 1){
						$arena->setP1Spawn($vec3);
						$edit = true;
					}elseif($index === 2){
						$arena->setP2Spawn($vec3);
						$edit = true;
					}
				}elseif($arena instanceof EventArena){
					if($index === 1){
						$arena->setP1Spawn($vec3);
						$edit = true;
					}elseif($index === 2){
						$arena->setP2Spawn($vec3);
						$edit = true;
					}elseif($index === 3){
						$arena->setSpecSpawn($vec3);
						$edit = true;
					}
				}elseif($arena instanceof BlockInArena){
					if($index === 1){
						$arena->setP1Spawn($vec3);
						$edit = true;
					}elseif($index === 2){
						$arena->setP2Spawn($vec3);
						$edit = true;
					}elseif($index === 3){
						$arena->setCoreSpawn($vec3);
						$edit = true;
					}
				}
				if($edit){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully edited {$arena->getName()}'s spawnpoint");
				}
			}
		});

		$spawn = $pos = $player->getPosition();
		if($type === "edit"){
			if($arena instanceof FFAArena){
				$temp = $arena->getSpawns($index);
				if(!empty($temp)){
					$spawn = $temp[0];
				}
			}elseif($arena instanceof DuelArena || $arena instanceof TrainingArena){
				if($index === 1){
					$spawn = $arena->getP1Spawn();
				}elseif($index === 2){
					$spawn = $arena->getP2Spawn();
				}
			}elseif($arena instanceof EventArena){
				if($index === 1){
					$spawn = $arena->getP1Spawn();
				}elseif($index === 2){
					$spawn = $arena->getP2Spawn();
				}elseif($index === 3){
					$spawn = $arena->getSpecSpawn();
				}
			}elseif($arena instanceof BlockInArena){
				if($index === 1){
					$spawn = $arena->getP1Spawn();
				}elseif($index === 2){
					$spawn = $arena->getP2Spawn();
				}elseif($index === 3){
					$spawn = $arena->getCoreSpawn();
				}
			}
		}

		$title = ($type === "edit" ? "Edit " : "Create ");
		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . $title . TextFormat::WHITE . "Spawnpoint"));
		$form->addLabel("Your position x: {$pos->getX()} y: {$pos->getY()} z: {$pos->getZ()}");
		$x = (string) round($spawn->getX());
		$y = (string) round($spawn->getY());
		$z = (string) round($spawn->getZ());
		$form->addInput("X", $x, $x);
		$form->addInput("Y", $y, $y);
		$form->addInput("Z", $z, $z);
		$form->addExtraData("arena", $arena);
		$form->addExtraData("index", $index);
		$player->sendForm($form);
	}
}