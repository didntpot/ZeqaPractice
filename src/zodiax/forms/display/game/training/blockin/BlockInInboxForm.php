<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\training\blockin;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\training\misc\BlockInInvite;
use zodiax\training\misc\BlockInInviteHandler;
use zodiax\training\TrainingHandler;
use function array_keys;
use function count;

class BlockInInboxForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && !$session->isInBlockIn() && $data !== null && isset($extraData["invites"]) && count($extraData["invites"]) > 0){
				$invites = $extraData["invites"];
				$keys = array_keys($invites);
				if(!isset($keys[$data])){
					return;
				}
				$invite = $invites[$keys[$data]];
				if($invite instanceof BlockInInvite){
					$blockIn = TrainingHandler::getBlockInById($invite->getWorldId());
					if($blockIn !== null){
						$name = PlayerManager::getPlayerExact($blockIn->getOwner())?->getDisplayName() . "'s Block-In";
						$isavailable = $blockIn->isAvailable();
						$blacklisted = $blockIn->isBlackListed($player);
						if($isavailable && !$blacklisted){
							BlockInInviteHandler::acceptInvite($invite);
							$blockIn->addPlayer($player);
						}elseif($blacklisted){
							$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You are blacklisted from " . TextFormat::GRAY . $name);
						}elseif($isavailable){
							$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . $name . TextFormat::GRAY . " is not available anymore");
						}
					}else{
						$player->sendMessage(PracticeCore::PREFIX . "Target match does not exist");
					}
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Block-In " . TextFormat::WHITE . "Inbox"));
		$form->setContent("");
		$invites = BlockInInviteHandler::getInvitesOf($player);
		if(count($invites) <= 0){
			$form->addButton(TextFormat::GRAY . "None");
		}else{
			foreach($invites as $invite){
				$form->addButton(PracticeCore::COLOR . "Sent by: " . TextFormat::WHITE . PlayerManager::getPlayerExact($invite->getFrom())?->getDisplayName());
			}
		}
		$form->addExtraData("invites", $invites);
		$player->sendForm($form);
	}
}
