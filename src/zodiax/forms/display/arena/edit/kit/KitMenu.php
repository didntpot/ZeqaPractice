<?php

declare(strict_types=1);

namespace zodiax\forms\display\arena\edit\kit;

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

class KitMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($arena = $args[0]) === null){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null){
				$arena = $extraData["arena"];
				switch((int) $data){
					case 0:
					case 1:
						if($arena instanceof FFAArena || $arena instanceof EventArena){
							ManageKitForm::onDisplay($player, $arena, "manage");
						}elseif($arena instanceof DuelArena || $arena instanceof TrainingArena){
							ManageKitForm::onDisplay($player, $arena, "add");
						}elseif($arena instanceof BlockInArena){
							ManageKitForm::onDisplay($player, $arena, "attacker");
						}
						break;
					case 2:
						if($arena instanceof FFAArena || $arena instanceof EventArena){
							ManageKitForm::onDisplay($player, $arena, "manage");
						}elseif($arena instanceof DuelArena || $arena instanceof TrainingArena){
							ManageKitForm::onDisplay($player, $arena, "delete");
						}elseif($arena instanceof BlockInArena){
							ManageKitForm::onDisplay($player, $arena, "defender");
						}
						break;
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Kit " . TextFormat::WHITE . "Menu"));
		$form->setContent("Choose whether to edit the {$arena->getName()}'s kits");
		if($arena instanceof BlockInArena){
			$form->addButton("Attacker Kit", 0, "textures/ui/strength_effect.png");
			$form->addButton("Defender Kit", 0, "textures/ui/resistance_effect.png");
		}else{
			$form->addButton("Add Kit", 0, "textures/ui/confirm.png");
			$form->addButton("Manage Kit", 0, "textures/ui/debug_glyph_color.png");
			$form->addButton("Delete Kit", 0, "textures/ui/realms_red_x.png");
		}
		$form->addExtraData("arena", $arena);
		$player->sendForm($form);
	}
}