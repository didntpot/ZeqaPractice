<?php

declare(strict_types=1);

namespace zodiax\commands\basic;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\commands\PracticeCommand;
use zodiax\duel\BotHandler;
use zodiax\duel\DuelHandler;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\proxy\TransferHandler;
use zodiax\ranks\RankHandler;

class MoveServerCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("moveserver", "Transfers you to a target server", "Usage: /moveserver <name>", ["mv"]);
		parent::setPermission("practice.permission.moveserver");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player && ($session = PlayerManager::getSession($sender)) !== null && $session->isInHub() && !$session->isInParty() && !TransferHandler::isTransferring($sender->getName())){
			if(($server = $args[0] ?? null) !== null){
				if($server !== PracticeCore::getRegionInfo()){
					$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Saving your data, you will get transfer to $server once saving done");
					DuelHandler::removeFromQueue($sender, false);
					BotHandler::removeFromQueue($sender, false);
					$session->saveData($server);
					return true;
				}
				$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You already connected to $server");
				return true;
			}
			$sender->sendMessage(PracticeCore::PREFIX . $this->getUsage());
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_NONE;
	}
}
