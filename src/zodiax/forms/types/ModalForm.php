<?php

declare(strict_types=1);

namespace zodiax\forms\types;

use zodiax\forms\Form;

class ModalForm extends Form{

	private string $content = "";

	public function __construct(?callable $callable){
		parent::__construct($callable);
		$this->data["type"] = "modal";
		$this->data["title"] = "";
		$this->data["content"] = $this->content;
		$this->data["button1"] = "";
		$this->data["button2"] = "";
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

	public function setButton1(string $text) : void{
		$this->data["button1"] = $text;
	}

	public function getButton1() : string{
		return $this->data["button1"] ?? "not-set";
	}

	public function setButton2(string $text) : void{
		$this->data["button2"] = $text;
	}

	public function getButton2() : string{
		return $this->data["button2"] ?? "not-set";
	}
}