<?php

declare(strict_types=1);

namespace zodiax\commands\staff;

use pocketmine\command\CommandSender;
use zodiax\commands\PracticeCommand;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\ranks\RankHandler;
use function count;
use function implode;

class StaffCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("staff", "Lists all online staffs", "Usage: /staff", []);
		parent::setPermission("practice.permission.staff");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender)){
			$staffs = [];
			foreach(PlayerManager::getAllStaffSessions() as $session){
				$staffs[] = "{$session->getPlayer()->getName()} ({$session->getRankInfo()->getRank()->getName()})";
			}
			$online = count($staffs);
			$sender->sendMessage(PracticeCore::PREFIX . "There are $online staff(s) online: " . implode(", ", $staffs));
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_HELPER;
	}
}