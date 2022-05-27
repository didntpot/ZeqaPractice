<?php

declare(strict_types=1);

namespace zodiax\player\info;

use Closure;
use DateTime;
use poggit\libasynql\SqlThread;
use zodiax\data\database\DatabaseManager;
use function date_create_from_format;
use function date_format;
use function time;

class DurationInfo{

	private string $lastvoted = "0";
	private string $lastdonated = "0";
	private string $lasthosted = "0";
	private string $lastmuted = "0";
	private int $lastplayed = 0;
	private int $totalonline = 0;
	private int $warned = 0;

	public function init(array $data) : void{
		$this->lastvoted = $data["lastvoted"] ?? "0";
		$this->lastdonated = $data["lastdonated"] ?? "0";
		$this->lasthosted = $data["lasthosted"] ?? "0";
		$this->lastmuted = $data["lastmuted"] ?? "0";
		$this->lastplayed = time();
		$this->totalonline = (int) ($data["totalonline"] ?? 0);
		$this->warned = (int) ($data["warned"] ?? 0);
	}

	public function setVoted($voted) : void{
		$this->lastvoted = $voted;
	}

	public function isVoteExpired() : bool{
		if($this->lastvoted === "0"){
			return false;
		}
		$now = new DateTime("NOW");
		$expire = date_create_from_format("Y-m-d-H-i", $this->lastvoted);
		if($expire instanceof DateTime && $expire < $now){
			$this->lastvoted = "0";
			return true;
		}
		return false;
	}

	public function setDonated($donated) : void{
		$this->lastdonated = $donated;
	}

	public function isDonateExpired() : bool{
		if($this->lastdonated === "0"){
			return true;
		}
		$now = new DateTime("NOW");
		$expire = date_create_from_format("Y-m-d-H-i", $this->lastdonated);
		if($expire instanceof DateTime && $expire < $now){
			$this->lastdonated = "0";
			return true;
		}
		return false;
	}

	public function setHosted($hosted) : void{
		$this->lasthosted = $hosted;
	}

	public function getHosted() : string{
		return $this->lasthosted;
	}

	public function isHostExpired() : bool{
		if($this->lasthosted === "0"){
			return true;
		}
		$now = new DateTime("NOW");
		$expire = date_create_from_format("Y-m-d-H-i", $this->lasthosted);
		if($expire instanceof DateTime && $expire < $now){
			$this->lasthosted = "0";
			return true;
		}
		return false;
	}

	public function setMuted($muted) : void{
		$this->lastmuted = $muted;
	}

	public function isMuted() : bool{
		if($this->lastmuted === "0"){
			return false;
		}
		if($this->lastmuted === "-1"){
			return true;
		}
		$now = new DateTime("NOW");
		$expire = date_create_from_format("Y-m-d-H-i", $this->lastmuted);
		if($expire instanceof DateTime && $expire < $now){
			$this->lastmuted = "0";
			return false;
		}
		return true;
	}

	public function addWarnedCount() : void{
		$this->warned += 1;
	}

	public function resetWarnedCount() : void{
		$this->warned = 0;
	}

	public function getWarnedCount() : int{
		return $this->warned;
	}

	public function save(string $xuid, string $name, Closure $closure) : void{
		$lastplayed = date_format(new DateTime("NOW"), "Y-m-d-H-i");
		$totalonline = ($this->totalonline + (time() - $this->lastplayed));
		DatabaseManager::getMainDatabase()->executeImplRaw([0 => "INSERT INTO PlayerDuration (xuid, name, lastvoted, lastdonated, lasthosted, lastmuted, lastplayed, totalonline, warned) VALUES ('$xuid', '$name', '$this->lastvoted', '$this->lastdonated', '$this->lasthosted', '$this->lastmuted', '$lastplayed', '$totalonline', '$this->warned') ON DUPLICATE KEY UPDATE name = '$name', lastvoted = '$this->lastvoted', lastdonated = '$this->lastdonated', lasthosted = '$this->lasthosted', lastmuted = '$this->lastmuted', lastplayed = '$lastplayed', totalonline = '$totalonline', warned = '$this->warned'"], [0 => []], [0 => SqlThread::MODE_GENERIC], $closure, $closure);
	}
}