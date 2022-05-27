<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\bot;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\kits\DefaultKit;
use zodiax\kits\KitsManager;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function count;

class BotForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && $data !== null && isset($extraData["kits"]) && isset($extraData["kits"][$data])){
				$kit = $extraData["kits"][$data];
				if($kit instanceof DefaultKit){
					DifficultSelectorMenu::onDisplay($player, $kit);
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Bot" . TextFormat::WHITE . " Duel"));
		$form->setContent("");
		if(count($duels = KitsManager::getBotKits()) <= 0){
			$form->addButton(TextFormat::GRAY . "None");
		}else{
			foreach($duels as $duel){
				$form->addButton(TextFormat::GRAY . $duel->getName(), 0, $duel->getMiscKitInfo()->getTexture());
			}
			$form->addExtraData("kits", $duels);
		}
		$player->sendForm($form);
	}
}