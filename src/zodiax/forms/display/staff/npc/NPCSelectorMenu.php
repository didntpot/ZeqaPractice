<?php

declare(strict_types=1);

namespace zodiax\forms\display\staff\npc;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\game\npc\NPCManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function count;

class NPCSelectorMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null){
				if(isset($extraData["npcs"]) && isset($extraData["npcs"][$data]) && isset($extraData["formType"])){
					$npc = $extraData["npcs"][$data];
					$type = $extraData["formType"];
					switch($type){
						case "edit" :
							EditNPCForm::onDisplay($player, $npc);
							break;
						case "delete":
							if(NPCManager::removeNPC($npc)){
								$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Successfully deleted NPC " . TextFormat::WHITE . $npc);
							}else{
								$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Failed to delete NPC " . TextFormat::WHITE . $npc);
							}
							break;
					}
				}
			}
		});

		$formType = $args[0] ?? "edit";
		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Select " . TextFormat::WHITE . "NPC"));
		$form->setContent("Select the NPC to edit or delete");
		$npcs = NPCManager::getNPCs(true);
		if(count($npcs) === 0){
			$form->addButton(TextFormat::DARK_GRAY . "Close");
		}else{
			foreach($npcs as $npc){
				$form->addButton($npc);
			}
			$form->addExtraData("formType", $formType);
			$form->addExtraData("npcs", $npcs);
		}
		$player->sendForm($form);
	}
}