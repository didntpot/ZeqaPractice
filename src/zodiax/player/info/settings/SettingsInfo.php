<?php

declare(strict_types=1);

namespace zodiax\player\info\settings;

use Closure;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlThread;
use zodiax\data\database\DatabaseManager;
use zodiax\player\info\client\ClientInfo;
use zodiax\player\misc\PlayerTrait;
use zodiax\player\misc\SettingsHandler;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;

class SettingsInfo{
	use PlayerTrait;

	private bool $scoreboard = true;
	private bool $fairqueue = false;
	private bool $pingrange = false;
	private bool $cpspopup = true;
	private bool $arenarespawn = false;
	private bool $morecrit = false;
	private bool $smoothpearl = false;
	private bool $blood = false;
	private bool $lightning = false;
	private bool $autorecycle = false;
	private bool $hidenonopponents = false;
	private bool $devicedisplay = true;
	private bool $cpsdisplay = true;
	private bool $pingdisplay = true;
	private bool $autosprint = false;
	private bool $silentstaff = false;
	private BuilderModeInfo $builderModeInfo;

	public function __construct(Player $player){
		$this->player = $player->getName();
		$this->builderModeInfo = new BuilderModeInfo($player);
	}

	public function init(array $data) : void{
		$this->scoreboard = (bool) ($data["scoreboard"] ?? true);
		$this->fairqueue = (bool) ($data["fairqueue"] ?? false);
		$this->pingrange = (bool) ($data["pingrange"] ?? false);
		$this->cpspopup = (bool) ($data["cpspopup"] ?? true);
		$this->arenarespawn = (bool) ($data["arenarespawn"] ?? false);
		$this->morecrit = (bool) ($data["morecrit"] ?? false);
		$this->smoothpearl = (bool) ($data["smoothpearl"] ?? false);
		$this->blood = (bool) ($data["blood"] ?? false);
		$this->lightning = (bool) ($data["lightning"] ?? false);
		$this->autorecycle = (bool) ($data["autorecycle"] ?? false);
		$this->hidenonopponents = (bool) ($data["hidenonopponents"] ?? false);
		$this->devicedisplay = (bool) ($data["devicedisplay"] ?? true);
		$this->cpsdisplay = (bool) ($data["cpsdisplay"] ?? true);
		$this->pingdisplay = (bool) ($data["pingdisplay"] ?? true);
		$this->autosprint = (bool) ($data["autosprint"] ?? false);
		$this->silentstaff = (bool) ($data["silentstaff"] ?? false);
		$this->builderModeInfo->init();
		if(($player = $this->getPlayer()) !== null){
			SettingsHandler::addOrRemoveFromCache($player, SettingsHandler::DEVICE, $this->devicedisplay);
			SettingsHandler::addOrRemoveFromCache($player, SettingsHandler::NO_DEVICE, !$this->devicedisplay);
			if($this->devicedisplay){
				foreach(PlayerManager::getAllSessions() as $session){
					if($session->isDefaultTag()){
						/** @var ClientInfo $clientInfo */
						$clientInfo = $session->getClientInfo();
						$session->getPlayer()->sendData([$player], [EntityMetadataProperties::SCORE_TAG => new StringMetadataProperty(PracticeCore::COLOR . $clientInfo->getDeviceOS(true, PracticeCore::isPackEnable()) . TextFormat::GRAY . " | " . TextFormat::WHITE . $clientInfo->getInputAtLogin(true))]);
					}
				}
			}
			if($this->cpsdisplay && $this->pingdisplay){
				SettingsHandler::addOrRemoveFromCache($player, SettingsHandler::CPS, false);
				SettingsHandler::addOrRemoveFromCache($player, SettingsHandler::PING, false);
				SettingsHandler::addOrRemoveFromCache($player, SettingsHandler::CPS_PING, true);
			}else{
				SettingsHandler::addOrRemoveFromCache($player, SettingsHandler::CPS_PING, false);
				SettingsHandler::addOrRemoveFromCache($player, SettingsHandler::CPS, $this->cpsdisplay);
				SettingsHandler::addOrRemoveFromCache($player, SettingsHandler::PING, $this->pingdisplay);
			}
		}
	}

	public function getBuilderModeInfo() : ?BuilderModeInfo{
		return $this->builderModeInfo;
	}

	public function isScoreboard() : bool{
		return $this->scoreboard;
	}

	public function setScoreboard(bool $newSet) : void{
		$this->scoreboard = $newSet;
	}

