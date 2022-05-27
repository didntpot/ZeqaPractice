<?php

declare(strict_types=1);

namespace zodiax\game\behavior\kits;

use pocketmine\entity\Human;
use pocketmine\player\Player;
use zodiax\kits\IKit;
use zodiax\kits\KitsManager;
use zodiax\player\PlayerManager;
use function is_string;

class KitHolder{

	protected ?IKit $kit = null;
	protected IKitHolderEntity $holderEntity;

	public function __construct(IKitHolderEntity $holderEntity){
		$this->holderEntity = $holderEntity;
	}

	public function getParentEntity() : ?Human{
		return $this->holderEntity->getKitHolderEntity();
	}

	public function setKit(string|IKit $kit) : void{
		if(is_string($kit)){
			$kit = KitsManager::getKit($kit);
		}
		if($kit instanceof IKit){
			$this->clearEntity();
			$this->kit = $kit;
			$this->kit->giveTo($this->holderEntity);
		}
	}

	public function clearKit() : void{
		$this->kit = null;
		$this->clearEntity();
	}

	public function getKit() : ?IKit{
		return $this->kit;
	}

	public function hasKit() : bool{
		return $this->kit !== null;
	}

	protected function clearEntity() : void{
		$entity = $this->getParentEntity();
		if($entity instanceof Player && ($session = PlayerManager::getSession($entity)) !== null){
			$session->getExtensions()?->clearAll();
		}else{
			$entity?->getArmorInventory()->clearAll();
			$entity?->getInventory()->clearAll();
			$entity?->getEffects()->clear();
		}
	}
}