<?php

declare(strict_types=1);

namespace zodiax\party\duel\misc;

use pocketmine\utils\TextFormat;
use pocketmine\world\sound\XpCollectSound;
use zodiax\party\duel\PartyDuelHandler;
use zodiax\party\PartyManager;
use zodiax\party\PracticeParty;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;

class PartyDuelRequestHandler{

	private static array $requests = [];

	public static function sendRequest(PracticeParty $from, PracticeParty $to, string $kit) : void{
		$requested = self::$requests["{$to->getName()}:{$from->getName()}"] ?? null;
		if($requested !== null && $requested->getKit() === $kit){
			if(($tSession = PlayerManager::getSession(PlayerManager::getPlayerExact($to->getOwner()))) !== null && $tSession->isInHub() && $tSession->isInParty() && !$to->isInQueue()){
				self::acceptRequest($requested);
				PartyDuelHandler::placeInDuel($from, $to, $kit);
				return;
			}
		}
		PlayerManager::getPlayerExact($from->getOwner())?->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "Successfully sent a duel request to " . TextFormat::WHITE . $to->getName() . TextFormat::GRAY);
		$request = self::$requests[$key = "{$from->getName()}:{$to->getName()}"] ?? null;
		$toOwner = PlayerManager::getPlayerExact($to->getOwner());
		if($toOwner !== null && $toOwner->isOnline() && ($request === null || $request->getKit() !== $kit)){
			$toOwner->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "Received a new duel request from " . TextFormat::WHITE . $from->getName() . TextFormat::GRAY);
			$toOwner->broadcastSound(new XpCollectSound(), [$toOwner]);
		}
		self::$requests[$key] = new PartyDuelRequest($from, $to, $kit);
	}

	public static function getRequestsOf(PracticeParty $party) : array{
		$result = [];
		$name = $party->getName();
		foreach(self::$requests as $key => $request){
			if($request->getTo() === $name){
				if(PartyManager::getPartyFromName($from = $request->getFrom()) !== null){
					$result[$from] = $request;
				}else{
					unset(self::$requests[$key]);
				}
			}
		}
		return $result;
	}

	public static function removeRequestsOf(PracticeParty $party) : void{
		$name = $party->getName();
		foreach(self::$requests as $key => $request){
			if($request->getTo() === $name || $request->getFrom() === $name){
				unset(self::$requests[$key]);
			}
		}
	}

	public static function acceptRequest(PartyDuelRequest $request) : void{
		$from = PartyManager::getPartyFromName($request->getFrom());
		$to = PartyManager::getPartyFromName($request->getTo());
		if($from instanceof PracticeParty && $to instanceof PracticeParty){
			PartyDuelHandler::removeFromQueue($from);
			PartyDuelHandler::removeFromQueue($to);
			PlayerManager::getPlayerExact($to->getOwner())?->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "You have accepted " . TextFormat::WHITE . $from->getName() . "'s" . TextFormat::GRAY . " duel request");
			PlayerManager::getPlayerExact($from->getOwner())?->sendMessage(PracticeCore::PREFIX . TextFormat::WHITE . $to->getName() . TextFormat::GRAY . " has accepted your duel request");
			unset(self::$requests["{$from->getName()}:{$to->getName()}"]);
		}
	}
}
