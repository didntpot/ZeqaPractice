<?php

declare(strict_types=1);

namespace zodiax\party;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\duel\DuelHandler;
use zodiax\game\items\ItemHandler;
use zodiax\party\duel\PartyDuelHandler;
use zodiax\player\info\scoreboard\ScoreboardInfo;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use function array_rand;
use function array_search;
use function array_values;
use function count;
use function in_array;

class PracticeParty{

	private array $players;
	private string $owner;
	private string $name;
	private bool $open;
	private array $blacklisted;

	public function __construct(Player $owner, string $name, bool $open = true){
		$this->owner = $owner->getName();
		$this->name = $name;
		$this->players = [$this->owner => $this->owner];
		$this->open = $open;
		$this->blacklisted = [];
	}

	public function addPlayer(Player $player) : void{
		if(!isset($this->players[$name = $player->getName()])){
			DuelHandler::removeFromQueue($player, false);
			$this->players[$name] = $name;
			ItemHandler::spawnPartyItems($player);
			$msg = PracticeCore::PREFIX . TextFormat::GREEN . $player->getDisplayName() . TextFormat::GRAY . " joined party";
			foreach($this->players as $member){
				if(($msession = PlayerManager::getSession($member = (PlayerManager::getPlayerExact($member)))) !== null){
					$member->sendMessage($msg);
					$msession->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_PARTY);
				}
			}
		}
	}

	public function removePlayer(Player $player, string $reason = "", bool $blacklist = false) : void{
		$kicked = $reason !== "";
		if(isset($this->players[$name = $player->getName()])){
			PartyDuelHandler::removeFromQueue($this);
			$partyDuel = PartyDuelHandler::getDuel($this);
			$partyDuel?->removeFromTeam($player);
			if($this->isOwner($player)){
				if($partyDuel !== null && $this->getPlayers(true) > 1){
					unset($this->players[$name]);
					$this->owner = $this->players[array_rand($this->players)];
					$msg = PracticeCore::PREFIX . TextFormat::RED . $player->getDisplayName() . TextFormat::GRAY . " left the party. " . TextFormat::LIGHT_PURPLE . PlayerManager::getPlayerExact($this->owner)?->getDisplayName() . TextFormat::GRAY . " has been promoted to the owner of the party instead";
					foreach($this->players as $member){
						PlayerManager::getPlayerExact($member)?->sendMessage($msg);
					}
					return;
				}
				$msg = PracticeCore::PREFIX . TextFormat::RED . $player->getDisplayName() . TextFormat::GRAY . " has disbanded the party";
				$players = $this->players;
				PartyManager::endParty($this);
				foreach($players as $member){
					if(($msession = PlayerManager::getSession($member = (PlayerManager::getPlayerExact($member)))) !== null){
						$member->sendMessage($msg);
						if($msession->isInHub()){
							ItemHandler::spawnHubItems($member);
							$msession->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_SPAWN);
						}
					}
				}
				return;
			}
			unset($this->players[$name]);
			if($kicked && $blacklist){
				$this->addToBlacklist($player);
			}
			$msg = PracticeCore::PREFIX . TextFormat::RED . $player->getDisplayName() . TextFormat::GRAY . " left the party";
			foreach($this->players as $member){
				if(($msession = PlayerManager::getSession($member = (PlayerManager::getPlayerExact($member)))) !== null){
					$member->sendMessage($msg);
					if($msession->isInHub()){
						ItemHandler::spawnPartyItems($member);
						$msession->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_PARTY);
					}
				}
			}
			if($player->isOnline()){
				$player->sendMessage($msg);
				PlayerManager::getSession($player)->reset();
			}
		}
	}

	public function isInQueue() : bool{
		return PartyDuelHandler::isInQueue($this);
	}

	public function isOwner(Player $player) : bool{
		return $this->owner === $player->getName();
	}

	public function addToBlacklist(string|Player $player) : void{
		$name = ($player instanceof Player) ? $player->getName() : $player;
		$this->blacklisted[] = $name;
		$msg = PracticeCore::PREFIX . TextFormat::DARK_GRAY . (($player instanceof Player) ? $player->getDisplayName() : PlayerManager::getPlayerExact($name)?->getDisplayName() ?? $name) . TextFormat::GRAY . " was added to the blacklist";
		foreach($this->players as $member){
			PlayerManager::getPlayerExact($member)?->sendMessage($msg);
		}
	}

	public function isPlayer(string|Player $player) : bool{
		return isset($this->players[$player instanceof Player ? $player->getName() : $player]);
	}

	public function equalsParty(PracticeParty $party) : bool{
		return $party->getName() === $this->getName();
	}

	public function getName() : string{
		return $this->name;
	}

	public function getPlayers(bool $asInt = false) : int|array{
		return $asInt ? count($this->players) : $this->players;
	}

	public function isOpen() : bool{
		return $this->open;
	}

	public function setOpen(bool $open = true) : void{
		$this->open = $open;
	}

	public function promoteToOwner(Player $player) : void{
		$oldOwner = $this->owner;
		$this->owner = $player->getName();
		ItemHandler::spawnPartyItems(PlayerManager::getPlayerExact($oldOwner));
		ItemHandler::spawnPartyItems(PlayerManager::getPlayerExact($this->owner));
		$msg = PracticeCore::PREFIX . TextFormat::LIGHT_PURPLE . $player->getDisplayName() . TextFormat::GRAY . " has been promoted to the owner of the party";
		foreach($this->players as $member){
			PlayerManager::getPlayerExact($member)?->sendMessage($msg);
		}
	}

	public function getOwner() : string{
		return $this->owner;
	}

	public function getPlayer(string $name) : ?Player{
		if(isset($this->players[$name])){
			return PlayerManager::getPlayerExact($this->players[$name]);
		}
		foreach($this->players as $player){
			if($player === $name){
				return PlayerManager::getPlayerExact($player);
			}
		}
		return null;
	}

	public function isBlackListed(string|Player $player) : bool{
		return in_array(($player instanceof Player ? $player->getName() : $player), $this->blacklisted, true);
	}

	public function removeFromBlacklist(string|Player $player) : void{
		if(in_array($name = ($player instanceof Player) ? $player->getName() : $player, $this->blacklisted, true)){
			unset($this->blacklisted[array_search($name, $this->blacklisted, true)]);
			$this->blacklisted = array_values($this->blacklisted);
			if($player instanceof Player){
				$name = $player->getDisplayName();
			}elseif(($player = PlayerManager::getPlayerExact($name)) !== null){
				$name = $player->getDisplayName();
			}
			$msg = PracticeCore::PREFIX . TextFormat::GREEN . $name . TextFormat::GRAY . " has been removed from the blacklist";
			foreach($this->players as $member){
				PlayerManager::getPlayerExact($member)?->sendMessage($msg);
			}
		}
	}

	public function getBlacklisted() : array{
		return $this->blacklisted;
	}

	public function destroyCycles() : void{
		$this->players = [];
		$this->blacklisted = [];
	}
}