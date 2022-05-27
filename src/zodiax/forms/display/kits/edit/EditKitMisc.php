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

class EditKitMisc{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($kit = $args[0]) === null || !$kit instanceof DefaultKit){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["kit"])){
				$kit = $extraData["kit"];
				$misckitInfo = $kit->getMiscKitInfo();
				$misckitInfo->setFFAEnabled($data[1]);
				$misckitInfo->setDuelsEnabled($data[2]);
				$misckitInfo->setReplaysEnabled($data[3]);
				$misckitInfo->setBotEnabled($data[4]);
				$misckitInfo->setEventEnabled($data[5]);
				$misckitInfo->setTrainingEnabled($data[6]);
				$misckitInfo->setDamageEnabled($data[7]);
				$misckitInfo->setBuild($data[8]);
				KitsManager::saveKit($kit);
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully edited {$kit->getName()}'s misc " . TextFormat::GRAY . $kit->getName());
			}
		});

		$misckitInfo = $kit->getMiscKitInfo();
		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Edit " . TextFormat::WHITE . "Misc"));
		$form->addLabel("Edit the information of the {$kit->getName()} kit");
		$form->addToggle("isFFAEnabled", $misckitInfo->isFFAEnabled());
		$form->addToggle("isDuelsEnabled", $misckitInfo->isDuelsEnabled());
		$form->addToggle("isReplaysEnabled", $misckitInfo->isReplaysEnabled());
		$form->addToggle("isBotEnabled", $misckitInfo->isBotEnabled());
		$form->addToggle("isEventEnabled", $misckitInfo->isEventEnabled());
		$form->addToggle("isTrainingEnabled", $misckitInfo->isTrainingEnabled());
		$form->addToggle("canDamagePlayers", $misckitInfo->canDamagePlayers());
		$form->addToggle("canBuild", $misckitInfo->canBuild());
		$form->addExtraData("kit", $kit);
		$player->sendForm($form);
	}
}