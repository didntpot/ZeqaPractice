<?php

declare(strict_types=1);

namespace zodiax\duel\misc;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\XpCollectSound;
use zodiax\duel\DuelHandler;
use zodiax\player\misc\queue\QueueHandler;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;

class RequestHandler{

	private static array $requests = [];

	public static function sendRequest(Player $from, Player $to, string $kit, bool $ranked) : void{
		$requested = self::$requests["{$to->getName()}:{$from->getName()}"] ?? null;
		if($requested !== null && $requested->getKit() === $kit && $requested->isRanked() === $ranked){
			if(($tSession = PlayerManager::getSession($to)) !== null && $tSession->isInHub() && !$tSession->isInParty() && !$tSession->getKitHolder()->isEditingKit() && !$tSession->isInQueue() && !$tSession->isInBotQueue() && !QueueHandler::isInQueue($to)){
				self::acceptRequest($requested);
				DuelHandler::placeInDuel($from, $to, $kit, $ranked);
				return;
			}
		}
		if($from->isOnline()){
			$from->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "Successfully sent a duel request to " . TextFormat::WHITE . $to->getDisplayName());
		}
		$request = self::$requests[$key = "{$from->getName()}:{$to->getName()}"] ?? null;
		if($to->isOnline() && ($request === null || $request->getKit() !== $kit || $request->isRanked() !== $ranked)){
			$to->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "Received a new duel request from " . TextFormat::WHITE . $from->getDisplayName());
			$to->broadcastSound(new XpCollectSound(), [$to]);
		}
		self::$requests[$key] = new DuelRequest($from, $to, $kit, $ranked);
	}

	public static function getRequestsOf(Player $player) : array{
		$result = [];
		$name = $player->getName();
		foreach(self::$requests as $key => $request){
			if($request->getTo() === $name){
				$from = $request->getFrom();
				if(PlayerManager::getPlayerExact($from) !== null){
					$result[$from] = $request;
				}else{
					unset(self::$requests[$key]);
				}
			}
		}
		return $result;
	}

	public static function removeRequestsOf(Player $player) : void{
		$name = $player->getName();
		foreach(self::$requests as $key => $request){
			if($request->getTo() === $name || $request->getFrom() === $name){
				unset(self::$requests[$key]);
			}
		}
	}

	public static function acceptRequest(DuelRequest $request) : void{
		if(($from = PlayerManager::getPlayerExact($request->getFrom())) !== null && ($to = PlayerManager::getPlayerExact($request->getTo())) !== null){
			DuelHandler::removeFromQueue($from, false);
			DuelHandler::removeFromQueue($to, false);
			$to->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "You have accepted " . TextFormat::WHITE . $from->getDisplayName() . "'s" . TextFormat::GRAY . " duel request");
			$from->sendMessage(PracticeCore::PREFIX . TextFormat::WHITE . $to->getDisplayName() . TextFormat::GRAY . " has accepted your duel request");
			unset(self::$requests["{$from->getName()}:{$to->getName()}"]);
		}
	}
}
