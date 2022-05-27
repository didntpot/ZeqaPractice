<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\event;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\arena\ArenaManager;
use zodiax\event\EventHandler;
use zodiax\event\PracticeEvent;
use zodiax\forms\types\SimpleForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function count;

class EventForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && $data !== null && isset($extraData["events"]) && isset($extraData["events"][$data])){
				$event = $extraData["events"][$data];
				if($event instanceof PracticeEvent && !$event->isStarted()){
					$event->addPlayer($player);
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Play " . TextFormat::WHITE . "Event"));
		$form->setContent("");
		$result = [];
		foreach(EventHandler::getEvents() as $event){
			if($event->isOpen()){
				$result[] = $event;
			}
		}
		if(count($result) <= 0){
			$form->addButton(TextFormat::GRAY . "None");
		}else{
			foreach($result as $event){
				$button = TextFormat::GRAY . $event->getArena() . "\n" . PracticeCore::COLOR . "Starting in: " . TextFormat::WHITE . $event->getCountdown() . PracticeCore::COLOR . " Players: " . TextFormat::WHITE . $event->getPlayers(true);
				$form->addButton($button, 0, ArenaManager::getArena($event->getArena())->getKit()->getMiscKitInfo()->getTexture());
			}
			$form->addExtraData("events", $result);
		}
		$player->sendForm($form);
	}
}