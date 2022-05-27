<?php

declare(strict_types=1);

namespace zodiax\commands\staff;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use zodiax\commands\PracticeCommand;
use zodiax\forms\display\kits\KitManagerMenu;
use zodiax\ranks\RankHandler;

class KitCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("kit", "Edit a kit", "Usage: /kit", []);
		parent::setPermission("practice.permission.kit");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player){
			KitManagerMenu::onDisplay($sender);
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_OWNER;
	}
}