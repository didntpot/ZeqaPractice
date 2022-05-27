<?php

declare(strict_types=1);

namespace zodiax\kits\edited;

use zodiax\game\behavior\kits\IKitHolderEntity;
use zodiax\kits\DefaultKit;
use zodiax\kits\IKit;
use zodiax\kits\info\EffectsInfo;
use zodiax\kits\info\KnockbackInfo;
use zodiax\kits\info\MiscKitInfo;
use zodiax\kits\KitsManager;
use zodiax\PracticeUtil;
use function is_string;

class EditedKit implements IKit{

	private ?DefaultKit $parentKit = null;
	private array $items;
	private array $slotsData;

	public function __construct($parentKit, array $slotsData){
		$this->items = [];
		$this->slotsData = $slotsData;
		$this->setParentKit($parentKit);
	}

	public function setParentKit($parentKit) : void{
		if($parentKit instanceof DefaultKit){
			$this->parentKit = $parentKit;
		}elseif(is_string($parentKit)){
			$this->parentKit = KitsManager::getKit($parentKit);
		}
		if($this->parentKit !== null){
			$result = [];
			$backup = [];
			$items = $this->parentKit->getItems();
			foreach($items as $slot => $item){
				if(isset($this->slotsData[$slot])){
					$result[$this->slotsData[$slot]] = $item;
				}else{
					$backup[] = $item;
				}
			}
			foreach($backup as $item){
				$result[] = $item;
			}
			if(empty($result)){
				$result = $items;
			}
			$this->items = $result;
		}
	}

	public function giveTo(IKitHolderEntity $entity) : bool{
		if(!$this->hasParentKit()){
			return false;
		}
		$entityHolder = $entity->getKitHolderEntity();
		$entityHolder->getInventory()->setContents($this->items);
		$entityHolder->getArmorInventory()->setContents($this->parentKit->getArmor());
		$effectManager = $entityHolder->getEffects();
		foreach($this->getEffectsInfo()->getEffects() as $effect){
			$effectManager->add($effect->setDuration(PracticeUtil::minutesToTicks(60))->setVisible(false));
		}
		return true;
	}

	public function hasParentKit() : bool{
		return $this->parentKit !== null;
	}

	public function getEffectsInfo() : EffectsInfo{
		return $this->parentKit->getEffectsInfo();
	}

	public function getKnockbackInfo() : KnockbackInfo{
		return $this->parentKit->getKnockbackInfo();
	}

	public function getMiscKitInfo() : MiscKitInfo{
		return $this->parentKit->getMiscKitInfo();
	}

	public function getName() : string{
		return $this->parentKit->getName();
	}

	public function getLocalName() : string{
		return $this->parentKit->getLocalName();
	}

	public function equals($kit) : bool{
		return $this->parentKit->equals($kit);
	}

	public function export() : array{
		$outputItems = [];
		foreach($this->items as $slot => $item){
			$outputItems[$slot] = PracticeUtil::itemToArr($item);
		}
		return ["parentName" => $this->parentKit->getName(), "items" => $outputItems];
	}
}