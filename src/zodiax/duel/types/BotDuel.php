<?php

declare(strict_types=1);

namespace zodiax\duel\types;

use pocketmine\block\Block;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\sound\ClickSound;
use pocketmine\world\sound\XpCollectSound;
use pocketmine\world\World;
use stdClass;
use zodiax\arena\ArenaManager;
use zodiax\arena\types\DuelArena;
use zodiax\duel\BotHandler;
use zodiax\game\entity\CombatBot;
use zodiax\game\items\ItemHandler;
use zodiax\game\world\BlockRemoverHandler;
use zodiax\kits\DefaultKit;
use zodiax\player\info\scoreboard\ScoreboardInfo;
use zodiax\player\misc\cosmetic\CosmeticManager;
use zodiax\player\misc\VanishHandler;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\utils\Math;
use function abs;
use function array_rand;
use function count;
use function rand;

class BotDuel{

	const STATUS_STARTING = 0;
	const STATUS_IN_PROGRESS = 1;
	const STATUS_ENDING = 2;
	const STATUS_ENDED = 3;

	const MAX_DURATION_SECONDS = 600;

	private int $status;
	private int $currentTick;
	private int $countdownSeconds;
	private int $durationSeconds;
	private string $kit;
	private int $mode;
	private string $arena;
	private int $worldId;
	private ?World $world;
	private ?Position $centerPosition;
	private Player|CombatBot|null $winner;
	private Player|CombatBot|null $loser;
	private string $playerName;
	private ?CombatBot $bot;
	private array $spectators;
	private array $chunks;
	private array $numHits;

	public function __construct(int $worldId, Player $player, DefaultKit $kit, int $mode, DuelArena $arena){
		$this->status = self::STATUS_STARTING;
		$this->currentTick = 0;
		$this->countdownSeconds = 5;
		$this->durationSeconds = 0;
		$this->kit = $kit->getName();
		$this->mode = $mode;
		$this->arena = $arena->getName();
		$this->worldId = $worldId;
		/** @var DuelArena $arena */
		$arena = ArenaManager::getArena($this->arena);
		$this->world = ArenaManager::MAPS_MODE === ArenaManager::ADVANCE ? $arena->getWorld(true) : Server::getInstance()->getWorldManager()->getWorldByName("duel" . $this->worldId);
		$spawnPos1 = $arena->getP1Spawn();
		$spawnPos2 = $arena->getP2Spawn();
		$this->centerPosition = new Position((int) (($spawnPos2->getX() + $spawnPos1->getX()) / 2), $spawnPos1->getY(), (int) (($spawnPos2->getZ() + $spawnPos1->getZ()) / 2), $this->world);
		$this->winner = null;
		$this->loser = null;
		$this->playerName = $player->getName();
		$this->bot = null;
		$this->spectators = [];
		$this->chunks = [];
		$this->numHits = ["player" => 0, "bot" => 0];
	}

	public function update() : void{
		if(($player = PlayerManager::getPlayerExact($this->playerName)) === null){
			$this->setEnded($this->bot);
		}elseif($this->bot !== null && !$this->bot->isAlive()){
			$this->setEnded($player);
		}
		$this->currentTick++;
		switch($this->status){
			case self::STATUS_STARTING:
				if($this->currentTick % 20 === 0){
					if($this->countdownSeconds === 5){
						$this->setInDuel();
						$player->sendTitle(TextFormat::RED . "Starting duel in 5", "", 5, 20, 5);
						$player->broadcastSound(new ClickSound(), [$player]);
					}elseif($this->countdownSeconds > 0 && $this->countdownSeconds < 5){
						$player->sendTitle(TextFormat::RED . $this->countdownSeconds . "...", "", 5, 20, 5);
						$player->broadcastSound(new ClickSound(), [$player]);
					}elseif($this->countdownSeconds === 0){
						$player->sendTitle(TextFormat::RED . "Fight!", "", 5, 20, 5);
						$player->broadcastSound(new XpCollectSound(), [$player]);
						if(!PlayerManager::getSession($player)->isFrozen()){
							$player->setImmobile(false);
						}
						$this->bot->setTarget($player);
						$this->status = self::STATUS_IN_PROGRESS;
						$this->countdownSeconds = 3;
					}
					$this->countdownSeconds--;
				}
				break;
			case self::STATUS_IN_PROGRESS:
				if($this->durationSeconds === 0){
					$this->bot->scheduleUpdate();
				}
				$session = PlayerManager::getSession($player);
				$session->getClicksInfo()->update();
				if($this->currentTick % 20 === 0){
					$session->getScoreboardInfo()->updateDuration($this->getDuration());
					foreach($this->spectators as $spec){
						if(($spectator = PlayerManager::getPlayerExact($spec, true)) !== null){
							PlayerManager::getSession($spectator)->getScoreboardInfo()->updateDuration($this->getDuration());
						}else{
							$this->removeSpectator($spec);
						}
					}
					if(++$this->durationSeconds >= self::MAX_DURATION_SECONDS){
						$this->setEnded();
						return;
					}
				}
				break;
			case self::STATUS_ENDING:
				if($this->currentTick % 20 === 0 && --$this->countdownSeconds === 0){
					$this->endDuel();
					return;
				}
				break;
		}
	}

