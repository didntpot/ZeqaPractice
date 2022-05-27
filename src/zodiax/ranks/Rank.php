<?php

declare(strict_types=1);

namespace zodiax\ranks;

class Rank{

	private string $name;
	private string $format;
	private string $color;
	private string $permission;

	public function __construct(string $name, string $format, string $color, string $permission = RankHandler::PERMISSION_NONE){
		$this->name = $name;
		$this->format = $format;
		$this->color = $color;
		$this->permission = $permission;
	}

	public function getName() : string{
		return $this->name;
	}

	public function getFormat() : string{
		return $this->format;
	}

	public function getColor() : string{
		return $this->color;
	}

	public function getPermission() : string{
		return $this->permission;
	}

	public function encode() : array{
		return ["name" => $this->name, "format" => $this->format, "color" => $this->color, "permission" => $this->permission];
	}
}