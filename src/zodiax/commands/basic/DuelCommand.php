<?php

declare(strict_types=1);

namespace zodiax\commands\basic;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\commands\PracticeCommand;
use zodiax\forms\display\game\duel\DuelRequestForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\proxy\TransferHandler;
use zodiax\ranks\RankHandler;
use function count;
use function implode;
use function trim;

class DuelCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("duel", "Send duel request", "Usage: /duel <player>");
		parent::setPermission("practice.permission.duel");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player && !TransferHandler::isTransferring($sender->getName())){
			if(count($args) === 0){
				DuelRequestForm::onDisplay($sender);
				return true;
			}
			if(($player = PlayerManager::getPlayerByPrefix($name = trim(implode(" ", $args)))) !== null && $player->getName() !== $sender->getName()){
				DuelRequestForm::onDisplay($sender, $player);
				return true;
			}
			$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Can not find player $name");
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_NONE;
	}
}
