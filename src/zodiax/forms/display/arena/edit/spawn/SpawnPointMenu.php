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

class SpawnPointMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($arena = $args[0]) === null){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null){
				$arena = $extraData["arena"];
				switch((int) $data){
					case 0:
						if($arena instanceof FFAArena){
							EditSpawnForm::onDisplay($player, $arena, 0, "create");
						}elseif($arena instanceof DuelArena || $arena instanceof EventArena || $arena instanceof TrainingArena || $arena instanceof BlockInArena){
							ManageSpawnForm::onDisplay($player, $arena, "manage");
						}
						break;
					case 1:
						ManageSpawnForm::onDisplay($player, $arena, "manage");
						break;
					case 2:
						if($arena instanceof FFAArena){
							ManageSpawnForm::onDisplay($player, $arena, "delete");
						}elseif($arena instanceof DuelArena || $arena instanceof EventArena || $arena instanceof TrainingArena || $arena instanceof BlockInArena){
							ManageSpawnForm::onDisplay($player, $arena, "manage");
						}
						break;
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Spawnpoint " . TextFormat::WHITE . "Menu"));
		$form->setContent("Choose whether to edit the {$arena->getName()}'s spawns");
		$form->addButton("Create Spawnpoint", 0, "textures/ui/confirm.png");
		$form->addButton("Manage Spawnpoint", 0, "textures/ui/debug_glyph_color.png");
		$form->addButton("Delete Spawnpoint", 0, "textures/ui/realms_red_x.png");
		$form->addExtraData("arena", $arena);
		$player->sendForm($form);
	}
}