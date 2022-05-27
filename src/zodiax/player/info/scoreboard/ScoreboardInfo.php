<?php

declare(strict_types=1);

namespace zodiax\player\info\scoreboard;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\arena\ArenaManager;
use zodiax\duel\BotHandler;
use zodiax\duel\DuelHandler;
use zodiax\duel\ReplayHandler;
use zodiax\event\EventDuel;
use zodiax\event\EventHandler;
use zodiax\game\entity\CombatBot;
use zodiax\party\duel\PartyDuelHandler;
use zodiax\player\misc\PlayerTrait;
use zodiax\player\misc\queue\QueueHandler;
use zodiax\player\PlayerManager;
use zodiax\player\PracticePlayer;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\training\TrainingHandler;
use function array_sum;
use function count;
use function str_replace;
use function strlen;

class ScoreboardInfo{
	use PlayerTrait;

	const SCOREBOARD_LOBBY = "lobby";
	const SCOREBOARD_SPAWN = "spawn";
	const SCOREBOARD_DUEL = "duel";
	const SCOREBOARD_FFA = "ffa";
	const SCOREBOARD_EVENT = "event";
	const SCOREBOARD_NONE = "none";
	const SCOREBOARD_SPECTATOR = "spec";
	const SCOREBOARD_REPLAY = "replay";
	const SCOREBOARD_PARTY = "party";

	private ?Scoreboard $scoreboard = null;
	private string $scoreboardType;
	private string $seperator;
	private int $pixelWidth;
	private int $currentQueueLine = -1;
	private int $currentPingLine = -1;
	private int $currentOpponentPingLine = -1;
	private int $currentDurationLine = -1;
	private int $currentMemberLine = -1;

	public function __construct(Player $player){
		$this->player = $player->getName();
		$this->scoreboardType = self::SCOREBOARD_NONE;
		$this->seperator = PracticeCore::isPackEnable() ? "" : TextFormat::DARK_GRAY . " ------------------";
		$this->pixelWidth = PracticeCore::isPackEnable() ? 110 : PracticeUtil::getPixelLength($this->seperator);
	}

	public function getScoreboardType() : string{
		return $this->scoreboardType;
	}

	public function setScoreboard(string $scoreboardType) : void{
		if(($session = $this->getSession()) !== null && $session->getSettingsInfo()->isScoreboard()){
			$this->scoreboard = $this->scoreboard ?? new Scoreboard($this->player, TextFormat::BOLD . PracticeCore::COLOR . (PracticeCore::isLobby() ? PracticeCore::NAME : PracticeCore::getRegionInfo()) . TextFormat::WHITE . " Practice");
			$this->scoreboard->clearScoreboard();
			$this->scoreboardType = self::SCOREBOARD_NONE;
			$this->currentQueueLine = -1;
			$this->currentPingLine = -1;
			$this->currentOpponentPingLine = -1;
			$this->currentDurationLine = -1;
			$this->currentMemberLine = -1;
			switch($scoreboardType){
				case self::SCOREBOARD_NONE:
					$this->setNoScoreboard($session);
					break;
				case self::SCOREBOARD_LOBBY:
					$this->setLobbyScoreboard($session);
					break;
				case self::SCOREBOARD_SPAWN:
					$this->setSpawnScoreboard($session);
					break;
				case self::SCOREBOARD_DUEL:
					$this->setDuelScoreboard($session);
					break;
				case self::SCOREBOARD_FFA:
					$this->setFFAScoreboard($session);
					break;
				case self::SCOREBOARD_EVENT:
					$this->setEventScoreboard($session);
					break;
				case self::SCOREBOARD_SPECTATOR:
					$this->setSpectatorScoreboard($session);
					break;
				case self::SCOREBOARD_REPLAY:
					$this->setReplayScoreboard($session);
					break;
				case self::SCOREBOARD_PARTY:
					$this->setPartyScoreboard($session);
					break;
			}
		}
	}

	private function setNoScoreboard(PracticePlayer $session) : void{
		$this->scoreboard?->removeScoreboard();
		$this->scoreboard = null;
		$this->scoreboardType = self::SCOREBOARD_NONE;
	}

