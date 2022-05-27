<?php

declare(strict_types=1);

namespace zodiax\player\info;

use poggit\libasynql\SqlThread;
use zodiax\data\database\DatabaseManager;

class StaffStatsInfo{

	private int $bans = 0;
	private int $kicks = 0;
	private int $mutes = 0;
	private int $tickets = 0;
	private int $reports = 0;

	public function init(array $data) : void{
		$this->bans = (int) $data["bans"] ?? 0;
		$this->kicks = (int) $data["kicks"] ?? 0;
		$this->mutes = (int) $data["mutes"] ?? 0;
		$this->tickets = (int) $data["tickets"] ?? 0;
		$this->reports = (int) $data["reports"] ?? 0;
	}

	public function addBan() : void{
		$this->bans++;
	}

	public function addKick() : void{
		$this->kicks++;
	}

	public function addMute() : void{
		$this->mutes++;
	}

	public function save(string $xuid, string $name) : void{
		DatabaseManager::getMainDatabase()->executeImplRaw([0 => "INSERT INTO StaffStats (xuid, name, bans, kicks, mutes, tickets, reports) VALUES ('$xuid', '$name', '$this->bans', '$this->kicks', '$this->mutes', '$this->tickets', '$this->reports') ON DUPLICATE KEY UPDATE name = '$name', bans = '$this->bans', kicks = '$this->kicks', mutes = '$this->mutes', tickets = '$this->tickets', reports = '$this->reports'"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);
	}
}