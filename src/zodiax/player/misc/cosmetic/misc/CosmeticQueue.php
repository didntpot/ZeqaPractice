<?php

declare(strict_types=1);

namespace zodiax\player\misc\cosmetic\misc;

class CosmeticQueue{

	const LOAD = 0;
	const SAVE = 1;

	private int $method;
	private string $player;
	private bool $boolean;
	private string $extraData;

	public function __construct(int $method, string $player, bool $boolean, string $extraData){
		$this->method = $method;
		$this->player = $player;
		$this->boolean = $boolean;
		$this->extraData = $extraData;
	}

	public function getMethod() : int{
		return $this->method;
	}

	public function getPlayer() : string{
		return $this->player;
	}

	public function getBoolean() : bool{
		return $this->boolean;
	}

	public function getExtraData() : string{
		return $this->extraData;
	}
}
