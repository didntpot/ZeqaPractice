<?php

declare(strict_types=1);

namespace zodiax\game\entity;

use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\entity\object\ItemEntity;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;

class CustomItemEntity extends ItemEntity{

	public function __construct(Location $location, Item $item, ?Entity $shootingEntity, ?CompoundTag $nbt = null){
		if($shootingEntity !== null){
			$this->setOwningEntity($shootingEntity);
		}
		parent::__construct($location, $item, $nbt);
	}
}