	private function setLobbyScoreboard(PracticePlayer $session) : void{
		$regions = [];
		foreach(QueueHandler::getQueryResults() as $region => $server){
			$regions[$region] = 0;
			foreach($server as $data){
				$regions[$region] += $data["players"];
			}
		}
		$i = 0;
		$this->scoreboard->addLine($i++, $this->seperator);
		$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Online: " . TextFormat::WHITE . count(PlayerManager::getOnlinePlayers()) + array_sum($regions));
		$this->scoreboard->addLine($i++, "");
		foreach($regions as $region => $players){
			$this->scoreboard->addLine($i++, PracticeCore::COLOR . " $region: " . TextFormat::WHITE . $players);
		}
		$this->scoreboard->addLine($i++, $this->seperator . TextFormat::RESET);
		if(!empty($queue = QueueHandler::getQueueOf($this->getPlayer()))){
			$this->currentQueueLine = $i;
			$this->addQueueToLobbyScoreboard($queue["server"], $queue["queue"]);
		}else{
			$this->scoreboard->addLine($i, PracticeUtil::centerText(PracticeCore::COLOR . "Zeqa.net", $this->pixelWidth, true));
		}
		$this->scoreboardType = self::SCOREBOARD_LOBBY;
	}

	public function updateOnlinePlayersToLobbyScoreboard(array $regions) : void{
		if($this->scoreboardType === self::SCOREBOARD_LOBBY){
			$i = 3;
			foreach($regions as $region => $players){
				$this->scoreboard->addLine($i++, PracticeCore::COLOR . " $region: " . TextFormat::WHITE . $players);
			}
		}
	}

	public function addQueueToLobbyScoreboard(string $name, int $queue) : void{
		if($this->scoreboardType === self::SCOREBOARD_LOBBY){
			if($this->currentQueueLine === -1){
				$this->currentQueueLine = 3 + count(QueueHandler::getQueryResults());
			}
			$this->scoreboard->addLine($this->currentQueueLine, "  ");
			$this->scoreboard->addLine($this->currentQueueLine + 1, PracticeCore::COLOR . " Queue:");
			$this->scoreboard->addLine($this->currentQueueLine + 2, TextFormat::WHITE . " $name #$queue");
			$this->scoreboard->addLine($this->currentQueueLine + 3, $this->seperator . TextFormat::RESET);
			$this->scoreboard->addLine($this->currentQueueLine + 4, PracticeUtil::centerText(PracticeCore::COLOR . "Zeqa.net", $this->pixelWidth, true));
		}
	}

	private function setSpawnScoreboard(PracticePlayer $session) : void{
		$i = 0;
		$this->scoreboard->addLine($i++, $this->seperator);
		$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Online: " . TextFormat::WHITE . count(PlayerManager::getOnlinePlayers()));
		$this->scoreboard->addLine($i++, "");
		$this->scoreboard->addLine($i++, PracticeCore::COLOR . " K: " . TextFormat::WHITE . $session->getStatsInfo()->getKills() . PracticeCore::COLOR . " D: " . TextFormat::WHITE . $session->getStatsInfo()->getDeaths());
		$this->scoreboard->addLine($i++, PracticeCore::COLOR . " KDR: " . TextFormat::WHITE . $session->getStatsInfo()->getKDR() . PracticeCore::COLOR . " Elo: " . TextFormat::WHITE . $session->getEloInfo()->getEloFromKit());
		$this->scoreboard->addLine($i++, " ");
		$this->scoreboard->addLine($i++, PracticeCore::COLOR . " In-Fights: " . TextFormat::WHITE . DuelHandler::getDuels(true) * 2);
		$this->scoreboard->addLine($i++, PracticeCore::COLOR . " In-Queues: " . TextFormat::WHITE . DuelHandler::getEveryoneInQueues());
		$this->scoreboard->addLine($i++, $this->seperator . TextFormat::RESET);
		if($session->isInQueue()){
			$queue = DuelHandler::getQueueOf($this->player);
			$this->addQueueToScoreboard($queue->isRanked(), $queue->getKit());
		}elseif($session->isInBotQueue()){
			$queue = BotHandler::getQueueOf($this->player);
			$this->addBotQueueToScoreboard($queue->getMode(), $queue->getKit());
		}else{
			$this->scoreboard->addLine($i, PracticeUtil::centerText(PracticeCore::COLOR . "Zeqa.net", $this->pixelWidth, true));
		}
		$this->scoreboardType = self::SCOREBOARD_SPAWN;
	}

