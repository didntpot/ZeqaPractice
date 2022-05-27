<?php

declare(strict_types=1);

namespace zodiax\player\info\duel;

use zodiax\player\info\duel\data\PlayerReplayData;
use zodiax\player\info\duel\data\WorldReplayData;

class ReplayInfo{

	private PlayerReplayData $playerAData;
	private PlayerReplayData $playerBData;
	private WorldReplayData $worldData;
	private int $endTick;
	private string $arena;
	private string $kit;
	private bool $ranked;

	public function __construct(int $endTick, PlayerReplayData $p1Data, PlayerReplayData $p2Data, WorldReplayData $worldData, string $kit, string $arena, bool $ranked){
		$this->endTick = $endTick;
		$this->playerAData = $p1Data;
		$this->playerBData = $p2Data;
		$this->worldData = $worldData;
		$this->arena = $arena;
		$this->kit = $kit;
		$this->ranked = $ranked;
	}

	public function getPlayerAData() : PlayerReplayData{
		return $this->playerAData;
	}

	public function getPlayerBData() : PlayerReplayData{
		return $this->playerBData;
	}

	public function getWorldData() : WorldReplayData{
		return $this->worldData;
	}

	public function getEndTick() : int{
		return $this->endTick;
	}

	public function getArena() : string{
		return $this->arena;
	}

	public function getKit() : string{
		return $this->kit;
	}

	public function isRanked() : bool{
		return $this->ranked;
	}
}
