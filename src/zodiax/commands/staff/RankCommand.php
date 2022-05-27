<?php

declare(strict_types=1);

namespace zodiax\commands\staff;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use zodiax\commands\PracticeCommand;
use zodiax\forms\display\staff\rank\RankManagerMenu;
use zodiax\ranks\RankHandler;

class RankCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("rank", "Edit a rank", "Usage: /rank", []);
		parent::setPermission("practice.permission.rank");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player){
			RankManagerMenu::onDisplay($sender);
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_OWNER;
	}
}