	public function addQueueToScoreboard(bool $ranked, string $kit) : void{
		if($this->scoreboardType === self::SCOREBOARD_SPAWN){
			$this->scoreboard->addLine(8, "  ");
			$this->scoreboard->addLine(9, PracticeCore::COLOR . " Queue:");
			$this->scoreboard->addLine(10, TextFormat::WHITE . ($ranked ? " Ranked " : " Unranked ") . TextFormat::WHITE . $kit);
			$this->scoreboard->addLine(11, $this->seperator . TextFormat::RESET);
			$this->scoreboard->addLine(12, PracticeUtil::centerText(PracticeCore::COLOR . "Zeqa.net", $this->pixelWidth, true));
		}
	}

	public function addBotQueueToScoreboard(int $mode, string $kit) : void{
		if($this->scoreboardType === self::SCOREBOARD_SPAWN){
			$modeName = match ($mode) {
				CombatBot::EASY => "Easy",
				CombatBot::MEDIUM => "Medium",
				CombatBot::HARD => "Hard"
			};
			$this->scoreboard->addLine(8, "  ");
			$this->scoreboard->addLine(9, PracticeCore::COLOR . " Queue:");
			$this->scoreboard->addLine(10, TextFormat::WHITE . " $modeName " . TextFormat::WHITE . " $kit");
			$this->scoreboard->addLine(11, $this->seperator . TextFormat::RESET);
			$this->scoreboard->addLine(12, PracticeUtil::centerText(PracticeCore::COLOR . "Zeqa.net", $this->pixelWidth, true));
		}
	}

	public function removeQueueFromScoreboard() : void{
		if($this->scoreboardType === self::SCOREBOARD_SPAWN){
			$this->scoreboard->removeLine(10);
			$this->scoreboard->removeLine(11);
			$this->scoreboard->removeLine(12);
			$this->scoreboard->addLine(8, $this->seperator . TextFormat::RESET);
			$this->scoreboard->addLine(9, PracticeUtil::centerText(PracticeCore::COLOR . "Zeqa.net", $this->pixelWidth, true));
		}
	}

