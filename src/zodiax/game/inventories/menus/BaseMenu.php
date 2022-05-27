<?php

declare(strict_types=1);

namespace zodiax\game\inventories\menus;

use pocketmine\player\Player;
use zodiax\game\inventories\menus\inventory\PracticeBaseInv;

abstract class BaseMenu{

	private string $name;
	private bool $edit;
	private PracticeBaseInv $inv;

	public function __construct(PracticeBaseInv $inv){
		$this->name = "";
		$this->edit = true;
		$this->inv = $inv;
	}

	public function getInventory() : PracticeBaseInv{
		return $this->inv;
	}

	public function setName(string $name) : void{
		$this->name = $name;
	}

	public function getName() : string{
		return $this->name;
	}

	public function setEdit(bool $edit) : void{
		$this->edit = $edit;
	}

	public function canEdit() : bool{
		return $this->edit;
	}

	public function send(Player $player) : void{
		$this->getInventory()->send($player);
	}
}