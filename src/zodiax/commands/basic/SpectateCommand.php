<?php

declare(strict_types=1);

namespace zodiax\commands\basic;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\commands\PracticeCommand;
use zodiax\forms\display\game\spectate\SpectateMenu;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\proxy\TransferHandler;
use zodiax\ranks\RankHandler;
use function count;
use function implode;
use function trim;

class SpectateCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("spectate", "Spectate a player", "Usage: /spectate <player>", ["spec"]);
		parent::setPermission("practice.permission.spectate");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player && ($session = PlayerManager::getSession($sender)) !== null && !TransferHandler::isTransferring($sender->getName())){
			if(!$session->isInHub()){
				$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You can only use this command in the lobby");
				return true;
			}
			if(count($args) === 0){
				SpectateMenu::onDisplay($sender);
				return true;
			}
			if(($player = PlayerManager::getPlayerByPrefix($name = trim(implode(" ", $args)))) !== null && $player->getName() !== $sender->getName() && ($pSession = PlayerManager::getSession($player)) !== null){
				if(($game = $pSession->getArena() ?? $pSession->getDuel() ?? $pSession->getBotDuel() ?? $pSession->getEvent() ?? $pSession->getPartyDuel() ?? $pSession->getBlockIn() ?? $pSession->getClutch() ?? $pSession->getReduce()) !== null){
					$game->addSpectator($sender);
					return true;
				}
				$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . $player->getDisplayName() . TextFormat::GRAY . " is not in a game");
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