	public function isFairQueue() : bool{
		return $this->fairqueue;
	}

	public function setFairQueue(bool $newSet) : void{
		$this->fairqueue = $newSet;
	}

	public function isPingRange() : bool{
		return $this->pingrange;
	}

	public function setPingRange(bool $newSet) : void{
		$this->pingrange = $newSet;
	}

	public function isCpsPopup() : bool{
		return $this->cpspopup;
	}

	public function setCpsPopup(bool $newSet) : void{
		$this->cpspopup = $newSet;
	}

	public function isArenaRespawn() : bool{
		return $this->arenarespawn;
	}

	public function setArenaRespawn(bool $newSet) : void{
		$this->arenarespawn = $newSet;
	}

	public function isMoreCritical() : bool{
		return $this->morecrit;
	}

	public function setMoreCritical(bool $newSet) : void{
		$this->morecrit = $newSet;
	}

	public function isSmoothPearl() : bool{
		return $this->smoothpearl;
	}

	public function setSmoothPearl(bool $newSet) : void{
		$this->smoothpearl = $newSet;
	}

	public function isBlood() : bool{
		return $this->blood;
	}

	public function setBlood(bool $newSet) : void{
		$this->blood = $newSet;
	}

	public function isLightning() : bool{
		return $this->lightning;
	}

	public function setLightning(bool $newSet) : void{
		$this->lightning = $newSet;
	}

	public function isAutoRecycle() : bool{
		return $this->autorecycle;
	}

	public function setAutoRecycle(bool $newSet) : void{
		$this->autorecycle = $newSet;
	}

	public function isHideNonOpponents() : bool{
		return $this->hidenonopponents;
	}

	public function setHideNonOpponents(bool $newSet) : void{
		$this->hidenonopponents = $newSet;
	}

	public function isDeviceDisplay() : bool{
		return $this->devicedisplay;
	}

	public function setDeviceDisplay(bool $newSet) : void{
		$this->devicedisplay = $newSet;
	}

	public function isCpsDisplay() : bool{
		return $this->cpsdisplay;
	}

	public function setCpsDisplay(bool $newSet) : void{
		$this->cpsdisplay = $newSet;
	}

	public function isPingDisplay() : bool{
		return $this->pingdisplay;
	}

	public function setPingDisplay(bool $newSet) : void{
		$this->pingdisplay = $newSet;
	}

	public function isAutoSprint() : bool{
		return $this->autosprint;
	}

	public function setAutoSprint(bool $newSet) : void{
		$this->autosprint = $newSet;
	}

	public function isSilentStaff() : bool{
		return $this->silentstaff;
	}

	public function setSilentStaff(bool $newSet) : void{
		$this->silentstaff = $newSet;
	}

	public function save(string $xuid, string $name, string $disguise, Closure $closure) : void{
		DatabaseManager::getMainDatabase()->executeImplRaw([0 => "INSERT INTO PlayerSettings (xuid, name, disguise, scoreboard, fairqueue, pingrange, cpspopup, arenarespawn, morecrit, smoothpearl, blood, lightning, autorecycle, hidenonopponents, devicedisplay, cpsdisplay, pingdisplay, autosprint, silentstaff) VALUES ('$xuid', '$name', '$disguise', '$this->scoreboard', '$this->fairqueue', '$this->pingrange', '$this->cpspopup', '$this->arenarespawn', '$this->morecrit', '$this->smoothpearl', '$this->blood', '$this->lightning', '$this->autorecycle', '$this->hidenonopponents', '$this->devicedisplay', '$this->cpsdisplay', '$this->pingdisplay', '$this->autosprint', '$this->silentstaff') ON DUPLICATE KEY UPDATE name = '$name', disguise = '$disguise', scoreboard = '$this->scoreboard', fairqueue = '$this->fairqueue', pingrange = '$this->pingrange', cpspopup = '$this->cpspopup', arenarespawn = '$this->arenarespawn', morecrit = '$this->morecrit', smoothpearl = '$this->smoothpearl', blood = '$this->blood', lightning = '$this->lightning', autorecycle = '$this->autorecycle', hidenonopponents = '$this->hidenonopponents', devicedisplay = '$this->devicedisplay', cpsdisplay = '$this->cpsdisplay', pingdisplay = '$this->pingdisplay', autosprint = '$this->autosprint', silentstaff = '$this->silentstaff'"], [0 => []], [0 => SqlThread::MODE_GENERIC], $closure, $closure);
	}
}