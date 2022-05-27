<?php

declare(strict_types=1);

namespace zodiax\party;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\game\items\ItemHandler;
use zodiax\party\duel\misc\PartyDuelRequestHandler;
use zodiax\player\info\scoreboard\ScoreboardInfo;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use function trim;

class PartyManager{

	private static array $parties = [];

	public static function createParty(Player $owner, string $name, bool $open = true) : void{
		$partyName = trim($name);
		if(!isset(self::$parties[$name = $partyName])){
			self::$parties[$name] = new PracticeParty($owner, $partyName, $open);
			$owner->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "You have successfully created a new party");
			ItemHandler::spawnPartyItems($owner);
			PlayerManager::getSession($owner)->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_PARTY);
			$msg = PracticeCore::PREFIX . TextFormat::LIGHT_PURPLE . $owner->getDisplayName() . TextFormat::DARK_PURPLE . " created a party named " . TextFormat::LIGHT_PURPLE . $name;
			foreach(PlayerManager::getOnlinePlayers() as $p){
				$p->sendMessage($msg);
			}
		}
	}

	public static function endParty(PracticeParty $party) : void{
		PartyDuelRequestHandler::removeRequestsOf($party);
		if(isset(self::$parties[$name = $party->getName()])){
			self::$parties[$name]->destroyCycles();
			self::$parties[$name] = null;
			unset(self::$parties[$name]);
		}
	}

	public static function getPartyFromName(string $name) : ?PracticeParty{
		return self::$parties[$name] ?? null;
	}

	public static function getPartyFromPlayer(Player $player) : ?PracticeParty{
		foreach(self::$parties as $party){
			if($party->isPlayer($player)){
				return $party;
			}
		}
		return null;
	}

	public static function getParties() : array{
		return self::$parties;
	}
}
