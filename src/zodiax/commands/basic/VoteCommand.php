<?php

declare(strict_types=1);

namespace zodiax\commands\basic;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\commands\PracticeCommand;
use zodiax\PracticeCore;
use zodiax\ranks\RankHandler;
use zodiax\ranks\VoteHandler;

class VoteCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("vote", "Claim your vote", "Usage: /vote", []);
		parent::setPermission("practice.permission.vote");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player){
			if(VoteHandler::isInQueue($sender)){
				$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Your vote is already being processed");
				return true;
			}
			$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Your vote is being processed, please wait");
			VoteHandler::processVote($sender);
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_NONE;
	}
}