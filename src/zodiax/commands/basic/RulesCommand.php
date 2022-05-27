<?php

declare(strict_types=1);

namespace zodiax\commands\basic;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use zodiax\commands\PracticeCommand;
use zodiax\forms\display\basic\RulesInfoForm;
use zodiax\ranks\RankHandler;

class RulesCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("rules", "Show rules information", "Usage: /rules", []);
		parent::setPermission("practice.permission.rules");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player){
			RulesInfoForm::onDisplay($sender);
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_NONE;
	}
}