	private function setDuelScoreboard(PracticePlayer $session) : void{
		$showPing = true;
		$opponent = "Unknown";
		$opponentMS = -1;
		if(($duel = $session->getDuel()) !== null){
			if(($osession = PlayerManager::getSession($duel->getOpponent($this->player))) !== null){
				$opponent = $osession->getPlayer()->getDisplayName();
				$opponentMS = $osession->getPing();
			}
		}elseif(($duel = $session->getBotDuel()) !== null){
			$opponent = match ($duel->getMode()) {
				0 => "Easy Bot",
				1 => "Medium Bot",
				2 => "Hard Bot"
			};
			$opponentMS = 10;
		}elseif(($party = $session->getParty()) !== null && ($duel = $session->getPartyDuel()) !== null){
			$opponent = $duel->getOpponent($party)?->getName();
			$opponentMS = 0;
		}elseif(($duel = $session->getEvent()?->getCurrentGame()) !== null && $duel->isPlayer($this->player)){
			if(($osession = PlayerManager::getSession($duel->getOpponent($this->player))) !== null){
				$opponent = $osession->getPlayer()->getDisplayName();
				$opponentMS = $osession->getPing();
			}
		}else{
			$duel = $session->getBlockIn() ?? $session->getClutch() ?? $session->getReduce();
		}
		if($duel === null){
			return;
		}
		$name = $duel->getKit();
		$i = 0;
		$this->scoreboard->addLine($i++, $this->seperator);
		if($name === "Bridge" || $name === "BattleRush"){
			$this->scoreboard->addLine($i++, TextFormat::BLUE . " [B] " . TextFormat::GRAY . ($name === "Bridge" ? "OOOOO" : "OOO"));
			$this->scoreboard->addLine($i++, TextFormat::RED . " [R] " . TextFormat::GRAY . ($name === "Bridge" ? "OOOOO" : "OOO"));
			$this->scoreboard->addLine($i++, "");
			$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Kills: " . TextFormat::WHITE . "0");
			$this->scoreboard->addLine($this->currentDurationLine = $i++, PracticeCore::COLOR . " Duration: " . TextFormat::WHITE . "00:00");
			$this->scoreboard->addLine($i++, " ");
		}elseif($name === "MLGRush"){
			$this->scoreboard->addLine($i++, TextFormat::BLUE . " [B] " . TextFormat::GRAY . "OOOOO");
			$this->scoreboard->addLine($i++, TextFormat::RED . " [R] " . TextFormat::GRAY . "OOOOO");
			$this->scoreboard->addLine($i++, "");
			$this->scoreboard->addLine($this->currentDurationLine = $i++, PracticeCore::COLOR . " Duration: " . TextFormat::WHITE . "00:00");
			$this->scoreboard->addLine($i++, " ");
		}elseif($name === "Boxing" || $name === "StickFight"){
			$this->scoreboard->addLine($i++, PracticeCore::COLOR . ($name === "Boxing" ? " Hits: " . TextFormat::GREEN . "(+0)" : " Lives: "));
			$this->scoreboard->addLine($i++, PracticeCore::COLOR . "   You: " . TextFormat::WHITE . ($name === "Boxing" ? "0" : "5"));
			$this->scoreboard->addLine($i++, PracticeCore::COLOR . "   Them: " . TextFormat::WHITE . ($name === "Boxing" ? "0" : "5"));
			$this->scoreboard->addLine($i++, "");
		}elseif($name === "BedFight"){
			$this->scoreboard->addLine($i++, TextFormat::RED . " R " . TextFormat::WHITE . "Red: " . TextFormat::GREEN . "O");
			$this->scoreboard->addLine($i++, TextFormat::BLUE . " B " . TextFormat::WHITE . "Blue: " . TextFormat::GREEN . "O");
			$this->scoreboard->addLine($i++, "");
			$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Kills: " . TextFormat::WHITE . "0");
			$this->scoreboard->addLine($this->currentDurationLine = $i++, PracticeCore::COLOR . " Duration: " . TextFormat::WHITE . "00:00");
			$this->scoreboard->addLine($i++, " ");
		}elseif($name === "Attacker" || $name === "Defender"){
			$showPing = false;
			$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Mode: " . TextFormat::WHITE . "Block-In");
			$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Role: " . TextFormat::WHITE . ($duel->getTeam($session->getPlayer()) === $duel->getTeam1() ? "Attacker" : "Defender"));
			$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Defense: " . TextFormat::WHITE . $duel->getDefenseType(true));
			$this->scoreboard->addLine($i++, "");
			$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Time: " . TextFormat::WHITE . "0.00 Second(s)");
			$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Best: " . TextFormat::WHITE . "0.00 Second(s)");
		}elseif($name === "Clutch" || $name === "Reduce"){
			$showPing = false;
			$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Mode: " . TextFormat::WHITE . $name);
			$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Blocks: " . TextFormat::WHITE . 0);
			$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Distance: " . TextFormat::WHITE . 0);
		}else{
			$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Mode: " . TextFormat::WHITE . $name);
			$this->scoreboard->addLine($i++, "");
			if(strlen($opponent) > 10){
				$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Opponent:");
				$this->scoreboard->addLine($i++, TextFormat::WHITE . " " . $opponent);
				$this->scoreboard->addLine($i++, " ");
			}else{
				$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Opponent: " . TextFormat::WHITE . $opponent);
			}
			$this->scoreboard->addLine($this->currentDurationLine = $i++, PracticeCore::COLOR . " Duration: " . TextFormat::WHITE . "00:00");
			$this->scoreboard->addLine($i++, "  ");
		}
		if($showPing){
			$this->scoreboard->addLine($this->currentPingLine = $i++, TextFormat::GREEN . " Your Ping: " . TextFormat::WHITE . "{$session->getPing()}ms");
			if($opponentMS !== -1){
				$this->scoreboard->addLine($this->currentOpponentPingLine = $i++, TextFormat::RED . " Their Ping: " . TextFormat::WHITE . "{$opponentMS}ms");
			}
		}
		$this->scoreboard->addLine($i++, $this->seperator . TextFormat::RESET);
		$this->scoreboard->addLine($i, PracticeUtil::centerText(PracticeCore::COLOR . "Zeqa.net", $this->pixelWidth, true));
		$this->scoreboardType = self::SCOREBOARD_DUEL;
	}

