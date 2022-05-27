<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\event;

use DateTime;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\XpCollectSound;
use zodiax\arena\ArenaManager;
use zodiax\event\EventHandler;
use zodiax\event\PracticeEvent;
use zodiax\forms\types\SimpleForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function array_intersect;
use function count;
use function date_create_from_format;
use function date_format;

class HostForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && $data !== null && isset($extraData["events"]) && isset($extraData["events"][$data])){
				$event = $extraData["events"][$data];
				if($event instanceof PracticeEvent){
					$durationInfo = $session->getDurationInfo();
					if($durationInfo->isHostExpired()){
						$expiresTime = new DateTime("NOW");
						$ranks = $session->getRankInfo()->getRanks(true);
						if(!empty(array_intersect($ranks, ["HeadMod", "Mod", "Helper", "MvpPlus"]))){
							$expiresTime->modify("+30 mins");
						}elseif(!empty(array_intersect($ranks, ["Builder", "Designer", "Famous", "Media", "Mvp"]))){
							$expiresTime->modify("+45 mins");
						}elseif(!empty(array_intersect($ranks, ["Vip", "Booster", "Voter"]))){
							$expiresTime->modify("+1 hours");
						}
						$session->getDurationInfo()->setHosted(date_format($expiresTime, "Y-m-d-H-i"));
						$msg = PracticeCore::PREFIX . TextFormat::AQUA . $player->getDisplayName() . TextFormat::DARK_AQUA . " is hosting a " . TextFormat::AQUA . $event->getArena() . "!";
						$xpSound = new XpCollectSound();
						foreach(PlayerManager::getOnlinePlayers() as $p){
							$p->sendMessage($msg);
							$p->broadcastSound($xpSound, [$p]);
						}
						$event->open();
					}else{
						$now = new DateTime("NOW");
						$expiretime = date_create_from_format("Y-m-d-H-i", $durationInfo->getHosted());
						if($expiretime instanceof DateTime){
							$remaintime = $now->diff($expiretime);
							$remaintime = $remaintime->format("%i minute(s)");
							$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You can host the event again in $remaintime");
						}
					}
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Host " . TextFormat::WHITE . "Event"));
		$form->setContent("");
		$events = EventHandler::getEvents();
		$result = [];
		foreach($events as $event){
			if(!$event->isOpen() && !$event->isStarted()){
				$result[] = $event;
			}
		}
		if(count($result) <= 0){
			$form->addButton(TextFormat::GRAY . "None");
		}else{
			foreach($result as $event){
				$form->addButton(TextFormat::GRAY . $event->getArena(), 0, ArenaManager::getArena($event->getArena())?->getKit()?->getMiscKitInfo()->getTexture() ?? "");
			}
			$form->addExtraData("events", $result);
		}
		$player->sendForm($form);
	}
}