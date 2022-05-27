<?php

declare(strict_types=1);

namespace zodiax\player\misc\cosmetic\misc;

use pocketmine\entity\Skin;
use pocketmine\player\Player;
use zodiax\player\misc\PlayerTrait;
use zodiax\player\PlayerManager;

class CosmeticHolder{
	use PlayerTrait;

	private Skin $skin;

	public function __construct(Player $player, Skin $skin){
		$this->player = $player->getName();
		$this->skin = $skin;
	}

	public function setSkin(string $skinData) : void{
		if(($player = $this->getPlayer()) !== null){
			$player->setSkin(new Skin($this->skin->getSkinId(), $skinData, $this->skin->getCapeData(), $this->skin->getGeometryName(), $this->skin->getGeometryData()));
			$player->sendSkin(PlayerManager::getOnlinePlayers());
		}
	}
}
