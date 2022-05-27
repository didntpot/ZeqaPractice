<?php

declare(strict_types=1);

namespace zodiax\game\inventories\menus;

use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use zodiax\game\inventories\menus\inventory\DoubleChestInv;
use zodiax\player\info\duel\DuelInfo;

class PostMatchInv extends BaseMenu{

	private DuelInfo $duelInfo;

	public function __construct(DuelInfo $info, Position $position, bool $winner){
		parent::__construct(new DoubleChestInv($this, $position));
		$this->duelInfo = $info;
		$this->setName(($winner ? TextFormat::GREEN . $info->getWinnerDisplayName() : TextFormat::RED . $info->getLoserDisplayName()) . "'s" . TextFormat::GRAY . " Inventory");
		$this->setEdit(false);
		$this->getInventory()->setContents($winner ? $info->getWinnerPostInv() : $info->getLoserPostInv());
	}

	public function getDuelInfo() : ?DuelInfo{
		return $this->duelInfo;
	}
}