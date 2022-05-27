<?php

declare(strict_types=1);

namespace zodiax\forms\display\staff\npc;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class NPCManagerMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null){
				switch($data){
					case 0:
						CreateNPCForm::onDisplay($player);
						break;
					case 1:
						NPCSelectorMenu::onDisplay($player, "edit");
						break;
					case 2:
						NPCSelectorMenu::onDisplay($player, "delete");
						break;
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Manage " . TextFormat::WHITE . "NPCs"));
		$form->setContent("Manage the NPCs in the server");
		$form->addButton(TextFormat::BOLD . "Create NPC", 0, "textures/ui/confirm.png");
		$form->addButton(TextFormat::BOLD . "Edit NPC", 0, "textures/ui/debug_glyph_color.png");
		$form->addButton(TextFormat::BOLD . "Delete NPC", 0, "textures/ui/realms_red_x.png");
		$player->sendForm($form);
	}
}
