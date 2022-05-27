<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\spectate;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\arena\ArenaManager;
use zodiax\duel\BotHandler;
use zodiax\duel\DuelHandler;
use zodiax\event\EventHandler;
use zodiax\forms\types\SimpleForm;
use zodiax\party\duel\PartyDuelHandler;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\training\TrainingHandler;
use function count;

class SpectateMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		$type = null;
		if(isset($args[0])){
			$type = $args[0];
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && $data !== null){
				$type = "";
				if(!isset($extraData["type"])){
					$type = match ($data) {
						0 => "ffa",
						1 => "duel",
						2 => "bot",
						3 => "party",
						4 => "training",
						5 => "event"
					};
					if($type === "training"){
						self::onDisplay($player, $type);
						return;
					}
				}elseif($extraData["type"] === "training"){
					$type = match ($data) {
						0 => "blockin",
						1 => "clutch",
						2 => "reduce"
					};
				}
				SpectateForm::onDisplay($player, $type);
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Spectate " . TextFormat::WHITE . "Games"));
		$form->setContent("");
		if($type === null){
			$ffas = ArenaManager::getFFAArenas(true);
			$ffa = 0;
			foreach($ffas as $arena){
				$ffa += $arena->getPlayers(true);
			}
			$events = EventHandler::getEvents();
			$event = 0;
			foreach(EventHandler::getEvents() as $arena){
				$event += $arena->getPlayers(true);
			}
			$form->addButton(PracticeCore::COLOR . count($ffas) . TextFormat::WHITE . " FFA(s)\n" . TextFormat::WHITE . $ffa . PracticeCore::COLOR . " Playing", 0, "zeqa/textures/ui/items/ffa.png");
			$form->addButton(PracticeCore::COLOR . "Player" . TextFormat::WHITE . " Duel\n" . TextFormat::WHITE . DuelHandler::getDuels(true) * 2 . PracticeCore::COLOR . " In-Fights", 0, "zeqa/textures/ui/more/player_duel.png");
			$form->addButton(PracticeCore::COLOR . "Bot" . TextFormat::WHITE . " Duel\n" . TextFormat::WHITE . BotHandler::getDuels(true) . PracticeCore::COLOR . " In-Fights", 0, "zeqa/textures/ui/items/spectate.png");
			$form->addButton(PracticeCore::COLOR . "Party" . TextFormat::WHITE . " Duel\n" . TextFormat::WHITE . PartyDuelHandler::getDuels(true) * 2 . PracticeCore::COLOR . " In-Fights", 0, "zeqa/textures/ui/more/party_duel.png");
			$form->addButton(PracticeCore::COLOR . 3 . TextFormat::WHITE . " Training\n" . TextFormat::WHITE . (count(TrainingHandler::getClutches()) + count(TrainingHandler::getReduces()) + count(TrainingHandler::getBlockIns())) . PracticeCore::COLOR . " Playing", 0, "textures/ui/icon_book_writable.png");
			$form->addButton(PracticeCore::COLOR . count($events) . TextFormat::WHITE . " Event(s)\n" . TextFormat::WHITE . $event . PracticeCore::COLOR . " Playing", 0, "textures/ui/worldsIcon.png");
		}elseif($type === "training"){
			$form->addButton(PracticeCore::COLOR . "Block-In" . TextFormat::WHITE . " Practice\n" . TextFormat::WHITE . count(TrainingHandler::getBlockIns()) . PracticeCore::COLOR . " Playing", 0, "textures/items/shears.png");
			$form->addButton(PracticeCore::COLOR . "Clutch" . TextFormat::WHITE . " Practice\n" . TextFormat::WHITE . count(TrainingHandler::getClutches()) . PracticeCore::COLOR . " Playing", 0, "zeqa/textures/ui/gm/build.png");
			$form->addButton(PracticeCore::COLOR . "Reduce" . TextFormat::WHITE . " Practice\n" . TextFormat::WHITE . count(TrainingHandler::getReduces()) . PracticeCore::COLOR . " Playing", 0, "zeqa/textures/ui/gm/stickfight.png");
			$form->addExtraData("type", $type);
		}
		$player->sendForm($form);
	}
}
