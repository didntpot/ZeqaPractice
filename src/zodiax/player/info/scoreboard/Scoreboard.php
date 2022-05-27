<?php

declare(strict_types=1);

namespace zodiax\player\info\scoreboard;

use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use zodiax\player\misc\PlayerTrait;

class Scoreboard{
	use PlayerTrait;

	private const SORT_ASCENDING = 0;
	private const SLOT_SIDEBAR = "sidebar";

	private array $lines;

	public function __construct(string $player, string $title){
		$this->player = $player;
		$this->lines = [];
		$this->initScoreboard($title);
	}

	private function initScoreboard(string $title) : void{
		if(($player = $this->getPlayer()) !== null){
			$pkt = new SetDisplayObjectivePacket();
			$pkt->objectiveName = $this->player;
			$pkt->displayName = $title;
			$pkt->sortOrder = self::SORT_ASCENDING;
			$pkt->displaySlot = self::SLOT_SIDEBAR;
			$pkt->criteriaName = "dummy";
			$player->getNetworkSession()->sendDataPacket($pkt);
		}
	}

	public function clearScoreboard() : void{
		if(($player = $this->getPlayer()) !== null){
			$pkt = new SetScorePacket();
			$pkt->entries = $this->lines;
			$pkt->type = SetScorePacket::TYPE_REMOVE;
			$this->lines = [];
			$player->getNetworkSession()->sendDataPacket($pkt);
		}
	}

	public function addLine(int $id, string $line) : void{
		$this->removeLine($id);
		if(($player = $this->getPlayer()) !== null){
			$entry = new ScorePacketEntry();
			$entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;
			$entry->score = $id;
			$entry->scoreboardId = $id;
			$entry->actorUniqueId = $player->getId();
			$entry->objectiveName = $this->player;
			$entry->customName = $line;
			$this->lines[$id] = $entry;
			$pkt = new SetScorePacket();
			$pkt->entries[] = $entry;
			$pkt->type = SetScorePacket::TYPE_CHANGE;
			$player->getNetworkSession()->sendDataPacket($pkt);
		}
	}

	public function removeLine(int $id) : void{
		if(($player = $this->getPlayer()) !== null && isset($this->lines[$id])){
			$line = $this->lines[$id];
			$packet = new SetScorePacket();
			$packet->entries[] = $line;
			$packet->type = SetScorePacket::TYPE_REMOVE;
			$player->getNetworkSession()->sendDataPacket($packet);
			unset($this->lines[$id]);
		}
	}

	public function removeScoreboard() : void{
		if(($player = $this->getPlayer()) !== null){
			$packet = new RemoveObjectivePacket();
			$packet->objectiveName = $this->player;
			$player->getNetworkSession()->sendDataPacket($packet);
		}
	}
}