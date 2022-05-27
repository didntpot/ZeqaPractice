<?php

declare(strict_types=1);

namespace zodiax\forms\display\arena\edit;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\arena\types\DuelArena;
use zodiax\arena\types\FFAArena;
use zodiax\forms\display\arena\edit\kit\KitMenu;
use zodiax\forms\display\arena\edit\protection\ProtectionMenu;
use zodiax\forms\display\arena\edit\spawn\SpawnPointMenu;
use zodiax\forms\types\SimpleForm;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class EditArenaMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($arena = $args[0]) === null){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null){
				$arena = $extraData["arena"];
				switch($data){
					case 0:
						KitMenu::onDisplay($player, $arena);
						break;
					case 1:
						SpawnPointMenu::onDisplay($player, $arena);
						break;
					case 2:
						if($arena instanceof FFAArena){
							EditArenaMisc::onDisplay($player, $arena);
						}elseif($arena instanceof DuelArena){
							ProtectionMenu::onDisplay($player, $arena);
						}
						break;
					case 3:
						EditArenaMisc::onDisplay($player, $arena);
						break;
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Edit " . TextFormat::WHITE . "Arena"));
		$form->setContent("Choose whether to edit the {$arena->getName()}'s kits or spawns");
		$form->addButton("Edit Kit", 0, "textures/ui/icon_recipe_equipment.png");
		$form->addButton("Edit Spawnpoint", 0, "textures/items/bed_red.png");
		if($arena instanceof FFAArena){
			$form->addButton("Edit Misc", 0, "textures/ui/settings_glyph_color_2x.png");
		}elseif($arena instanceof DuelArena){
			$form->addButton("Edit Protection", 0, "textures/ui/icon_recipe_construction.png");
			$form->addButton("Edit Misc", 0, "textures/ui/settings_glyph_color_2x.png");
		}
		$form->addExtraData("arena", $arena);
		$player->sendForm($form);
	}
}