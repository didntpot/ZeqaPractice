<?php

declare(strict_types=1);

namespace zodiax\player\info\duel\data;

use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\item\Item;
use pocketmine\item\ProjectileItem;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use zodiax\player\PlayerManager;
use function array_keys;
use function count;

class PlayerReplayData{

	private string $name;
	private Skin $skin;
	private int $deathTime;
	private array $startInventory;
	private array $startArmor;
	private array $itemTimes;
	private array $armorTimes;
	private array $scoreTags;
	private array $locationTimes;
	private array $animationTimes;
	private array $throwTimes;
	private array $fishingTimes;
	private array $bowTimes;
	private array $sneakTimes;
	private array $potcolor;

	public function __construct(Player $player){
		$this->name = $player->getNameTag();
		$this->skin = $player->getSkin();
		$this->deathTime = -1;
		$this->startInventory = $player->getInventory()->getContents(true);
		$this->startArmor = $player->getArmorInventory()->getContents(true);
		$this->itemTimes = [];
		$this->armorTimes = [];
		$this->scoreTags = [];
		$this->locationTimes = [];
		$this->animationTimes = [];
		$this->throwTimes = [];
		$this->fishingTimes = [];
		$this->bowTimes = [];
		$this->sneakTimes = [];
		$this->potcolor = PlayerManager::getSession($player)->getItemInfo()->getPotColor() ?? ["255", "0", "0"];
	}

	public function setDeathTime(int $tick) : void{
		$this->deathTime = $tick;
	}

	public function setItemAt(int $tick, Item $item) : void{
		$lastIndex = count($this->itemTimes) - 1;
		if($lastIndex < 0){
			$this->itemTimes[$tick] = $item;
			return;
		}
		$lastKey = array_keys($this->itemTimes)[$lastIndex];
		$lastValue = $this->itemTimes[$lastKey];
		if($item->getId() !== $lastValue->getId() || ($item->getId() === $lastValue->getId() && $item->getMeta() !== $lastValue->getMeta())){
			$this->itemTimes[$tick] = $item;
		}
	}

	public function setArmorAt(int $tick, array $armor = []) : void{
		$length = count($this->armorTimes);
		if($length <= 0){
			$this->armorTimes[$tick] = $armor;
			return;
		}
		$length = $length - 1;
		$keys = array_keys($this->armorTimes);
		$lastKey = $keys[$length];
		$lastArmorUpdate = $this->armorTimes[$lastKey];
		$keys = ["chest", "helmet", "pants", "boots"];
		foreach($keys as $key){
			if(!isset($lastArmorUpdate[$key]) && isset($armor[$key])){
				if(!isset($this->armorTimes[$tick])){
					$this->armorTimes[$tick] = [$key => $armor[$key]];
				}else{
					$this->armorTimes[$tick][$key] = $armor[$key];
				}
			}elseif(isset($lastArmorUpdate[$key]) && isset($armor[$key])){
				$lastArmor = $lastArmorUpdate[$key];
				$testArmor = $armor[$key];
				if(!$lastArmor->equals($testArmor)){
					if(!isset($this->armorTimes[$tick])){
						$this->armorTimes[$tick] = [$key => $testArmor];
					}else{
						$this->armorTimes[$tick][$key] = $testArmor;
					}
				}
			}
		}
	}

	public function setScoreTagAt(int $tick, string $name) : void{
		$this->scoreTags[$tick] = $name;
	}

	public function setLocationAt(int $tick, Location $location) : void{
		$this->locationTimes[$tick] = $location;
	}

	public function setAnimationAt(int $tick, int $animation, int $data) : void{
		if(!isset($this->animationTimes[$tick])){
			$this->animationTimes[$tick] = [];
		}
		$this->animationTimes[$tick][] = ["event" => $animation, "data" => $data];
	}

	public function setThrowAt(int $tick, string $item) : void{
		$this->throwTimes[$tick] = $item;
	}

	public function setFishingAt(int $tick, bool $fishing) : void{
		$this->fishingTimes[$tick] = $fishing;
	}

	public function setReleaseBowAt(int $tick, float $force) : void{
		$this->bowTimes[$tick] = $force;
	}

	public function setSneakingAt(int $tick, bool $sneak) : void{
		$this->sneakTimes[$tick] = $sneak;
	}

	public function getAttributesAt(int $tick) : array{
		if($this->didDie() && $tick >= $this->deathTime){
			return ["death" => true];
		}
		$result = [];
		if(isset($this->scoreTags[$tick])){
			$result["scoretag"] = $this->scoreTags[$tick];
		}
		if(isset($this->throwTimes[$tick])){
			$result["thrown"] = $this->throwTimes[$tick];
		}
		if(isset($this->fishingTimes[$tick])){
			$result["fishing"] = $this->fishingTimes[$tick];
		}
		if(isset($this->bowTimes[$tick])){
			$result["bow"] = $this->bowTimes[$tick];
		}
		if(isset($this->itemTimes[$tick])){
			$result["item"] = $this->itemTimes[$tick];
		}
		if(isset($this->animationTimes[$tick])){
			$result["animation"] = $this->animationTimes[$tick];
		}
		if(isset($this->armorTimes[$tick])){
			$result["armor"] = $this->armorTimes[$tick];
		}
		if(isset($this->locationTimes[$tick])){
			$result["location"] = $this->locationTimes[$tick];
		}
		if(isset($this->sneakTimes[$tick])){
			$result["sneak"] = $this->sneakTimes[$tick];
		}
		return $result;
	}

	public function getLastAttributeUpdate(int $tick, string $attribute) : float|Vector3|ProjectileItem|int|bool|array|string|Item|null{
		$searchedArray = match ($attribute) {
			"scoretag" => $this->scoreTags,
			"thrown" => $this->throwTimes,
			"fishing" => $this->fishingTimes,
			"bow" => $this->bowTimes,
			"item" => $this->itemTimes,
			"animation" => $this->animationTimes,
			"armor" => $this->armorTimes,
			"location" => $this->locationTimes,
			"sneak" => $this->sneakTimes,
			default => [],
		};
		$lastTick = $tick;
		while(!isset($searchedArray[$lastTick]) && $lastTick >= 0){
			$lastTick--;
		}
		return !isset($searchedArray[$lastTick]) ? null : $searchedArray[$lastTick];
	}

	public function getName() : string{
		return $this->name;
	}

	public function getSkin() : Skin{
		return $this->skin;
	}

	public function getStartInventory() : array{
		return $this->startInventory;
	}

	public function getStartArmorInventory() : array{
		return $this->startArmor;
	}

	public function getPotColor() : array{
		return $this->potcolor;
	}

	public function getDeathTime() : int{
		return $this->deathTime;
	}

	public function didDie() : bool{
		return $this->deathTime > 0;
	}
}