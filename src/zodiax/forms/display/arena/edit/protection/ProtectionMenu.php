<?php

declare(strict_types=1);

namespace zodiax\forms\display\arena\edit\protection;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class ProtectionMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($arena = $args[0]) === null){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null){
				$arena = $extraData["arena"];
				switch((int) $data){
					case 0:
						EditProtectionForm::onDisplay($player, $arena, 0, "create");
						break;
					case 1:
						ManageProtectionForm::onDisplay($player, $arena, "manage");
						break;
					case 2:
						ManageProtectionForm::onDisplay($player, $arena, "delete");
						break;
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Protection " . TextFormat::WHITE . "Menu"));
		$form->setContent("Choose whether to edit the {$arena->getName()}'s protections");
		$form->addButton("Create Protection", 0, "textures/ui/confirm.png");
		$form->addButton("Manage Protection", 0, "textures/ui/debug_glyph_color.png");
		$form->addButton("Delete Protection", 0, "textures/ui/realms_red_x.png");
		$form->addExtraData("arena", $arena);
		$player->sendForm($form);
	}
}