	private function setFFAScoreboard(PracticePlayer $session) : void{
		$i = 0;
		$this->scoreboard->addLine($i++, $this->seperator);
		$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Arena: " . TextFormat::WHITE . $session->getArena()?->getName() ?? "");
		$this->scoreboard->addLine($i++, TextFormat::YELLOW . " Combat: " . TextFormat::WHITE . "0s");
		$this->scoreboard->addLine($i++, "");
		$this->scoreboard->addLine($this->currentPingLine = $i++, TextFormat::GREEN . " Your Ping: " . TextFormat::WHITE . "{$session->getPing()}ms");
		$this->scoreboard->addLine($this->currentOpponentPingLine = $i++, TextFormat::RED . " Their Ping: " . TextFormat::WHITE . "0ms");
		$this->scoreboard->addLine($i++, $this->seperator . TextFormat::RESET);
		$this->scoreboard->addLine($i, PracticeUtil::centerText(PracticeCore::COLOR . "Zeqa.net", $this->pixelWidth, true));
		$this->scoreboardType = self::SCOREBOARD_FFA;
	}

	private function setEventScoreboard(PracticePlayer $session) : void{
		if(($event = $session->getEvent()) !== null || ($event = EventHandler::getEventFromSpec($session->getPlayer())) !== null){
			$i = 0;
			$this->scoreboard->addLine($i++, $this->seperator);
			if(!$event->isStarted()){
				$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Starting in: " . TextFormat::WHITE . $event->getCountdown());
				$this->scoreboard->addLine($i++, "");
			}
			$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Players: " . TextFormat::WHITE . $event->getPlayers(true));
			$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Arena: " . TextFormat::WHITE . str_replace("Event", "", ArenaManager::getArena($event->getArena())->getKit()->getName()));
			$this->scoreboard->addLine($i++, " ");
			$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Eliminated: " . TextFormat::WHITE . $event->getEliminated(true));
			$this->scoreboard->addLine($i++, $this->seperator . TextFormat::RESET);
			$this->scoreboard->addLine($i, PracticeUtil::centerText(PracticeCore::COLOR . "Zeqa.net", $this->pixelWidth, true));
			$this->scoreboardType = self::SCOREBOARD_EVENT;
		}
	}

	private function setPartyScoreboard(PracticePlayer $session) : void{
		if(($party = $session->getParty()) !== null){
			$i = 0;
			$this->scoreboard->addLine($i++, $this->seperator);
			$this->scoreboard->addLine($i++, TextFormat::WHITE . " " . $party->getName());
			$this->scoreboard->addLine($i++, "");
			$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Members:");
			foreach($party->getPlayers() as $p){
				$this->scoreboard->addLine($this->currentMemberLine = $i++, TextFormat::WHITE . " " . PlayerManager::getPlayerExact($p)?->getDisplayName());
			}
			$this->scoreboard->addLine($i++, " ");
			$this->scoreboard->addLine($i++, PracticeCore::COLOR . " In-Fights: " . TextFormat::WHITE . PartyDuelHandler::getDuels(true) * 2);
			$this->scoreboard->addLine($i++, PracticeCore::COLOR . " In-Queues: " . TextFormat::WHITE . PartyDuelHandler::getEveryPartiesInQueues());
			$this->scoreboard->addLine($i++, $this->seperator . TextFormat::RESET);
			if($party->isInQueue()){
				$queue = PartyDuelHandler::getQueueOf($party);
				$this->addQueueToPartyScoreboard($queue->getSize(), $queue->getKit());
			}else{
				$this->scoreboard->addLine($i, PracticeUtil::centerText(PracticeCore::COLOR . "Zeqa.net", $this->pixelWidth, true));
			}
			$this->scoreboardType = self::SCOREBOARD_PARTY;
		}
	}

