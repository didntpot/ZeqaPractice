<?php

declare(strict_types=1);

namespace zodiax\player\info;

use pocketmine\player\Player;
use poggit\libasynql\SqlThread;
use zodiax\data\database\DatabaseManager;
use zodiax\game\behavior\kits\IKitHolderEntity;
use zodiax\game\behavior\kits\KitHolder;
use zodiax\kits\DefaultKit;
use zodiax\kits\edited\EditedKit;
use zodiax\kits\KitsManager;
use zodiax\player\PlayerManager;
use function array_shift;
use function base64_encode;
use function is_string;
use function json_encode;
use function strtolower;

class PlayerKitHolder extends KitHolder{

	private array $editedKits;
	private ?DefaultKit $currentEditingKit = null;

	public function __construct(IKitHolderEntity $entity){
		parent::__construct($entity);
		$this->editedKits = [];
	}

	public function init(array $kitsData) : void{
		foreach($kitsData as $kitName => $data){
			$editedKit = new EditedKit($kitName, $data);
			if($editedKit->hasParentKit()){
				$this->editedKits[$editedKit->getName()] = $editedKit;
			}
		}
	}

	public function setEditingKit(string $editingKit) : bool{
		if($this->isEditingKit() || ($kit = KitsManager::getKit($editingKit)) === null){
			return false;
		}
		$this->currentEditingKit = $kit;
		$this->clearEntity();
		$this->getParentEntity()->setImmobile(true);
		$this->currentEditingKit->giveTo($this->holderEntity);
		return true;
	}

	public function resetEditingKit() : void{
		if($this->isEditingKit()){
			$this->clearEntity();
			$this->getParentEntity()->setImmobile(true);
			$this->currentEditingKit->giveTo($this->holderEntity);
		}
	}

	public function isEditingKit() : bool{
		return $this->currentEditingKit !== null;
	}

	public function setFinishedEditingKit(bool $cancelled) : void{
		if(!$this->isEditingKit()){
			return;
		}
		/** @var Player $player */
		$player = $this->getParentEntity();
		if($cancelled){
			$this->clearEntity();
			if(!PlayerManager::getSession($player)->isFrozen()){
				$player->setImmobile(false);
			}
			$this->currentEditingKit = null;
			return;
		}
		$this->addEditedKit($this->currentEditingKit, $player->getInventory()->getContents());
		$this->clearEntity();
		if(!PlayerManager::getSession($player)->isFrozen()){
			$player->setImmobile(false);
		}
		$this->currentEditingKit = null;
	}

	private function addEditedKit(DefaultKit $kit, array $items) : void{
		$slotsData = [];
		$lib = $kit->getEditItemsLib();
		foreach($items as $slot => $item){
			if(isset($lib[$name = $item->getName()])){
				$slotsData[array_shift($lib[$name])] = $slot;
			}
		}
		$this->editedKits[$kit->getName()] = new EditedKit($kit, $slotsData);
		$player = $this->getParentEntity();
		if($player instanceof Player && ($session = PlayerManager::getSession($player)) !== null){
			$local = $kit->getLocalName();
			$xuid = $session->getClientInfo()->getXuid();
			$lowername = strtolower($player->getName());
			$data = base64_encode(json_encode($slotsData));
			DatabaseManager::getExtraDatabase()->executeImplRaw([0 => "INSERT INTO KitsData (xuid, name, $local) VALUES ('$xuid', '$lowername', '$data') ON DUPLICATE KEY UPDATE $local = '$data'"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);
		}
	}

	public function setKit($kit) : void{
		if(is_string($kit) && isset($this->editedKits[$kit])){
			parent::setKit($this->editedKits[$kit]);
			return;
		}
		if($kit instanceof DefaultKit && isset($this->editedKits[$kit->getName()])){
			parent::setKit($this->editedKits[$kit->getName()]);
			return;
		}
		parent::setKit($kit);
	}

	public function export() : array{
		$kits = [];
		foreach($this->editedKits as $kitName => $editedKit){
			$kits[$kitName] = $editedKit->export();
		}
		return $kits;
	}
}