	private function setInDuel() : void{
		/** @var DuelArena $arena */
		$arena = ArenaManager::getArena($this->arena);
		$spawnPos1 = $arena->getP1Spawn();
		$spawnPos2 = $arena->getP2Spawn();
		$player = PlayerManager::getPlayerExact($this->playerName);
		$session = PlayerManager::getSession($player);
		$player->setImmobile();
		PracticeUtil::onChunkGenerated($this->world, $spawnPos1->getFloorX() >> 4, $spawnPos1->getFloorZ() >> 4, function() use ($player, $session, $spawnPos1, $spawnPos2){
			PracticeUtil::teleport($player, Position::fromObject($spawnPos1, $this->world), $spawnPos2);
			$session->getKitHolder()->setKit($this->kit);
			$session->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_DUEL);
		});
		PracticeUtil::onChunkGenerated($this->world, $spawnPos2->getFloorX() >> 4, $spawnPos2->getFloorZ() >> 4, function() use ($player, $spawnPos1, $spawnPos2){
			$this->bot = new CombatBot(Location::fromObject($spawnPos2, $this->world), $player->getSkin());
			$this->bot->getKitHolder()->setKit($this->kit);
			$this->bot->initialize($this->mode, $this->centerPosition, $spawnPos1);
			$this->bot->spawnToAll();
			PracticeUtil::teleport($this->bot, $spawnPos2, $spawnPos1);
		});
	}

	public function setEnded(mixed $winner = null) : void{
		if($this->status !== self::STATUS_ENDING && $this->status !== self::STATUS_ENDED){
			if($winner !== null){
				$player = PlayerManager::getPlayerExact($this->playerName);
				$this->winner = $winner;
				$this->loser = $winner instanceof Player ? $this->bot : $player;
				if($player instanceof Player){
					if(rand(1, 10000) <= ($winner instanceof Player ? 100 : 50)){
						$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "You received a drop for winning a bot!");
						$itemList = ["41", "43", "45", "47"];
						PlayerManager::getSession($player)->getItemInfo()->alterCosmeticById($player, CosmeticManager::CAPE, $itemList[array_rand($itemList)], false, true, true);
					}
				}
			}
			$this->countdownSeconds = 3;
			$this->status = self::STATUS_ENDING;
		}
	}

	private function endDuel() : void{
		$this->status = self::STATUS_ENDED;
		$fillerMessages = new stdClass();
		$this->generateMessages($fillerMessages);
		if(($player = PlayerManager::getPlayerExact($this->playerName)) !== null){
			$this->sendFinalMessage($player, $fillerMessages);
			PlayerManager::getSession($player)->reset();
		}
		foreach($this->spectators as $spec){
			if(($spectator = PlayerManager::getPlayerExact($spec, true)) !== null){
				$this->sendFinalMessage($spectator, $fillerMessages);
				PlayerManager::getSession($spectator)->reset();
			}
		}
		if($this->world instanceof World){
			if(ArenaManager::MAPS_MODE !== ArenaManager::NORMAL){
				BlockRemoverHandler::removeBlocks($this->world, $this->chunks);
			}
		}
		BotHandler::removeDuel($this->worldId);
	}

	private function generateMessages(stdClass $fillerMessages) : void{
		if(($spectatorsSize = count($this->spectators)) > 0){
			$spectatorsString = "";
			$currentSpectatorIndex = 0;
			$loopedSpectatorsSize = Math::ceil($spectatorsSize, 3);
			foreach($this->spectators as $spec){
				if(($spectator = PlayerManager::getPlayerExact($spec, true)) !== null){
					if($currentSpectatorIndex === $loopedSpectatorsSize){
						$more = count($this->spectators) - $loopedSpectatorsSize;
						$spectatorsString .= TextFormat::DARK_GRAY . ", " . TextFormat::WHITE . "(+$more more)";
						break;
					}
					$comma = ($currentSpectatorIndex === ($loopedSpectatorsSize - 1)) ? "" : TextFormat::DARK_GRAY . ", ";
					$spectatorsString .= TextFormat::WHITE . $spectator->getDisplayName() . $comma;
					$currentSpectatorIndex++;
				}
			}
			$fillerMessages->spectatorString = $spectatorsString;
		}
	}

	private function sendFinalMessage(Player $playerToSendMessage, stdClass $extensionMessages) : void{
		if(PracticeCore::isPackEnable()){
			$finalMessage = "\n";
			$finalMessage .= " " . PracticeUtil::formatUnicodeKit($this->kit) . TextFormat::BOLD . TextFormat::WHITE . " Duel Summary" . TextFormat::RESET . "\n";
			$finalMessage .= TextFormat::GREEN . "  Winner: " . TextFormat::GRAY . (($this->winner === null) ? "None" : TextFormat::clean($this->winner->getDisplayName())) . "\n";
			$finalMessage .= TextFormat::RED . "  Loser: " . TextFormat::GRAY . (($this->loser === null) ? "None" : TextFormat::clean($this->loser->getDisplayName())) . "\n\n";
			$finalMessage .= "  " . TextFormat::WHITE . "Spectator(s): " . TextFormat::GRAY . ($extensionMessages->spectatorString ?? "None") . "\n";
			$finalMessage .= "\n";
		}else{
			$finalMessage = TextFormat::DARK_GRAY . "--------------------------\n";
			$finalMessage .= TextFormat::GREEN . "Winner: " . TextFormat::WHITE . (($this->winner === null) ? "None" : TextFormat::clean($this->winner->getDisplayName())) . "\n";
			$finalMessage .= TextFormat::RED . "Loser: " . TextFormat::WHITE . (($this->loser === null) ? "None" : TextFormat::clean($this->loser->getDisplayName())) . "\n";
			$finalMessage .= TextFormat::DARK_GRAY . "--------------------------\n";
			$finalMessage .= TextFormat::GRAY . "Spectator(s): " . TextFormat::WHITE . ($extensionMessages->spectatorString ?? "None") . "\n";
			$finalMessage .= TextFormat::DARK_GRAY . "--------------------------\n";
		}
		$playerToSendMessage->sendMessage($finalMessage);
	}

	public function addSpectator(Player $player) : void{
		if($this->status !== self::STATUS_ENDING && $this->status !== self::STATUS_ENDED){
			$this->spectators[$name = $player->getDisplayName()] = $name;
			PracticeUtil::onChunkGenerated($this->world, $this->centerPosition->getFloorX() >> 4, $this->centerPosition->getFloorZ() >> 4, function() use ($player){
				PracticeUtil::teleport($player, $this->centerPosition);
				VanishHandler::addToVanish($player);
				ItemHandler::giveSpectatorItem($player);
				$session = PlayerManager::getSession($player);
				$session->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_SPECTATOR);
				$session->setInHub(false);
			});
			$msg = PracticeCore::PREFIX . TextFormat::GREEN . $name . TextFormat::GRAY . " is now spectating the duel";
			PlayerManager::getPlayerExact($this->playerName)?->sendMessage($msg);
			foreach($this->spectators as $spec){
				PlayerManager::getPlayerExact($spec, true)?->sendMessage($msg);
			}
		}
	}

	public function removeSpectator(string $name) : void{
		if(isset($this->spectators[$name])){
			PlayerManager::getSession(PlayerManager::getPlayerExact($name, true))?->reset();
			$msg = PracticeCore::PREFIX . TextFormat::RED . $name . TextFormat::GRAY . " is no longer spectating the duel";
			PlayerManager::getPlayerExact($this->playerName)?->sendMessage($msg);
			foreach($this->spectators as $spec){
				PlayerManager::getPlayerExact($spec, true)?->sendMessage($msg);
			}
			unset($this->spectators[$name]);
		}
	}

	public function isSpectator(string|Player $player) : bool{
		return isset($this->spectators[$player instanceof Player ? $player->getDisplayName() : $player]);
	}

	public function addHitTo(CombatBot|Player $player) : void{
		if($player instanceof CombatBot || $this->isPlayer($player)){
			$this->numHits[$key = ($player instanceof CombatBot) ? "bot" : "player"]++;
			if($this->kit === "Boxing"){
				if(($player = PlayerManager::getPlayerExact($this->playerName)) !== null){
					$diff = abs($this->numHits["player"] - $this->numHits["bot"]);
					$isPlayerLeading = $this->numHits["player"] >= $this->numHits["bot"];
					$sbInfo = PlayerManager::getSession($player)->getScoreboardInfo();
					$sbInfo->updateLineOfScoreboard(1, PracticeCore::COLOR . " Hits: " . ($isPlayerLeading ? TextFormat::GREEN . "(+$diff)" : TextFormat::RED . "(-$diff)"));
					$sbInfo->updateLineOfScoreboard(2, PracticeCore::COLOR . "   You: " . TextFormat::WHITE . $this->numHits["player"]);
					$sbInfo->updateLineOfScoreboard(3, PracticeCore::COLOR . "   Them: " . TextFormat::WHITE . $this->numHits["bot"]);
				}
				$player1Line = PracticeCore::COLOR . "   {$player->getDisplayName()}: " . TextFormat::WHITE . $this->numHits["player"];
				$player2Line = PracticeCore::COLOR . "   {$this->bot->getDisplayName()}: " . TextFormat::WHITE . $this->numHits["bot"];
				foreach($this->spectators as $spectator){
					if(($specSession = PlayerManager::getSession(PlayerManager::getPlayerExact($spectator, true))) !== null){
						$sbInfo = $specSession->getScoreboardInfo();
						$sbInfo->updateLineOfScoreboard(2, $player1Line);
						$sbInfo->updateLineOfScoreboard(3, $player2Line);
					}
				}
				if($this->numHits[$key] >= 100 && $this->status === self::STATUS_IN_PROGRESS){
					$this->setEnded($key === "player" ? $player : $this->bot);
				}
			}
		}
	}

	public function tryBreakOrPlaceBlock(Player $player, Block $block, bool $break = true) : bool{
		return false;
	}

	public function getPlayer() : string{
		return $this->playerName;
	}

	public function getBot() : CombatBot{
		return $this->bot;
	}

	public function isPlayer(string|Player $player) : bool{
		return $this->playerName === ($player instanceof Player ? $player->getName() : $player);
	}

	public function getKit() : string{
		return $this->kit;
	}

	public function getMode() : int{
		return $this->mode;
	}

	public function getArena() : string{
		return $this->arena;
	}

	public function getCenterPosition() : Position{
		return $this->centerPosition;
	}

	public function isRunning() : bool{
		return $this->status === self::STATUS_IN_PROGRESS;
	}

	public function getDuration() : string{
		$seconds = $this->durationSeconds % 60;
		$minutes = (int) ($this->durationSeconds / 60);
		return ($minutes < 10 ? "0" . $minutes : $minutes) . ":" . ($seconds < 10 ? "0" . $seconds : $seconds);
	}

	public function destroyCycles() : void{
		$this->world = null;
		$this->centerPosition = null;
		$this->winner = null;
		$this->loser = null;
		$this->spectators = [];
		$this->bot = null;
		$this->chunks = [];
		ArenaManager::getArena($this->arena)?->setPreWorldAsAvailable($this->worldId);
	}
}
