<?php

declare(strict_types=1);

namespace zodiax\forms\types;

use zodiax\forms\Form;
use zodiax\forms\types\properties\ButtonTexture;
use function count;
use function is_int;
use function is_integer;
use function is_string;

class SimpleForm extends Form{

	const IMAGE_TYPE_PATH = 0;
	const IMAGE_TYPE_URL = 1;

	private string $content = "";
	private array $labelMap = [];

	public function __construct(?callable $callable){
		parent::__construct($callable);
		$this->data["type"] = "form";
		$this->data["title"] = "";
		$this->data["content"] = $this->content;
	}

	public function processData(&$data) : void{
		$data = $this->labelMap[$data] ?? null;
	}

	public function setTitle(string $title) : void{
		$this->data["title"] = $title;
	}

	public function getTitle() : string{
		return $this->data["title"] ?? "not-set";
	}

	public function getContent() : string{
		return $this->data["content"] ?? "not-set";
	}

	public function setContent(string $content) : void{
		$this->data["content"] = $content;
	}

	public function addButton(string $text, ...$args) : void{
		$content = ["text" => $text];
		if(isset($args[0])){
			$firstArgument = $args[0];
			if($firstArgument instanceof ButtonTexture){
				$firstArgument->import($content);
				if(isset($args[1]) && (is_string(($secondArgument = $args[1])) || is_integer($secondArgument))){
					$label = $secondArgument;
				}
			}elseif(is_integer($firstArgument) && isset($args[1]) && is_string(($secondArgument = $args[1]))){
				$texture = new ButtonTexture($firstArgument, (string) $secondArgument);
				$texture->import($content);
				if(isset($args[3]) && (is_int($thirdArgument = $args[3]) || is_string($thirdArgument))){
					$label = $thirdArgument;
				}
			}elseif(is_string($firstArgument)){
				$label = $firstArgument;
				if(isset($args[1]) && ($secondArgument = $args[1]) !== null && $secondArgument instanceof ButtonTexture){
					$secondArgument->import($content);
				}
			}
		}
		$this->data["buttons"][] = $content;
		$this->labelMap[] = $label ?? count($this->labelMap);
	}
}