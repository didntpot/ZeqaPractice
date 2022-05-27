<?php

declare(strict_types=1);

namespace zodiax\commands\staff;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use zodiax\commands\PracticeCommand;
use zodiax\forms\display\staff\npc\NPCManagerMenu;
use zodiax\ranks\RankHandler;

class NPCCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("npc", "Spawn/Remove NPC", "Usage: /npc", []);
		parent::setPermission("practice.permission.npc");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($sender instanceof Player && $this->testPermission($sender)){
			NPCManagerMenu::onDisplay($sender);
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_OWNER;
	}
}