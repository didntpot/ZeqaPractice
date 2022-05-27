<?php

declare(strict_types=1);

namespace zodiax\commands\basic;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use zodiax\commands\PracticeCommand;
use zodiax\forms\display\cosmetic\shop\ShopMenu;
use zodiax\proxy\TransferHandler;
use zodiax\ranks\RankHandler;

class ShopCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("shop", "Open Shop Menu", "Usage: /shop", []);
		parent::setPermission("practice.permission.shop");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player && !TransferHandler::isTransferring($sender->getName())){
			ShopMenu::onDisplay($sender);
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_NONE;
	}
}