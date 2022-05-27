<?php

declare(strict_types=1);

namespace zodiax\kits\info;

use function is_array;

class KnockbackInfo{

	private float $horizontalKB;
	private float $verticalKB;
	private float $maxHeight;
	private bool $canRevert;
	private int $speed;

	public function __construct(float $horizontalKB = 0.4, float $verticalKB = 0.4, float $maxHeight = 0.0, bool $canRevert = false, int $speed = 10){
		$this->horizontalKB = $horizontalKB;
		$this->verticalKB = $verticalKB;
		$this->maxHeight = $maxHeight;
		$this->canRevert = $canRevert;
		$this->speed = $speed;
	}

	public static function decode($data) : KnockbackInfo{
		if(is_array($data) && isset($data["xzkb"], $data["ykb"], $data["maxheight"], $data["revert"], $data["speed"])){
			return new KnockbackInfo($data["xzkb"], $data["ykb"], $data["maxheight"], $data["revert"], $data["speed"]);
		}
		return new KnockbackInfo();
	}

	public function getHorizontalKb() : float{
		return $this->horizontalKB;
	}

	public function setHorizontalKb(float $kb) : void{
		$this->horizontalKB = $kb;
	}

	public function getVerticalKb() : float{
		return $this->verticalKB;
	}

	public function setVerticalKb(float $kb) : void{
		$this->verticalKB = $kb;
	}

	public function getMaxHeight() : float{
		return $this->maxHeight;
	}

	public function setMaxHeight(float $height) : void{
		$this->maxHeight = $height;
	}

	public function canRevert() : bool{
		return $this->canRevert;
	}

	public function setCanRevert(bool $canRevert) : void{
		$this->canRevert = $canRevert;
	}

	public function getSpeed() : int{
		return $this->speed;
	}

	public function setSpeed(int $speed) : void{
		$this->speed = $speed;
	}

	public function export() : array{
		return ["xzkb" => $this->horizontalKB, "ykb" => $this->verticalKB, "maxheight" => $this->maxHeight, "revert" => $this->canRevert, "speed" => $this->speed];
	}
}