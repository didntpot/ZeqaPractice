<?php

declare(strict_types=1);

namespace zodiax\forms\display\kits;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\display\kits\edit\EditKitMenu;
use zodiax\forms\types\SimpleForm;
use zodiax\kits\KitsManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function array_values;
use function count;

class KitSelectorMenu{
	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["kits"]) && isset($extraData["formType"])){
				$kits = array_values($extraData["kits"]);
				$type = $extraData["formType"];
				if(count($kits) <= 0){
					return;
				}
				$kit = $kits[$data];
				switch($type){
					case "view" :
						ViewKitForm::onDisplay($player, $kit);
						break;
					case "edit" :
						EditKitMenu::onDisplay($player, $kit);
						break;
					case "delete":
						DeleteKitForm::onDisplay($player, $kit);
						break;
				}
			}
		});

		$formType = $args[0] ?? "view";
		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Select " . TextFormat::WHITE . "Kit"));
		$form->setContent("Select the kit to edit or delete");
		$kits = KitsManager::getKits();
		if(count($kits) <= 0){
			$form->addButton(TextFormat::GRAY . "None");
		}else{
			foreach($kits as $kit){
				$form->addButton($kit->getName(), 0, $kit->getMiscKitInfo()->getTexture());
			}
		}
		$form->addExtraData("kits", $kits);
		$form->addExtraData("formType", $formType);
		$player->sendForm($form);
	}
}