<?php

declare(strict_types=1);

namespace zodiax\commands\basic;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use zodiax\commands\PracticeCommand;
use zodiax\forms\display\cosmetic\CosmeticMenuForm;
use zodiax\ranks\RankHandler;

class CosmeticsCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("cosmetics", "Show cosmetic UI", "Usage: /cosmetics", []);
		parent::setPermission("practice.permission.cosmetics");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player){
			CosmeticMenuForm::onDisplay($sender);
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_NONE;
	}
}