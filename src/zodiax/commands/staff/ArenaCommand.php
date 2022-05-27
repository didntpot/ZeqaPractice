<?php

declare(strict_types=1);

namespace zodiax\commands\staff;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use zodiax\commands\PracticeCommand;
use zodiax\forms\display\arena\ArenaManagerMenu;
use zodiax\ranks\RankHandler;

class ArenaCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("arena", "Edit an arena", "Usage: /arena", []);
		parent::setPermission("practice.permission.arena");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player){
			ArenaManagerMenu::onDisplay($sender);
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_OWNER;
	}
}