<?php

declare(strict_types=1);

namespace zodiax\commands\basic;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use zodiax\commands\PracticeCommand;
use zodiax\forms\display\basic\RanksInfoForm;
use zodiax\ranks\RankHandler;

class RanksCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("ranks", "Show ranks information", "Usage: /ranks", []);
		parent::setPermission("practice.permission.ranks");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player){
			RanksInfoForm::onDisplay($sender);
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_NONE;
	}
}