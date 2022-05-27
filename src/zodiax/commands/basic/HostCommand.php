<?php

declare(strict_types=1);

namespace zodiax\commands\basic;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use zodiax\commands\PracticeCommand;
use zodiax\forms\display\game\event\HostForm;
use zodiax\ranks\RankHandler;

class HostCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("host", "Host an event", "Usage: /host", []);
		parent::setPermission("practice.permission.host");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player){
			HostForm::onDisplay($sender);
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_VIP;
	}
}