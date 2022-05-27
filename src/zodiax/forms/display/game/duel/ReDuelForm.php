<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\duel;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\duel\DuelHandler;
use zodiax\duel\misc\RequestHandler;
use zodiax\duel\ReplayHandler;
use zodiax\forms\types\SimpleForm;
use zodiax\game\inventories\menus\PostMatchInv;
use zodiax\player\info\duel\DuelInfo;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function is_bool;
use function is_string;

class ReDuelForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(($session = PlayerManager::getSession($player)) === null || !isset($args[0]) || !is_string($opponent = $args[0]) || !isset($args[1]) || !is_string($kit = $args[1]) || !isset($args[2]) || !is_bool($ranked = $args[2])){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null && ($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && isset($extraData["opponent"], $extraData["kit"], $extraData["ranked"], $extraData["info"])){
				$ranked = $extraData["ranked"];
				$info = $extraData["info"];
				switch($data){
					case 0:
						DuelHandler::placeInQueue($player, $extraData["kit"], $ranked);
						break;
					case 1:
						if(!$ranked){
							if(($opponent = PlayerManager::getPlayerExact($extraData["opponent"], true)) !== null && $opponent->isOnline()){
								RequestHandler::sendRequest($player, $opponent, $extraData["kit"], $ranked);
							}else{
								$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Player is not online");
							}
						}else{
							(new PostMatchInv($info["info"], $player->getPosition(), true))->send($player);
						}
						break;
					case 2:
						(new PostMatchInv($info["info"], $player->getPosition(), !$ranked))->send($player);
						break;
					case 3:
						if(!$ranked){
							(new PostMatchInv($info["info"], $player->getPosition(), false))->send($player);
						}elseif(isset($info["replay"])){
							ReplayHandler::startReplay($player, $info["replay"]);
						}
						break;
					case 4:
						if(isset($info["replay"])){
							ReplayHandler::startReplay($player, $info["replay"]);
						}
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Play " . TextFormat::WHITE . "Duels"));
		$form->setContent(PracticeCore::COLOR . "Mode: " . TextFormat::WHITE . ($ranked ? "Ranked " : "Unranked ") . $kit);
		$form->addButton(PracticeCore::COLOR . "Play " . TextFormat::WHITE . "Again", 0, "textures/ui/import.png");
		if(!$ranked){
			$form->addButton(PracticeCore::COLOR . "Request " . TextFormat::WHITE . "Rematch", 0, "textures/ui/refresh_hover.png");
		}
		$info = $session->getLatestDuelHistory();
		$duelInfo = $info["info"] ?? null;
		if($duelInfo instanceof DuelInfo){
			$playerVsString = "";
			if($duelInfo->getWinnerName() === $player->getName()){
				$playerVsString = TextFormat::WHITE . $duelInfo->getWinnerDisplayName() . PracticeCore::COLOR . " vs " . TextFormat::WHITE . $duelInfo->getLoserDisplayName();
			}elseif($duelInfo->getLoserName() === $player->getName()){
				$playerVsString = TextFormat::WHITE . $duelInfo->getLoserDisplayName() . PracticeCore::COLOR . " vs " . TextFormat::WHITE . $duelInfo->getWinnerDisplayName();
			}
			$form->setTitle(PracticeUtil::formatTitle($playerVsString));
			$form->addButton(TextFormat::GRAY . "View " . ($duelInfo->isDraw() ? PracticeCore::COLOR : TextFormat::GREEN) . $duelInfo->getWinnerDisplayName() . "'s " . TextFormat::GRAY . "Inventory", 0, "textures/blocks/trapped_chest_front.png");
			$form->addButton(TextFormat::GRAY . "View " . ($duelInfo->isDraw() ? PracticeCore::COLOR : TextFormat::RED) . $duelInfo->getLoserDisplayName() . "'s " . TextFormat::GRAY . "Inventory", 0, "textures/blocks/trapped_chest_front.png");
			if(isset($info["replay"])){
				$form->addButton(TextFormat::GRAY . "View " . TextFormat::GOLD . "Replay", 0, "textures/ui/timer.png");
			}
		}
		$form->addExtraData("info", $info);
		$form->addExtraData("opponent", $opponent);
		$form->addExtraData("kit", $kit);
		$form->addExtraData("ranked", $ranked);
		$form->addExtraData("info", $info);
		$player->sendForm($form);
	}
}
