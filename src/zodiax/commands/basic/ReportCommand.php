<?php

declare(strict_types=1);

namespace zodiax\commands\basic;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use zodiax\commands\PracticeCommand;
use zodiax\forms\display\basic\report\ReportMenu;
use zodiax\ranks\RankHandler;

class ReportCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("report", "Report Hackers/Bugs", "Usage: /report", []);
		parent::setPermission("practice.permission.report");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player){
			ReportMenu::onDisplay($sender);
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_NONE;
	}
}