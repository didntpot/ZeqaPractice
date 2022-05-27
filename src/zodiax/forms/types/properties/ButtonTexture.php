<?php

declare(strict_types=1);

namespace zodiax\forms\types\properties;

use function is_array;

class ButtonTexture{

	const TYPE_NONE = -1;
	const TYPE_PATH = 0;
	const TYPE_URL = 1;

	private int $imageType;
	private string $path;

	public function __construct(int $imageType, string $path){
		$this->imageType = $imageType;
		$this->path = $path;
	}

	public static function decode($data) : ?ButtonTexture{
		if(is_array($data) && isset($data["type"], $data["path"])){
			return new ButtonTexture($data["type"], $data["path"]);
		}
		return null;
	}

	public function getPath() : string{
		return $this->path;
	}

	public function setPath(string $path) : void{
		if($path === $this->path){
			return;
		}
		$this->path = $path;
	}

	public function getImageType() : int{
		return $this->imageType;
	}

	public function setImageType($imageType) : void{
		if($this->imageType === (int) $imageType){
			return;
		}
		$this->imageType = (int) $imageType;
	}

	public function import(array &$array) : void{
		if(!$this->validate()){
			return;
		}
		$array["image"]["type"] = $this->imageType === 0 ? "path" : "url";
		$array["image"]["data"] = $this->path;
	}

	public function validate() : bool{
		return ($this->imageType === 0 || $this->imageType === 1);
	}

	public function export() : array{
		return ["type" => $this->imageType, "path" => $this->path];
	}
}