	public function addQueueToPartyScoreboard(int $size, string $kit) : void{
		if($this->scoreboardType === self::SCOREBOARD_PARTY){
			$this->scoreboard->addLine($this->currentMemberLine + 4, "  ");
			$this->scoreboard->addLine($this->currentMemberLine + 5, PracticeCore::COLOR . " Queue:");
			$this->scoreboard->addLine($this->currentMemberLine + 6, TextFormat::WHITE . " " . $size . "vs" . $size . " " . TextFormat::WHITE . $kit);
			$this->scoreboard->addLine($this->currentMemberLine + 7, $this->seperator . TextFormat::RESET);
			$this->scoreboard->addLine($this->currentMemberLine + 8, PracticeUtil::centerText(PracticeCore::COLOR . "Zeqa.net", $this->pixelWidth, true));
		}
	}

	public function removeQueueFromPartyScoreboard() : void{
		if($this->scoreboardType === self::SCOREBOARD_PARTY){
			$this->scoreboard->removeLine($this->currentMemberLine + 6);
			$this->scoreboard->removeLine($this->currentMemberLine + 7);
			$this->scoreboard->removeLine($this->currentMemberLine + 8);
			$this->scoreboard->addLine($this->currentMemberLine + 4, $this->seperator . TextFormat::RESET);
			$this->scoreboard->addLine($this->currentMemberLine + 5, PracticeUtil::centerText(PracticeCore::COLOR . "Zeqa.net", $this->pixelWidth, true));
		}
	}

	public function updateFightToPartyScoreboard(int $numInPartyFights) : void{
		if($this->scoreboardType === self::SCOREBOARD_PARTY){
			$this->updateLineOfScoreboard($this->currentMemberLine + 2, PracticeCore::COLOR . " In-Fights: " . TextFormat::WHITE . $numInPartyFights);
		}
	}

	public function updateQueueToPartyScoreboard(int $numInPartyQueues) : void{
		if($this->scoreboardType === self::SCOREBOARD_PARTY){
			$this->updateLineOfScoreboard($this->currentMemberLine + 3, PracticeCore::COLOR . " In-Queues: " . TextFormat::WHITE . $numInPartyQueues);
		}
	}

