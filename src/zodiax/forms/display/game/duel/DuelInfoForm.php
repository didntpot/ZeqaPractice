<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\duel;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\duel\ReplayHandler;
use zodiax\forms\types\SimpleForm;
use zodiax\game\inventories\menus\PostMatchInv;
use zodiax\player\info\duel\DuelInfo;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class DuelInfoForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["info"]) && ($session = PlayerManager::getSession($player)) !== null && $session->isInHub()){
				if(($info = $extraData["info"]) !== null){
					switch($data){
						case 0:
							(new PostMatchInv($info["info"], $player->getPosition(), true))->send($player);
							break;
						case 1:
							(new PostMatchInv($info["info"], $player->getPosition(), false))->send($player);
							break;
						case 2:
							if(isset($info["replay"])){
								ReplayHandler::startReplay($player, $info["replay"]);
							}else{
								DuelHistoryForm::onDisplay($player);
							}
							break;
						case 3:
							DuelHistoryForm::onDisplay($player);
							break;
					}
				}
			}
		});

		$info = $args[0];
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
			$form->addButton(TextFormat::RED . "Back", 0, "textures/gui/newgui/XPress.png");
			$form->addExtraData("info", $info);
			$player->sendForm($form);
		}
	}
}