<?php

declare(strict_types=1);

namespace zodiax\commands\staff;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use zodiax\commands\PracticeCommand;
use zodiax\forms\display\staff\cosmetic\CosmeticManagerMenu;
use zodiax\ranks\RankHandler;

class XoopCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("xoop", "Give coins, shards, cosmetics", "Usage: /xoop", []);
		parent::setPermission("practice.permission.xoop");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player){
			CosmeticManagerMenu::onDisplay($sender);
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_OWNER;
	}
}