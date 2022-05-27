<?php

declare(strict_types=1);

namespace zodiax\game\inventories;

use pocketmine\player\Player;
use zodiax\game\inventories\menus\inventory\PracticeBaseInv;
use zodiax\misc\AbstractDelayedTask;

class InventoryTask extends AbstractDelayedTask{

	private Player $player;
	private PracticeBaseInv $inventory;

	public function __construct(Player $player, PracticeBaseInv $inv){
		parent::__construct(5);
		$this->player = $player;
		$this->inventory = $inv;
	}

	public function onUpdate(int $tickDifference) : void{
		if($this->player->isOnline()){
			$this->player->setCurrentWindow($this->inventory);
		}
	}
}