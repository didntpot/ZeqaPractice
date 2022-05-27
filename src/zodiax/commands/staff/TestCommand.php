<?php

declare(strict_types=1);

namespace zodiax\commands\staff;

use pocketmine\command\CommandSender;
use zodiax\commands\PracticeCommand;
use zodiax\ranks\RankHandler;

class TestCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("test", "Test", "Usage: /test", []);
		parent::setPermission("practice.permission.test");
	}

	/** @noinspection PhpStatementHasEmptyBodyInspection */
	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender)){

		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_OWNER;
	}
}