	private function setSpectatorScoreboard(PracticePlayer $session) : void{
		$player = $session->getPlayer();
		$player1 = "";
		$player2 = "";
		$kit = "";
		$type = "";
		$custom = false;
		if(($duel = DuelHandler::getDuelFromSpec($player)) !== null){
			$player1 = PlayerManager::getPlayerExact($duel->getPlayer1())?->getDisplayName();
			$player2 = PlayerManager::getPlayerExact($duel->getPlayer2())?->getDisplayName();
			$kit = $duel->getKit();
			$type = $duel->isRanked() ? "Ranked" : "Unranked";
		}elseif(($duel = BotHandler::getDuelFromSpec($player)) !== null){
			$player1 = PlayerManager::getPlayerExact($duel->getPlayer())?->getDisplayName();
			$player2 = match ($duel->getMode()) {
				0 => "Easy Bot",
				1 => "Medium Bot",
				2 => "Hard Bot"
			};
			$kit = $duel->getKit();
			$type = match ($duel->getMode()) {
				0 => "Easy",
				1 => "Medium",
				2 => "Hard"
			};
		}elseif(($duel = PartyDuelHandler::getDuelFromSpec($session->getPlayer())) !== null){
			$player1 = $duel->getParty1();
			$player2 = $duel->getParty2();
			$kit = $duel->getKit();
			$type = "{$duel->getSize()}vs{$duel->getSize()}";
		}else{
			$duel = TrainingHandler::getClutchFromSpec($player) ?? TrainingHandler::getReduceFromSpec($player) ?? TrainingHandler::getBlockInFromSpec($player);
			if($duel !== null){
				$kit = $duel->getKit();
			}
		}
		if(($arena = $session->getSpectateArena()) === null && $duel === null){
			return;
		}
		$i = 0;
		$this->scoreboard->addLine($i++, $this->seperator);
		if($arena !== null){
			$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Arena: " . TextFormat::WHITE . $arena->getName());
			$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Players: " . TextFormat::WHITE . $arena->getPlayers(true));
		}else{
			if($kit === "Bridge" || $kit === "BattleRush" || $kit === "MLGRush"){
				$this->scoreboard->addLine($i++, TextFormat::BLUE . " [B] " . TextFormat::GRAY . ($kit === "BattleRush" ? "OOO" : "OOOOO"));
				$this->scoreboard->addLine($i++, TextFormat::RED . " [R] " . TextFormat::GRAY . ($kit === "BattleRush" ? "OOO" : "OOOOO"));
			}elseif($kit === "Boxing" || $kit === "StickFight"){
				$this->scoreboard->addLine($i++, PracticeCore::COLOR . ($kit === "Boxing" ? " Hits: " : " Lives: "));
				$this->scoreboard->addLine($i++, PracticeCore::COLOR . "   $player1: " . TextFormat::WHITE . ($kit === "Boxing" ? "0" : "5"));
				$this->scoreboard->addLine($i++, PracticeCore::COLOR . "   $player2: " . TextFormat::WHITE . ($kit === "Boxing" ? "0" : "5"));
			}elseif($kit === "BedFight"){
				$this->scoreboard->addLine($i++, TextFormat::RED . " R " . TextFormat::WHITE . "Red: " . TextFormat::GREEN . "O");
				$this->scoreboard->addLine($i++, TextFormat::BLUE . " B " . TextFormat::WHITE . "Blue: " . TextFormat::GREEN . "O");
			}elseif($kit === "Attacker" || $kit === "Defender"){
				$custom = true;
				$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Mode: " . TextFormat::WHITE . "Block-In");
				$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Defense: " . TextFormat::WHITE . $duel->getDefenseType(true));
				$this->scoreboard->addLine($i++, "");
				$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Time: " . TextFormat::WHITE . "0.00 Second(s)");
				$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Best: " . TextFormat::WHITE . "0.00 Second(s)");
			}elseif($kit === "Clutch" || $kit === "Reduce"){
				$custom = true;
				$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Mode: " . TextFormat::WHITE . $kit);
				$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Blocks: " . TextFormat::WHITE . 0);
				$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Distance: " . TextFormat::WHITE . 0);
			}else{
				if((strlen($player1)) + (strlen($player1)) > 16){
					$this->scoreboard->addLine($i++, PracticeCore::COLOR . " $player1");
					$this->scoreboard->addLine($i++, TextFormat::WHITE . " vs");
					$this->scoreboard->addLine($i++, PracticeCore::COLOR . " $player2");
				}else{
					$this->scoreboard->addLine($i++, PracticeCore::COLOR . " $player1" . TextFormat::WHITE . " vs " . PracticeCore::COLOR . $player2);
				}
			}
			if(!$custom){
				$this->scoreboard->addLine($i++, "");
				$this->scoreboard->addLine($this->currentDurationLine = $i++, PracticeCore::COLOR . " Duration: " . TextFormat::WHITE . $duel->getDuration());
				$this->scoreboard->addLine($i++, " ");
				$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Mode: " . TextFormat::WHITE . $kit);
				$this->scoreboard->addLine($i++, PracticeCore::COLOR . " Type: " . TextFormat::WHITE . $type);
			}
		}
		$this->scoreboard->addLine($i++, $this->seperator . TextFormat::RESET);
		$this->scoreboard->addLine($i, PracticeUtil::centerText(PracticeCore::COLOR . "Zeqa.net", $this->pixelWidth, true));
		$this->scoreboardType = self::SCOREBOARD_SPECTATOR;
	}

