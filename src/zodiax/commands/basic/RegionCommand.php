<?php

declare(strict_types=1);

namespace zodiax\commands\basic;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use zodiax\commands\PracticeCommand;
use zodiax\forms\display\basic\servers\RegionMenu;
use zodiax\proxy\TransferHandler;
use zodiax\ranks\RankHandler;

class RegionCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("region", "Transfer to server", "Usage: /region", []);
		parent::setPermission("practice.permission.region");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player && !TransferHandler::isTransferring($sender->getName())){
			RegionMenu::onDisplay($sender);
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_NONE;
	}
}