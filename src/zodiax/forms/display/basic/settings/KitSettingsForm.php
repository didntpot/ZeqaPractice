<?php

declare(strict_types=1);

namespace zodiax\forms\display\basic\settings;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\kits\KitsManager;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function array_values;
use function count;

class KitSettingsForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["kits"]) && count($extraData["kits"]) > 0){
				$kit = array_values($extraData["kits"])[$data];
				$session = PlayerManager::getSession($player);
				if($session->isInHub() && $session->getKitHolder()->setEditingKit($kit->getName())){
					$player->sendMessage("\n\n");
					$player->sendMessage(TextFormat::YELLOW . "   You are now editing your inventory.\n");
					$player->sendMessage(TextFormat::WHITE . "   Type " . TextFormat::GREEN . "Confirm" . TextFormat::WHITE . " in chat to " . TextFormat::GREEN . "save" . TextFormat::WHITE . " the current edited");
					$player->sendMessage(TextFormat::WHITE . "   Type " . TextFormat::YELLOW . "Reset" . TextFormat::WHITE . " in chat to " . TextFormat::YELLOW . "reset" . TextFormat::WHITE . " the current edited");
					$player->sendMessage(TextFormat::WHITE . "   Type " . TextFormat::RED . "Cancel" . TextFormat::WHITE . " in chat to " . TextFormat::RED . "cancel" . TextFormat::WHITE . " the current edited");
					$player->sendMessage("\n\n");
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Kit " . TextFormat::WHITE . "Settings"));
		$form->setContent("");
		if(count($kits = KitsManager::getKits()) <= 0){
			$form->addButton(TextFormat::GRAY . "None");
		}else{
			foreach($kits as $kit){
				$form->addButton(TextFormat::GRAY . $kit->getName(), 0, $kit->getMiscKitInfo()->getTexture());
			}
		}
		$form->addExtraData("kits", $kits);
		$player->sendForm($form);
	}
}