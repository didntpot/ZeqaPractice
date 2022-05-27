<?php

declare(strict_types=1);

namespace zodiax\player\info\disguise;

class PlayerVisualData{

	private string $displayName;

	public function __construct(string $displayName){
		$this->displayName = $displayName;
	}

	public function getDisplayName() : string{
		return $this->displayName;
	}

	public function setDisplayName(string $displayName) : void{
		$this->displayName = $displayName;
	}
}