	private function setReplayScoreboard(PracticePlayer $session) : void{
		if(($replay = ReplayHandler::getReplayFrom($this->player)) !== null){
			$i = 0;
			$this->scoreboard->addLine($i++, $this->seperator);
			$this->scoreboard->addLine($i++, PracticeCore::COLOR . ($replay->isRanked() ? " Ranked " : " Unranked ") . TextFormat::WHITE . $replay->getKit());
			$this->scoreboard->addLine($i++, "");
			$this->scoreboard->addLine($this->currentDurationLine = $i++, PracticeCore::COLOR . " Duration:");
			$this->scoreboard->addLine($i++, TextFormat::WHITE . " " . $replay->getDuration() . TextFormat::WHITE . " | " . $replay->getMaxDuration());
			$this->scoreboard->addLine($i++, $this->seperator . TextFormat::RESET);
			$this->scoreboard->addLine($i, PracticeUtil::centerText(PracticeCore::COLOR . "Zeqa.net", $this->pixelWidth, true));
			$this->scoreboardType = self::SCOREBOARD_REPLAY;
		}
	}

	public function addPausedToScoreboard() : void{
		if($this->scoreboardType === self::SCOREBOARD_REPLAY){
			$this->scoreboard->addLine(5, " ");
			$this->scoreboard->addLine(6, TextFormat::RED . " Paused");
			$this->scoreboard->addLine(7, $this->seperator . TextFormat::RESET);
			$this->scoreboard->addLine(8, PracticeUtil::centerText(PracticeCore::COLOR . "Zeqa.net", $this->pixelWidth, true));
		}
	}

	public function removePausedFromScoreboard() : void{
		if($this->scoreboardType === self::SCOREBOARD_REPLAY){
			$this->scoreboard->removeLine(7);
			$this->scoreboard->removeLine(8);
			$this->scoreboard->addLine(5, $this->seperator . TextFormat::RESET);
			$this->scoreboard->addLine(6, PracticeUtil::centerText(PracticeCore::COLOR . "Zeqa.net", $this->pixelWidth, true));
		}
	}

	public function updatePing(int $pingMS) : void{
		if(($session = $this->getSession()) !== null){
			$player = $session->getPlayer();
			if($this->currentPingLine !== -1){
				$this->updateLineOfScoreboard($this->currentPingLine, TextFormat::GREEN . " Your Ping: " . TextFormat::WHITE . "{$pingMS}ms");
			}
			if($this->currentOpponentPingLine !== -1){
				if(($ksession = PlayerManager::getSession($session->getTarget())) !== null){
					$ksession->getScoreboardInfo()->updatePingToOpponent($pingMS);
				}elseif(($duel = $session->getDuel()) !== null){
					if(($ksession = PlayerManager::getSession($duel->getOpponent($player))) !== null){
						$ksession->getScoreboardInfo()->updatePingToOpponent($pingMS);
					}
				}elseif($session->getPartyDuel() !== null){
					if(($ev = $player->getLastDamageCause()) !== null && $ev instanceof EntityDamageByEntityEvent && ($damager = $ev->getDamager()) !== null && $damager instanceof Player && ($ksession = PlayerManager::getSession($damager)) !== null){
						$ksession->getScoreboardInfo()->updatePingToOpponent($pingMS);
					}
				}elseif(($event = $session->getEvent()) !== null){
					$game = $event->getCurrentGame();
					if($game instanceof EventDuel && $game->isPlayer($player)){
						if(($ksession = PlayerManager::getSession($game->getOpponent($player))) !== null){
							$ksession->getScoreboardInfo()->updatePingToOpponent($pingMS);
						}
					}
				}
			}
		}
	}

	public function updatePingToOpponent(int $pingMS) : void{
		if($this->currentOpponentPingLine !== -1){
			$this->updateLineOfScoreboard($this->currentOpponentPingLine, TextFormat::RED . " Their Ping: " . TextFormat::WHITE . "{$pingMS}ms");
		}
	}

	public function updateDuration(string $duration) : void{
		if($this->currentDurationLine !== -1){
			$this->updateLineOfScoreboard($this->currentDurationLine, PracticeCore::COLOR . " Duration: " . TextFormat::WHITE . $duration);
		}
	}

	public function updateLineOfScoreboard(int $line, string $display) : void{
		if($this->scoreboardType !== self::SCOREBOARD_NONE){
			$this->scoreboard->addLine($line, $display);
		}
	}
}
