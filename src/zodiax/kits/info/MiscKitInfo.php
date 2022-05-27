<?php

declare(strict_types=1);

namespace zodiax\kits\info;

use function is_array;

class MiscKitInfo{

	private bool $ffaEnabled;
	private bool $duelsEnabled;
	private bool $replaysEnabled;
	private bool $botEnabled;
	private bool $eventEnabled;
	private bool $trainingEnabled;
	private bool $damagePlayers;
	private bool $canBuild;
	private string $texture;

	public function __construct(bool $ffaEnabled = false, bool $duelsEnabled = false, bool $replaysEnabled = false, bool $botEnabled = false, bool $eventEnabled = false, bool $trainingEnabled = false, bool $damagePlayers = false, bool $canBuild = false, string $texture = ""){
		$this->ffaEnabled = $ffaEnabled;
		$this->duelsEnabled = $duelsEnabled;
		$this->replaysEnabled = $replaysEnabled;
		$this->botEnabled = $botEnabled;
		$this->eventEnabled = $eventEnabled;
		$this->trainingEnabled = $trainingEnabled;
		$this->damagePlayers = $damagePlayers;
		$this->canBuild = $canBuild;
		$this->texture = $texture;
	}

	public static function decode($data) : MiscKitInfo{
		if(is_array($data) && isset($data["ffa"], $data["duel"], $data["replay"], $data["bot"], $data["event"], $data["training"], $data["damage"], $data["build"], $data["texture"])){
			return new MiscKitInfo($data["ffa"], $data["duel"], $data["replay"], $data["bot"], $data["event"], $data["training"], $data["damage"], $data["build"], $data["texture"]);
		}
		return new MiscKitInfo();
	}

	public function isFFAEnabled() : bool{
		return $this->ffaEnabled;
	}

	public function setFFAEnabled(bool $enable) : void{
		$this->ffaEnabled = $enable;
	}

	public function isDuelsEnabled() : bool{
		return $this->duelsEnabled;
	}

	public function setDuelsEnabled(bool $enable) : void{
		$this->duelsEnabled = $enable;
	}

	public function isReplaysEnabled() : bool{
		return $this->replaysEnabled && $this->duelsEnabled;
	}

	public function setReplaysEnabled(bool $enable) : void{
		$this->replaysEnabled = $enable;
	}

	public function isBotEnabled() : bool{
		return $this->botEnabled;
	}

	public function setBotEnabled(bool $enable) : void{
		$this->botEnabled = $enable;
	}

	public function isEventEnabled() : bool{
		return $this->eventEnabled;
	}

	public function setEventEnabled(bool $enable) : void{
		$this->eventEnabled = $enable;
	}

	public function isTrainingEnabled() : bool{
		return $this->trainingEnabled;
	}

	public function setTrainingEnabled(bool $enable) : void{
		$this->trainingEnabled = $enable;
	}

	public function canDamagePlayers() : bool{
		return $this->damagePlayers;
	}

	public function setDamageEnabled(bool $enable) : void{
		$this->damagePlayers = $enable;
	}

	public function canBuild() : bool{
		return $this->canBuild;
	}

	public function setBuild(bool $enable) : void{
		$this->canBuild = $enable;
	}

	public function hasTexture() : bool{
		return $this->texture !== "";
	}

	public function getTexture() : string{
		return $this->texture;
	}

	public function setTexture(string $texture = "") : void{
		$this->texture = $texture;
	}

	public function export() : array{
		return ["ffa" => $this->ffaEnabled, "duel" => $this->duelsEnabled, "replay" => $this->replaysEnabled, "bot" => $this->botEnabled, "event" => $this->eventEnabled, "training" => $this->trainingEnabled, "damage" => $this->damagePlayers, "build" => $this->canBuild, "texture" => $this->texture];
	}
}