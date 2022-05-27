<?php

declare(strict_types=1);

namespace zodiax\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;

abstract class PracticeCommand extends Command{

	public function __construct(string $name, string $description = "", string $usageMessage = null, $aliases = []){
		parent::__construct($name, $description, $usageMessage, $aliases);
	}

	public function canUseCommand(CommandSender $sender) : bool{
		if($sender instanceof Player && ($session = PlayerManager::getSession($sender)) !== null){
			if($session->isInCombat()){
				$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You can not use this command while in combat");
				return false;
			}elseif($session->isInDuel() || $session->isInBotDuel() || $session->isInPartyDuel()){
				$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You can not use this command while in duel");
				return false;
			}elseif($session->isInEvent() || $session->isInGame()){
				$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You can not use this command while in event");
				return false;
			}elseif($session->isSpectateArena() || $session->isInSpectate() || $session->isInReplay()){
				$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You can not use this command while spectating");
				return false;
			}elseif($session->isInBlockIn() || $session->isInClutch() || $session->isInReduce()){
				$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You can not use this command while training");
				return false;
			}
		}
		return true;
	}

	abstract public function getRankPermission() : string;
}