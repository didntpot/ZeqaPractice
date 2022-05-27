<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\bot;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\duel\BotHandler;
use zodiax\forms\types\SimpleForm;
use zodiax\kits\DefaultKit;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class DifficultSelectorMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($kit = $args[0]) === null || !$kit instanceof DefaultKit){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && $data !== null && isset($extraData["kit"])){
				BotHandler::placeInQueue($player, $extraData["kit"]->getName(), $data);
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Bot" . TextFormat::WHITE . " Duel"));
		$form->setContent("");
		$form->addButton(TextFormat::GREEN . "Easy");
		$form->addButton(TextFormat::GOLD . "Medium");
		$form->addButton(TextFormat::RED . "Hard");
		$form->addExtraData("kit", $kit);
		$player->sendForm($form);
	}
}