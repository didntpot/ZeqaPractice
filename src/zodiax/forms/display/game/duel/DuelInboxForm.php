<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\duel;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\duel\DuelHandler;
use zodiax\duel\misc\DuelRequest;
use zodiax\duel\misc\RequestHandler;
use zodiax\forms\types\SimpleForm;
use zodiax\kits\KitsManager;
use zodiax\player\misc\queue\QueueHandler;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function array_keys;
use function count;

class DuelInboxForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && $data !== null && isset($extraData["requests"]) && count($extraData["requests"]) > 0){
				$requests = $extraData["requests"];
				$keys = array_keys($requests);
				if(!isset($keys[$data])){
					return;
				}
				$request = $requests[$keys[$data]];
				if($request instanceof DuelRequest){
					$pName = $player->getName();
					$opponent = ($pName === $request->getTo()) ? $request->getFrom() : $request->getTo();
					if(($opsession = PlayerManager::getSession($opponent = PlayerManager::getPlayerExact($opponent))) !== null){
						if($opsession->isInHub() && !$opsession->isInParty() && !$opsession->getKitHolder()->isEditingKit() && !$opsession->isInQueue() && !$opsession->isInBotQueue() && !QueueHandler::isInQueue($opponent)){
							RequestHandler::acceptRequest($request);
							DuelHandler::placeInDuel($player, $opponent, $request->getKit(), $request->isRanked());
						}else{
							$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You can only accept duel requests while opponent is in the lobby");
						}
					}
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Incoming " . TextFormat::WHITE . "Invites"));
		$form->setContent("");
		if(count($requests = RequestHandler::getRequestsOf($player)) <= 0){
			$form->addButton(TextFormat::GRAY . "None");
		}else{
			foreach($requests as $request){
				$button = PracticeCore::COLOR . "Sent by: " . TextFormat::WHITE . PlayerManager::getPlayerExact($request->getFrom())?->getDisplayName() . "\n";
				$button .= PracticeCore::COLOR . "Queue: " . TextFormat::WHITE . ($request->isRanked() ? "Ranked" : "Unranked") . " " . $request->getKit();
				$form->addButton($button, 0, KitsManager::getKit($request->getKit())?->getMiscKitInfo()->getTexture() ?? "");
			}
		}
		$form->addExtraData("requests", $requests);
		$player->sendForm($form);
	}
}