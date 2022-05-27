<?php

declare(strict_types=1);

namespace zodiax\forms\display\kits\edit;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\kits\DefaultKit;
use zodiax\kits\KitsManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class EditKitIcon{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($kit = $args[0]) === null || !$kit instanceof DefaultKit){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["kit"])){
				$kit = $extraData["kit"];
				$kit->getMiscKitInfo()->setTexture((string) $data[1]);
				KitsManager::saveKit($kit);
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully edited {$kit->getName()}'s icon " . TextFormat::GRAY . $kit->getName());
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Edit " . TextFormat::WHITE . "Icon"));
		$form->addLabel("Edit the form display icon for the {$kit->getName()} kit");
		$form->addInput("Image Path:", $kit->getMiscKitInfo()->getTexture());
		$form->addExtraData("kit", $kit);
		$player->sendForm($form);
	}
}