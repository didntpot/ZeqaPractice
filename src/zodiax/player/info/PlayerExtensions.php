<?php

declare(strict_types=1);

namespace zodiax\player\info;

use pocketmine\entity\Attribute;
use pocketmine\player\Player;
use zodiax\player\misc\PlayerTrait;

class PlayerExtensions{
	use PlayerTrait;

	public function __construct(Player $player){
		$this->player = $player->getName();
	}

	public function clearAll() : void{
		if(($player = $this->getPlayer()) !== null){
			$player->setHealth($player->getMaxHealth());
			$player->getHungerManager()->setFood($player->getHungerManager()->getMaxFood());
			$player->getHungerManager()->setSaturation($this->getMaxSaturation());
			$this->clearInventory();
			$player->getEffects()->clear();
			$this->setXpAndProgress(0, 0.0);
		}
	}

	public function getMaxSaturation() : float{
		return $this->getPlayer()->getAttributeMap()->get(Attribute::SATURATION)->getMaxValue();
	}

	public function clearInventory() : void{
		if(($player = $this->getPlayer()) !== null){
			$player->getInventory()->clearAll();
			$player->getCursorInventory()->clearAll();
			$player->getArmorInventory()->clearAll();
		}
	}

	public function setXpAndProgress(int $level, float $progress) : void{
		if(($player = $this->getPlayer()) !== null){
			$player->getXpManager()->setXpLevel($level);
			$player->getXpManager()->setXpProgress($progress);
		}
	}

	public function enableFlying(bool $flying) : void{
		if(($player = $this->getPlayer()) !== null){
			$player->setAllowFlight($flying);
			$player->setFlying($flying);
		}
	}
}
