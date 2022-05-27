<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\spectate;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\arena\ArenaManager;
use zodiax\arena\types\FFAArena;
use zodiax\duel\BotHandler;
use zodiax\duel\DuelHandler;
use zodiax\duel\types\BotDuel;
use zodiax\duel\types\PlayerDuel;
use zodiax\event\EventHandler;
use zodiax\event\PracticeEvent;
use zodiax\forms\types\SimpleForm;
use zodiax\kits\KitsManager;
use zodiax\party\duel\PartyDuel;
use zodiax\party\duel\PartyDuelHandler;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\training\TrainingHandler;
use zodiax\training\types\BlockInPractice;
use zodiax\training\types\ClutchPractice;
use zodiax\training\types\ReducePractice;
use function array_values;
use function count;

class SpectateForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($type = $args[0]) === null){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && $data !== null && isset($extraData["available"]) && isset($extraData["available"][$data])){
				$game = $extraData["available"][$data];
				if($game instanceof FFAArena || $game instanceof PlayerDuel || $game instanceof BotDuel || $game instanceof PartyDuel || $game instanceof PracticeEvent || $game instanceof BlockInPractice || $game instanceof ClutchPractice || $game instanceof ReducePractice){
					$game->addSpectator($player);
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Spectate " . TextFormat::WHITE . "Games"));
		$form->setContent("");
		if($type === "ffa"){
			if(count($ffas = ArenaManager::getFFAArenas(true)) <= 0){
				$form->addButton(TextFormat::GRAY . "None");
			}else{
				foreach($ffas as $ffa){
					$form->addButton(TextFormat::GRAY . $ffa->getName() . "\n" . TextFormat::WHITE . $ffa->getPlayers(true) . PracticeCore::COLOR . " Players", 0, $ffa->getTexture());
				}
				$form->addExtraData("available", array_values($ffas));
			}
		}elseif($type === "duel"){
			if(count($duels = DuelHandler::getDuels()) <= 0){
				$form->addButton(TextFormat::GRAY . "None");
			}else{
				foreach($duels as $duel){
					$button = TextFormat::WHITE . PlayerManager::getPlayerExact($duel->getPlayer1())?->getDisplayName() . PracticeCore::COLOR . " vs " . TextFormat::WHITE . PlayerManager::getPlayerExact($duel->getPlayer2())?->getDisplayName();
					$form->addButton($button, 0, KitsManager::getKit($duel->getKit())?->getMiscKitInfo()->getTexture() ?? "");
				}
				$form->addExtraData("available", array_values($duels));
			}
		}elseif($type === "bot"){
			if(count($duels = BotHandler::getDuels()) <= 0){
				$form->addButton(TextFormat::GRAY . "None");
			}else{
				foreach($duels as $duel){
					$button = TextFormat::WHITE . PlayerManager::getPlayerExact($duel->getPlayer())?->getDisplayName() . PracticeCore::COLOR . " vs " . TextFormat::WHITE . $duel->getBot()?->getDisplayName();
					$form->addButton($button, 0, KitsManager::getKit($duel->getKit())?->getMiscKitInfo()->getTexture() ?? "");
				}
				$form->addExtraData("available", array_values($duels));
			}
		}elseif($type === "party"){
			if(count($duels = PartyDuelHandler::getDuels()) <= 0){
				$form->addButton(TextFormat::GRAY . "None");
			}else{
				foreach($duels as $duel){
					$button = TextFormat::WHITE . $duel->getParty1() . PracticeCore::COLOR . " vs " . TextFormat::WHITE . $duel->getParty2();
					$form->addButton($button, 0, KitsManager::getKit($duel->getKit())?->getMiscKitInfo()->getTexture() ?? "");
				}
				$form->addExtraData("available", array_values($duels));
			}
		}elseif($type === "event"){
			if(count($events = EventHandler::getEvents()) <= 0){
				$form->addButton(TextFormat::GRAY . "None");
			}else{
				foreach($events as $event){
					$button = TextFormat::GRAY . $event->getArena() . "\n" . PracticeCore::COLOR . $event->getPlayers(true) . TextFormat::WHITE . " Playing";
					$form->addButton($button, 0, ArenaManager::getArena($event->getArena())?->getKit()?->getMiscKitInfo()->getTexture() ?? "");
				}
				$form->addExtraData("available", $events);
			}
		}elseif($type === "blockin"){
			if(count($blockIns = TrainingHandler::getBlockIns()) <= 0){
				$form->addButton(TextFormat::GRAY . "None");
			}else{
				foreach($blockIns as $blockIn){
					$button = TextFormat::WHITE . PlayerManager::getPlayerExact($blockIn->getOwner())?->getDisplayName() . PracticeCore::COLOR . "'s Block-In";
					$form->addButton($button, 0, KitsManager::getKit($blockIn->getKit())?->getMiscKitInfo()->getTexture() ?? "");
				}
				$form->addExtraData("available", array_values($blockIns));
			}
		}elseif($type === "clutch"){
			if(count($clutches = TrainingHandler::getClutches()) <= 0){
				$form->addButton(TextFormat::GRAY . "None");
			}else{
				foreach($clutches as $clutch){
					$button = TextFormat::WHITE . PlayerManager::getPlayerExact($clutch->getPlayer())?->getDisplayName() . PracticeCore::COLOR . "'s Clutch";
					$form->addButton($button, 0, KitsManager::getKit($clutch->getKit())?->getMiscKitInfo()->getTexture() ?? "");
				}
				$form->addExtraData("available", array_values($clutches));
			}
		}elseif($type === "reduce"){
			if(count($reduces = TrainingHandler::getReduces()) <= 0){
				$form->addButton(TextFormat::GRAY . "None");
			}else{
				foreach($reduces as $reduce){
					$button = TextFormat::WHITE . PlayerManager::getPlayerExact($reduce->getPlayer())?->getDisplayName() . PracticeCore::COLOR . "'s Reduce";
					$form->addButton($button, 0, KitsManager::getKit($reduce->getKit())?->getMiscKitInfo()->getTexture() ?? "");
				}
				$form->addExtraData("available", array_values($reduces));
			}
		}
		$player->sendForm($form);
	}
}