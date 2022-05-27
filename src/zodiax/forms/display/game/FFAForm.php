<?php

declare(strict_types=1);

namespace zodiax\forms\display\game;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\arena\ArenaManager;
use zodiax\forms\types\SimpleForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function count;

class FFAForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && $data !== null && isset($extraData["arenas"]) && isset($extraData["arenas"][$data])){
				$extraData["arenas"][$data]->addPlayer($player);
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Free for " . TextFormat::WHITE . "all"));
		$form->setContent("");
		if(count($arenas = ArenaManager::getFFAArenas(true)) <= 0){
			$form->addButton(TextFormat::GRAY . "None");
		}else{
			foreach($arenas as $arena){
				$form->addButton(TextFormat::GRAY . $arena->getName() . "\n" . TextFormat::WHITE . $arena->getPlayers(true) . PracticeCore::COLOR . " Players", 0, $arena->getTexture());
			}
			$form->addExtraData("arenas", $arenas);
		}
		$player->sendForm($form);
	}
}