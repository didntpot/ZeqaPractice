<?php

declare(strict_types=1);

namespace zodiax\commands\basic;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use zodiax\commands\PracticeCommand;
use zodiax\forms\display\cosmetic\types\DisguiseForm;
use zodiax\proxy\TransferHandler;
use zodiax\ranks\RankHandler;

class DisguiseCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("disguise", "Open Disguise Menu", "Usage: /disguise", ["nick"]);
		parent::setPermission("practice.permission.disguise");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player && !TransferHandler::isTransferring($sender->getName())){
			DisguiseForm::onDisplay($sender);
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_CONTENT_CREATOR;
	}
}