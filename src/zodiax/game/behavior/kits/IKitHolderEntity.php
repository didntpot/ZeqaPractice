<?php

declare(strict_types=1);

namespace zodiax\game\behavior\kits;

use pocketmine\entity\Human;

interface IKitHolderEntity{

	public function getKitHolder() : ?KitHolder;

	public function getKitHolderEntity() : ?Human;
}