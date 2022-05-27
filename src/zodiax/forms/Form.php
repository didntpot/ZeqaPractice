<?php

declare(strict_types=1);

namespace zodiax\forms;

use pocketmine\form\Form as IForm;
use pocketmine\player\Player;

abstract class Form implements IForm{

	protected array $data = [];
	protected array $extraData = [];
	private $callable;

	public function __construct(?callable $callable){
		$this->callable = $callable;
	}

	public function setExtraData(array $data) : void{
		$this->extraData = $data;
	}

	public function addExtraData(string $key, $value) : void{
		$this->extraData[$key] = $value;
	}

	public function handleResponse(Player $player, $data) : void{
		$this->processData($data);
		$callable = $this->getCallable();
		if($callable !== null){
			$callable($player, $data, $this->extraData);
		}
	}

	public function processData(&$data) : void{
	}

	public function getCallable() : ?callable{
		return $this->callable;
	}

	public function setCallable(?callable $callable){
		$this->callable = $callable;
	}

	public function resetData(){
		$this->data = [];
	}

	public function jsonSerialize() : array{
		return $this->data;
	}
}