<?php

declare(strict_types=1);

namespace zodiax\game\items;

use pocketmine\item\Item;

class PracticeItem{

	private Item $item;
	private int $slot;

	public function __construct(Item $item, int $slot){
		$this->item = $item;
		$this->slot = $slot;
	}

	public function getItem() : Item{
		return $this->item;
	}

	public function getSlot() : int{
		return $this->slot;
	}
}
