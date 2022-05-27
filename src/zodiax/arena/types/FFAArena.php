<?php

declare(strict_types=1);

namespace zodiax\arena\types;

use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\World;
use zodiax\arena\ArenaManager;
use zodiax\game\items\ItemHandler;
use zodiax\kits\DefaultKit;
use zodiax\kits\KitsManager;
use zodiax\player\info\scoreboard\ScoreboardInfo;
use zodiax\player\misc\VanishHandler;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function array_rand;
use function count;

class FFAArena extends Arena{

	private string $name;
	private string $kit;
	private int $worldId;
	private array $spawns;
	private bool $interrupt;
	private array $players;
	private array $spectators;

	public function __construct(string $name, DefaultKit $kit, World $world, array $spawns, bool $interrupt = true){
		$this->name = $name;
		$this->kit = $kit->getName();
		$this->worldId = $world->getId();
		$this->spawns = $spawns;
		$this->interrupt = $interrupt;
		$this->players = [];
		$this->spectators = [];
	}

	public function getName() : string{
		return $this->name;
	}

	public function getKit() : ?DefaultKit{
		return KitsManager::getKit($this->kit);
	}

	public function setKit(DefaultKit $kit) : void{
		$this->kit = $kit->getName();
		ArenaManager::saveArena($this);
	}

	public function getSpawns(int $index = 0) : array{
		if($index === 0){
			return $this->spawns;
		}
		return [$this->spawns[$index] ?? []];
	}

	public function setSpawn(Vector3 $spawn, int $key = 0) : void{
		if(isset($this->spawns[$key])){
			$this->spawns[$key] = $spawn;
		}elseif($key === 0){
			$this->spawns[(count($this->spawns) + 1)] = $spawn;
		}
		ArenaManager::saveArena($this);
	}

	public function removeSpawn(int $key) : void{
		if(isset($this->spawns[$key])){
			unset($this->spawns[$key]);
			$spawns = $this->spawns;
			$this->spawns = [];
			$i = 1;
			foreach($spawns as $spawn){
				$this->spawns[$i++] = $spawn;
			}
			ArenaManager::saveArena($this);
		}
	}

	public function getWorld() : ?World{
		return Server::getInstance()->getWorldManager()->getWorld($this->worldId);
	}

	public function canInterrupt() : bool{
		return $this->interrupt;
	}

	public function setCanInterrupt(bool $interrupt) : void{
		$this->interrupt = $interrupt;
		ArenaManager::saveArena($this);
	}

	public function getTexture() : string{
		return $this->getKit()?->getMiscKitInfo()->getTexture() ?? "";
	}

	public function getPlayers(bool $asInt = false) : int|array{
		return $asInt ? count($this->players) : $this->players;
	}

	public function addPlayer(Player $player) : void{
		if(($world = $this->getWorld()) !== null && ($session = PlayerManager::getSession($player)) !== null){
			$pos = Position::fromObject($this->spawns[array_rand($this->spawns)], $world);
			PracticeUtil::onChunkGenerated($world, $pos->getFloorX() >> 4, $pos->getFloorZ() >> 4, function() use ($pos, $session){
				if(($player = $session->getPlayer()) !== null){
					$this->players[$name = $player->getName()] = $name;
					PracticeUtil::teleport($player, $pos);
					$session->getKitHolder()->setKit($this->kit);
					$session->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_FFA);
					$session->setInHub(false);
					if(!$this->canInterrupt()){
						foreach($this->players as $p){
							if(($pSession = PlayerManager::getSession($p = PlayerManager::getPlayerExact($p))) !== null && $pSession->getSettingsInfo()->isHideNonOpponents() && $pSession->isInCombat()){
								$p->hidePlayer($player);
							}
						}
					}
					$players = $this->getPlayers(true);
					foreach($this->spectators as $spec){
						PlayerManager::getSession(PlayerManager::getPlayerExact($spec))?->getScoreboardInfo()->updateLineOfScoreboard(2, PracticeCore::COLOR . " Players: " . TextFormat::WHITE . $players);
					}
				}
			});
		}
	}

	public function removePlayer(string $name) : void{
		if(isset($this->players[$name])){
			unset($this->players[$name]);
			if(!$this->canInterrupt() && ($player = PlayerManager::getPlayerExact($name)) !== null){
				foreach($this->players as $p){
					PlayerManager::getPlayerExact($p)?->showPlayer($player);
				}
			}
			$players = $this->getPlayers(true);
			foreach($this->spectators as $spec){
				PlayerManager::getSession(PlayerManager::getPlayerExact($spec))?->getScoreboardInfo()->updateLineOfScoreboard(2, PracticeCore::COLOR . " Players: " . TextFormat::WHITE . $players);
			}
		}
	}

	public function isPlayer(string|Player $player) : bool{
		return isset($this->players[$player instanceof Player ? $player->getName() : $player]);
	}

	public function addSpectator(Player $player) : void{
		if(($world = $this->getWorld()) !== null && ($session = PlayerManager::getSession($player)) !== null){
			$pos = Position::fromObject($this->spawns[array_rand($this->spawns)], $world);
			PracticeUtil::onChunkGenerated($world, $pos->getFloorX() >> 4, $pos->getFloorZ() >> 4, function() use ($pos, $session){
				if(($player = $session->getPlayer()) !== null){
					$this->spectators[$name = $player->getName()] = $name;
					PracticeUtil::teleport($player, $pos);
					VanishHandler::addToVanish($player);
					ItemHandler::giveSpectatorItem($player);
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "You are now spectating " . TextFormat::GREEN . $this->name . TextFormat::GRAY . " FFA");
					$session->getScoreboardInfo()->setScoreboard(ScoreboardInfo::SCOREBOARD_SPECTATOR);
					$session->setInHub(false);
				}
			});
		}
	}

	public function removeSpectator(string $name) : void{
		if(isset($this->spectators[$name])){
			if(($session = PlayerManager::getSession($player = PlayerManager::getPlayerExact($name))) !== null){
				$session->reset();
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "You have left spectating " . TextFormat::RED . $this->name . TextFormat::GRAY . " FFA");
			}
			unset($this->spectators[$name]);
		}
	}

	public function isSpectator(string|Player $player) : bool{
		return isset($this->spectators[$player instanceof Player ? $player->getName() : $player]);
	}

	public function destroyCycles() : void{
		$msg = PracticeCore::PREFIX . TextFormat::RED . "This arena has been terminated";
		foreach($this->getPlayers() as $player){
			if(($session = PlayerManager::getSession($player = PlayerManager::getPlayerExact($player))) !== null){
				$player->sendMessage($msg);
				$session->reset();
			}
		}
	}

	public function export() : array{
		$spawns = [];
		foreach($this->spawns as $key => $pos){
			$spawns[$key] = PracticeUtil::posToArray($pos);
		}
		return ["kit" => $this->getKit()?->getName() ?? "", "world" => $this->getWorld()?->getFolderName() ?? "", "spawns" => $spawns, "interrupt" => $this->interrupt, "type" => ArenaManager::FFA];
	}
}
