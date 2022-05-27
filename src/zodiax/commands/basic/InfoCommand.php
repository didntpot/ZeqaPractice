<?php

declare(strict_types=1);

namespace zodiax\commands\basic;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\commands\PracticeCommand;
use zodiax\PracticeCore;
use zodiax\ranks\RankHandler;
use function implode;

class InfoCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("info", "Show server's information", "Usage: /info", []);
		parent::setPermission("practice.permission.info");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player){
			$title = TextFormat::GRAY . "     » " . TextFormat::BOLD . PracticeCore::COLOR . "Server " . TextFormat::WHITE . "info" . TextFormat::RESET . TextFormat::GRAY . " «";
			$lineSeparator = TextFormat::DARK_GRAY . "";
			$result = ["title" => $title, "firstSeparator" => $lineSeparator, "discord" => TextFormat::YELLOW . "Discord: " . TextFormat::WHITE . "discord.gg/zeqa", "store" => TextFormat::YELLOW . "Store: " . TextFormat::WHITE . "store.zeqa.net", "vote" => TextFormat::YELLOW . "Vote: " . TextFormat::WHITE . "vote.zeqa.net", "secondSeparator" => $lineSeparator];
			$sender->sendMessage(implode("\n", $result));
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_NONE;
	}
}
