<?php

declare(strict_types=1);

namespace zodiax\ranks;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlThread;
use zodiax\data\database\DatabaseManager;
use zodiax\game\hologram\HologramHandler;
use zodiax\party\duel\PartyDuelHandler;
use zodiax\player\misc\VanishHandler;
use zodiax\player\PlayerManager;
use function array_keys;

class RankHandler{

	const PERMISSION_OWNER = "owner";
	const PERMISSION_ADMIN = "admin";
	const PERMISSION_MOD = "mod";
	const PERMISSION_HELPER = "helper";
	const PERMISSION_BUILDER = "builder";
	const PERMISSION_CONTENT_CREATOR = "content-creator";
	const PERMISSION_VIPPL = "vip+";
	const PERMISSION_VIP = "vip";
	const PERMISSION_NONE = "none";
	const PERMISSION_INDEXES = [self::PERMISSION_OWNER, self::PERMISSION_ADMIN, self::PERMISSION_MOD, self::PERMISSION_HELPER, self::PERMISSION_BUILDER, self::PERMISSION_CONTENT_CREATOR, self::PERMISSION_VIPPL, self::PERMISSION_VIP, self::PERMISSION_NONE];

	private static array $ranks = [];
	private static array $bypass = [];
	private static ?Rank $defaultRank = null;

	public static function initialize() : void{
		DatabaseManager::getMainDatabase()->executeImplRaw([0 => "SELECT * FROM RanksData"], [0 => []], [0 => SqlThread::MODE_SELECT], function(array $rows){
			$ranks = [];
			foreach($rows[0]->getRows() as $rank){
				if(isset($rank["name"], $rank["format"], $rank["color"], $rank["permission"])){
					$ranks[$rank["name"]] = new Rank($rank["name"], $rank["format"], $rank["color"], $rank["permission"]);
					if((bool) $rank["isdefault"] === true){
						self::$defaultRank = $ranks[$rank["name"]];
					}
				}
			}
			self::$ranks = $ranks;
			self::$bypass = ["Owner" => true, "Dev" => true, "Admin" => true, "HeadMod" => true, "Mod" => true, "Helper" => true, "Builder" => true, "Designer" => true, "Famous" => true, "Media" => true, "MvpPlus" => true, "Vip" => true, "Host" => true];
			PermissionHandler::initialize();
			PlayerManager::initialize();
			HologramHandler::initialize();
		}, null);
	}

	public static function createRank(string $name, string $format, string $color, string $permission = self::PERMISSION_NONE) : bool{
		if(!isset(self::$ranks[$name])){
			self::$ranks[$name] = new Rank($name, $format, $color, $permission);
			if(self::$defaultRank === null){
				self::$defaultRank = self::$ranks[$name];
			}
			$isdefault = self::getDefaultRank()->getName() === $name;
			DatabaseManager::getMainDatabase()->executeImplRaw([0 => "INSERT INTO RanksData (name, format, color, permission, isdefault) VALUES ('$name', '$format', '$color', '$permission', '$isdefault') ON DUPLICATE KEY UPDATE format = '$format', color = '$color', permission = '$permission', isdefault = '$isdefault'"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);
			return true;
		}
		return false;
	}

	public static function editRank(string $name, string $format, string $color, string $permission = self::PERMISSION_NONE) : void{
		self::$ranks[$name] = new Rank($name, $format, $color, $permission);
		$isdefault = self::getDefaultRank()->getName() === $name;
		DatabaseManager::getMainDatabase()->executeImplRaw([0 => "INSERT INTO RanksData (name, format, color, permission, isdefault) VALUES ('$name', '$format', '$color', '$permission', '$isdefault') ON DUPLICATE KEY UPDATE format = '$format', color = '$color', permission = '$permission', isdefault = '$isdefault'"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);
		foreach(PlayerManager::getAllSessions() as $session){
			if($session->getRankInfo()->getRank()->getName() === $name){
				$session->updateNameTag();
			}
		}
	}

	public static function removeRank(string $name) : bool{
		$removed = false;
		if(isset(self::$ranks[$name])){
			if(self::$defaultRank !== null && self::$defaultRank->getName() === $name){
				self::$defaultRank = null;
			}
			foreach(PlayerManager::getAllSessions() as $session){
				$session->getRankInfo()->removeRank($name);
			}
			DatabaseManager::getMainDatabase()->executeImplRaw([0 => "DELETE FROM RanksData WHERE name = '$name'"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);
			unset(self::$ranks[$name]);
			$removed = true;
		}
		return $removed;
	}

	public static function getRank(string $name) : ?Rank{
		if(isset(self::$ranks[$name])){
			return self::$ranks[$name];
		}
		foreach(self::$ranks as $rank){
			$rankName = $rank->getName();
			if($rankName === $name){
				return $rank;
			}
		}
		return null;
	}

	public static function getRanks(bool $asString = true) : array{
		if($asString){
			$ranks = [];
			foreach(self::$ranks as $rank){
				$ranks[] = $rank->getName();
			}
			return $ranks;
		}
		return self::$ranks;
	}

	public static function getBypassAbleRanks() : array{
		return array_keys(self::$bypass);
	}

	public static function isBypassAbleRank(string $rank) : bool{
		return isset(self::$bypass[$rank]);
	}

	public static function formatRanksForTag(Player $player) : string{
		if(($session = PlayerManager::getSession($player)) !== null){
			$color = $session->getRankInfo()->getRank()->getColor();
			$tag = "";
			if($session->getItemInfo()->getTag() !== ""){
				$tag = $session->getItemInfo()->getTag() . " ";
			}
			if($session->isInParty() && ($partyDuel = PartyDuelHandler::getDuel($session->getParty())) !== null && ($team = $partyDuel->getTeam($player)) !== null){
				$color = $team->getTeamColor();
			}elseif($session->isInBlockIn() && ($blockIn = $session->getBlockIn()) !== null && ($team = $blockIn->getTeam($player)) !== null){
				$color = $team->getTeamColor();
			}elseif($session->getDisguiseInfo()->isDisguised()){
				$color = self::getDefaultRank()->getColor();
			}
			return ($session->isFrozen() ? TextFormat::AQUA . "[F] " : "") . (VanishHandler::isVanish($player) ? TextFormat::GREEN . "[V] " : "") . $tag . $color . $player->getDisplayName();
		}
		return $player->getDisplayName();
	}

	public static function getDefaultRank() : Rank{
		if(self::$defaultRank === null){
			self::createRank("Player", TextFormat::GRAY . "Player", TextFormat::GRAY);
			return self::getRank("Player");
		}
		return self::$defaultRank;
	}

	public static function formatRanksForChat(Player $player) : string{
		if(($session = PlayerManager::getSession($player)) !== null){
			$format = $session->getRankInfo()->getRank()->getFormat();
			$color = $session->getRankInfo()->getRank()->getColor();
			$tag = "";
			if($session->getItemInfo()->getTag() !== ""){
				$tag = TextFormat::GRAY . " [" . $session->getItemInfo()->getTag() . TextFormat::GRAY . "]";
			}
			if($session->getDisguiseInfo()->isDisguised()){
				$format = self::getDefaultRank()->getFormat();
				$color = self::getDefaultRank()->getColor();
			}
			return $format . " " . $color . $player->getDisplayName() . $tag;
		}
		return $player->getDisplayName();
	}

	public static function formatStaffForChat(Player $player) : string{
		return TextFormat::RED . TextFormat::BOLD . "STAFF" . TextFormat::RESET . TextFormat::RED . " " . $player->getName();
	}
}
