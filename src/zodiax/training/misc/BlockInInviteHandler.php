<?php

declare(strict_types=1);

namespace zodiax\training\misc;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\XpCollectSound;
use zodiax\duel\DuelHandler;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\training\types\BlockInPractice;

class BlockInInviteHandler{

	private static array $invites = [];

	public static function sendInvite(Player $from, Player $to, BlockInPractice $blockIn) : void{
		if($from->isOnline()){
			$from->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "Successfully sent block-in invite to " . TextFormat::WHITE . $to->getDisplayName() . TextFormat::GRAY);
		}
		$invite = self::$invites[$key = "{$from->getName()}:{$to->getName()}"] ?? null;
		if($to->isOnline() && ($invite === null || $invite->getWorldId() !== $blockIn->getWorldId())){
			$to->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "Received a new block-in invite from " . TextFormat::WHITE . $from->getDisplayName() . TextFormat::GRAY);
			$to->broadcastSound(new XpCollectSound(), [$to]);
		}
		self::$invites[$key] = new BlockInInvite($from, $to, $blockIn);
	}

	public static function getInvitesOf(Player $player) : array{
		$result = [];
		$name = $player->getName();
		foreach(self::$invites as $key => $invite){
			if($invite->getTo() === $name){
				if(PlayerManager::getPlayerExact($from = $invite->getFrom()) !== null){
					$result[$from] = $invite;
				}else{
					unset(self::$invites[$key]);
				}
			}
		}
		return $result;
	}

	public static function removeInvitesOf(Player $player) : void{
		$name = $player->getName();
		foreach(self::$invites as $key => $invite){
			if($invite->getTo() === $name || $invite->getFrom() === $name){
				unset(self::$invites[$key]);
			}
		}
	}

	public static function acceptInvite(BlockInInvite $invite) : void{
		$from = PlayerManager::getPlayerExact($invite->getFrom());
		$to = PlayerManager::getPlayerExact($invite->getTo());
		if($from instanceof Player && $to instanceof Player){
			DuelHandler::removeFromQueue($from, false);
			DuelHandler::removeFromQueue($to, false);
			$to->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "You have accepted " . TextFormat::WHITE . $from->getDisplayName() . "'s" . TextFormat::GRAY . " block-in invite");
			$from->sendMessage(PracticeCore::PREFIX . TextFormat::WHITE . $to->getDisplayName() . TextFormat::GRAY . " has accepted your block-in invite");
			unset(self::$invites["{$from->getName()}:{$to->getName()}"]);
		}
	}
}
