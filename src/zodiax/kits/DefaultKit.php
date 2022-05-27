<?php

declare(strict_types=1);

namespace zodiax\kits;

use pocketmine\item\ItemIds;
use zodiax\game\behavior\kits\IKitHolderEntity;
use zodiax\kits\info\EffectsInfo;
use zodiax\kits\info\KnockbackInfo;
use zodiax\kits\info\MiscKitInfo;
use zodiax\PracticeUtil;
use function strtolower;

class DefaultKit implements IKit{

	private string $name;
	private string $localName;
	private array $editItemsLib;
	protected array $items;
	protected array $armor;
	protected EffectsInfo $effectsInfo;
	protected MiscKitInfo $miscKitInfo;
	protected KnockbackInfo $knockbackInfo;

	public function __construct(string $name, array $items, array $armor, EffectsInfo $effectsInfo, MiscKitInfo $miscKitInfo, KnockbackInfo $knockbackInfo){
		$this->name = $name;
		$this->localName = strtolower($name);
		$this->items = $items;
		foreach($this->items as $slot => $item){
			if(isset($this->editItemsLib[$name = $item->getName()])){
				$this->editItemsLib[$name][] = $slot;
			}else{
				$this->editItemsLib[$name] = [$slot];
			}
		}
		$this->armor = $armor;
		$this->effectsInfo = $effectsInfo;
		$this->miscKitInfo = $miscKitInfo;
		$this->knockbackInfo = $knockbackInfo;
	}

	public function getItems() : array{
		return $this->items;
	}

	public function setItems(array $items) : void{
		$this->items = $items;
	}

	public function getArmor() : array{
		return $this->armor;
	}

	public function setArmor(array $armor) : void{
		$this->armor = $armor;
	}

	public function getMiscKitInfo() : MiscKitInfo{
		return $this->miscKitInfo;
	}

	public function getKnockbackInfo() : KnockbackInfo{
		return $this->knockbackInfo;
	}

	public function getName() : string{
		return $this->name;
	}

	public function getLocalName() : string{
		return $this->localName;
	}

	public function getEditItemsLib() : array{
		return $this->editItemsLib;
	}

	public function giveTo(IKitHolderEntity $entity) : bool{
		$entityHolder = $entity->getKitHolderEntity();
		$entityHolder->getInventory()->setContents($this->items);
		$entityHolder->getArmorInventory()->setContents($this->armor);
		$effectManager = $entityHolder->getEffects();
		foreach($this->getEffectsInfo()->getEffects() as $effect){
			$effectManager->add($effect->setDuration(PracticeUtil::minutesToTicks(60))->setVisible(false));
		}
		return true;
	}

	public function getEffectsInfo() : EffectsInfo{
		return $this->effectsInfo;
	}

	public function equals($kit) : bool{
		if($kit instanceof IKit){
			return $this->localName == $kit->getLocalName();
		}
		return false;
	}

	public function export() : array{
		$items = [];
		$armor = [];
		foreach($this->items as $slot => $item){
			if($item->getId() === ItemIds::AIR){
				continue;
			}
			$items[$slot] = PracticeUtil::itemToArr($item);
		}
		foreach($this->armor as $slot => $item){
			$armor[PracticeUtil::convertArmorIndex($slot)] = PracticeUtil::itemToArr($item);
		}
		return ["name" => $this->name, "items" => $items, "armor" => $armor, "effect" => $this->effectsInfo->export(), "misc" => $this->miscKitInfo->export(), "kb" => $this->knockbackInfo->export()];
	}
}
