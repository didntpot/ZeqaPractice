<?php

declare(strict_types=1);

namespace zodiax\party\misc;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\XpCollectSound;
use zodiax\duel\DuelHandler;
use zodiax\party\PracticeParty;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;

class InviteHandler{

	private static array $invites = [];

	public static function sendInvite(Player $from, Player $to, PracticeParty $party) : void{
		if($from->isOnline()){
			$from->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "Successfully sent party invite to " . TextFormat::WHITE . $to->getDisplayName() . TextFormat::GRAY);
		}
		$invite = self::$invites[$key = "{$from->getName()}:{$to->getName()}"] ?? null;
		if($to->isOnline() && ($invite === null || $invite->getParty() !== $party)){
			$to->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "Received a new party invite from " . TextFormat::WHITE . $from->getDisplayName() . TextFormat::GRAY);
			$to->broadcastSound(new XpCollectSound(), [$to]);
		}
		self::$invites[$key] = new PartyInvite($from, $to, $party);
	}

	public static function getInvitesOf(Player $player) : array{
		$result = [];
		$name = $player->getName();
		foreach(self::$invites as $key => $invite){
			if($invite->getTo() === $name){
				if(PlayerManager::getPlayerExact($from = $invite->getFrom()) !== null){
					$result[$from] = $invite;
				}else{
					unset(self::$invites[$key]);
				}
			}
		}
		return $result;
	}

	public static function removeInvitesOf(Player $player) : void{
		$name = $player->getName();
		foreach(self::$invites as $key => $invite){
			if($invite->getTo() === $name || $invite->getFrom() === $name){
				unset(self::$invites[$key]);
			}
		}
	}

	public static function acceptInvite(PartyInvite $invite) : void{
		$from = PlayerManager::getPlayerExact($invite->getFrom());
		$to = PlayerManager::getPlayerExact($invite->getTo());
		if($from instanceof Player && $to instanceof Player){
			DuelHandler::removeFromQueue($from, false);
			DuelHandler::removeFromQueue($to, false);
			$to->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "You have accepted " . TextFormat::WHITE . $from->getDisplayName() . "'s" . TextFormat::GRAY . " party invite");
			$from->sendMessage(PracticeCore::PREFIX . TextFormat::WHITE . $to->getDisplayName() . TextFormat::GRAY . " has accepted your party invite");
			unset(self::$invites["{$from->getName()}:{$to->getName()}"]);
		}
	}
}
