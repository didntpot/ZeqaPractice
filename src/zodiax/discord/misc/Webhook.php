<?php

declare(strict_types=1);

namespace zodiax\discord\misc;

use libasynCurl\Curl;
use function filter_var;
use function json_encode;

class Webhook{

	protected string $url;

	public function __construct(string $url){
		$this->url = $url;
	}

	public function getURL() : string{
		return $this->url;
	}

	public function isValid() : bool{
		return filter_var($this->url, FILTER_VALIDATE_URL) !== false;
	}

	public function send(Message $message) : void{
		Curl::postRequest($this->getURL(), json_encode($message), 10, ["Content-Type: application/json"]);
	}
}
