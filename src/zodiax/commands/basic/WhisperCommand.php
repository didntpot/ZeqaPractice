<?php

declare(strict_types=1);

namespace zodiax\commands\basic;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\XpCollectSound;
use zodiax\commands\PracticeCommand;
use zodiax\data\log\LogMonitor;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\ranks\RankHandler;
use function array_shift;
use function count;
use function implode;
use function trim;

class WhisperCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("whisper", "Whisper a message", "Usage: /whisper <player> <message>", ["w", "message", "msg", "tell"]);
		parent::setPermission("practice.permission.whisper");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player && ($session = PlayerManager::getSession($sender)) !== null && $session->tryChat()){
			if(count($args) > 1){
				if(($psession = PlayerManager::getSession($player = PlayerManager::getPlayerByPrefix($name = array_shift($args)))) !== null){
					$sentences = TextFormat::clean(trim(implode(" ", $args)));
					if($sender->isOnline()){
						$sender->sendMessage(TextFormat::GOLD . "To " . PracticeCore::COLOR . $player->getDisplayName() . TextFormat::DARK_GRAY . " » " . TextFormat::WHITE . $sentences);
					}
					if($player->isOnline()){
						$player->sendMessage(TextFormat::GOLD . "From " . PracticeCore::COLOR . $sender->getDisplayName() . TextFormat::DARK_GRAY . " » " . TextFormat::WHITE . $sentences);
						$player->broadcastSound(new XpCollectSound(), [$player]);
					}
					$session->setCanChat(false);
					$session->setLastReplied($player->getName());
					$psession->setLastReplied($sender->getName());
					LogMonitor::chatLog("Whisper: {$sender->getName()} » {$player->getName()} : $sentences");
					return true;
				}
				$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Can not find player $name");
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
