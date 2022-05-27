<?php

declare(strict_types=1);

namespace zodiax\player\info\settings;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\World;
use zodiax\duel\BotHandler;
use zodiax\duel\DuelHandler;
use zodiax\duel\ReplayHandler;
use zodiax\party\duel\PartyDuelHandler;
use zodiax\player\misc\PlayerTrait;
use zodiax\training\TrainingHandler;
use function is_string;

class BuilderModeInfo{
	use PlayerTrait;

	private bool $enabled = false;
	private array $builderWorlds = [];

	public function __construct(Player $player){
		$this->player = $player->getName();
	}

	public function init() : void{
		$this->enabled = false;
		$this->updateBuilderWorlds();
	}

	public function updateBuilderWorlds() : void{
		$outputWorlds = [];
		foreach(Server::getInstance()->getWorldManager()->getWorlds() as $world){
			if(DuelHandler::isDuelWorld($world) || BotHandler::isDuelWorld($world) || PartyDuelHandler::isDuelWorld($world) || ReplayHandler::isReplayWorld($world) || TrainingHandler::isClutchInWorld($world) || TrainingHandler::isReduceInWorld($world) || TrainingHandler::isBlockInWorld($world)){
				continue;
			}
			$outputWorlds[$name = $world->getFolderName()] = $this->builderWorlds[$name] ?? true;
		}
		$this->builderWorlds = $outputWorlds;
	}

	public function canBuild() : bool{
		return $this->getSession()->getRankInfo()->hasBuilderPermissions() && $this->isEnabled() && isset($this->builderWorlds[$name = $this->getPlayer()->getWorld()->getFolderName()]) && $this->builderWorlds[$name];
	}

	public function isEnabled() : bool{
		return $this->enabled;
	}

	public function setEnabled(bool $enabled) : void{
		$this->enabled = $enabled;
	}

	public function setBuildEnabledInWorld($world, bool $enabled) : void{
		if($world instanceof World){
			$name = $world->getFolderName();
		}elseif(is_string($world)){
			$name = $world;
		}
		if(isset($name)){
			$this->builderWorlds[$name] = $enabled;
		}
	}

	public function getBuilderWorlds() : array{
		return $this->builderWorlds;
	}
}