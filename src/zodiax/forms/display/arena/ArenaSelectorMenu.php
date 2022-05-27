<?php

declare(strict_types=1);

namespace zodiax\forms\display\arena;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\display\arena\edit\EditArenaMenu;
use zodiax\forms\types\SimpleForm;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function count;

class ArenaSelectorMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null){
				if(isset($extraData["arenas"]) && isset($extraData["arenas"][$data]) && isset($extraData["formType"])){
					$arena = $extraData["arenas"][$data];
					$type = $extraData["formType"];
					switch($type){
						case "view" :
							ViewArenaForm::onDisplay($player, $arena);
							break;
						case "edit" :
							EditArenaMenu::onDisplay($player, $arena);
							break;
						case "delete":
							DeleteArenaForm::onDisplay($player, $arena);
							break;
					}
				}
			}
		});

		$formType = $args[0] ?? "view";
		$arenas = $args[1] ?? [];
		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Select " . TextFormat::WHITE . "Arena"));
		$form->setContent("Select the arena to edit or delete");
		if(count($arenas) <= 0){
			$form->addButton(TextFormat::GRAY . "None");
		}else{
			foreach($arenas as $arena){
				$form->addButton($arena->getName());
			}
		}
		$form->addExtraData("formType", $formType);
		$form->addExtraData("arenas", $arenas);
		$player->sendForm($form);
	}
}