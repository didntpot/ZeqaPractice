<?php

declare(strict_types=1);

namespace zodiax\game\inventories\menus\inventory;

use pocketmine\block\inventory\BlockInventory;
use pocketmine\inventory\SimpleInventory;
use pocketmine\player\Player;
use pocketmine\world\Position;
use zodiax\game\inventories\menus\BaseMenu;

abstract class PracticeBaseInv extends SimpleInventory implements BlockInventory{

	const HEIGHT_ABOVE = 0;

	protected BaseMenu $menu;
	protected Position $holder;

	public function __construct(BaseMenu $menu, int $size, Position $position){
		parent::__construct($size);
		$this->menu = $menu;
		$this->holder = $position;
	}

	abstract public function sendPrivateInv(Player $player) : void;

	abstract public function sendPublicInv(Player $player) : void;

	public function send(Player $player) : void{
		$pos = $this->holder->floor()->add(0, self::HEIGHT_ABOVE, 0);
		if($player->getWorld()->isInWorld($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ())){
			$this->sendPrivateInv($player);
		}
	}

	public function onClose(Player $who) : void{
		if($who->getWorld()->isChunkLoaded($this->holder->x >> 4, $this->holder->z >> 4)){
			$this->sendPublicInv($who);
		}
		parent::onClose($who);
	}

	public function getBaseMenu() : BaseMenu{
		return $this->menu;
	}
}