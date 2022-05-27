<?php

declare(strict_types=1);

namespace zodiax\commands\staff;

use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use zodiax\commands\PracticeCommand;
use zodiax\PracticeCore;
use zodiax\ranks\RankHandler;
use function array_shift;
use function count;
use function implode;
use function sort;
use function strtolower;
use function trim;

class WhitelistCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("whitelist", "Whitelist", "Usage: /whitelist <on:off:reload:add:remove:list:clear>", []);
		parent::setPermission("practice.permission.whitelist");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender)){
			if(count($args) >= 1){
				$arg = strtolower(array_shift($args));
				switch($arg){
					case "on":
						Server::getInstance()->getConfigGroup()->setConfigBool("white-list", true);
						$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Whitelist enabled");
						return true;
					case "off":
						Server::getInstance()->getConfigGroup()->setConfigBool("white-list", false);
						$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Whitelist disabled");
						return true;
					case "reload":
						Server::getInstance()->getWhitelisted()->reload();
						$sender->sendMessage(PracticeCore::PREFIX . TextFormat::YELLOW . "Whitelist reloaded");
						return true;
					case "add":
						if(count($args) >= 1){
							Server::getInstance()->addWhitelist($name = trim(implode(" ", $args)));
							$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "Successfully added " . TextFormat::GREEN . $name . TextFormat::GRAY . " to whitelist");
							return true;
						}
						$sender->sendMessage(PracticeCore::PREFIX . "Usage: /whitelist add <player>");
						return true;
					case "remove":
						if(count($args) >= 1){
							Server::getInstance()->removeWhitelist($name = trim(implode(" ", $args)));
							$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "Successfully removed " . TextFormat::RED . $name . TextFormat::GRAY . " from whitelist");
							return true;
						}
						$sender->sendMessage(PracticeCore::PREFIX . "Usage: /whitelist remove <player>");
						return true;
					case "list":
						$entries = Server::getInstance()->getWhitelisted()->getAll(true);
						sort($entries, SORT_STRING);
						$sender->sendMessage(PracticeCore::PREFIX . PracticeCore::COLOR . count($entries) . TextFormat::GRAY . " Whitelisted player(s): " . implode(", ", $entries));
						return true;
					case "clear":
						$whitelist = Server::getInstance()->getWhitelisted();
						$entries = $whitelist->getAll(true);
						foreach($entries as $entry){
							$whitelist->remove($entry);
						}
						$whitelist->save();
						$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "Successfully cleared " . TextFormat::YELLOW . count($entries) . TextFormat::GRAY . " players from whitelist");
						return true;
				}
			}
			$sender->sendMessage(PracticeCore::PREFIX . $this->getUsage());
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_OWNER;
	}
}
