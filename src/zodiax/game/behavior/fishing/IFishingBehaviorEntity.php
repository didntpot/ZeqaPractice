<?php

declare(strict_types=1);

namespace zodiax\game\behavior\fishing;

use pocketmine\entity\Human;

interface IFishingBehaviorEntity{

	public function getFishingEntity() : ?Human;

	public function getFishingBehavior() : ?FishingBehavior;
}