<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\duel;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\duel\DuelHandler;
use zodiax\forms\types\SimpleForm;
use zodiax\kits\KitsManager;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class DuelMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null && ($session = PlayerManager::getSession($player)) !== null && $session->isInHub()){
				switch($data){
					case 0:
						DuelForm::onDisplay($player, ["ranked" => true]);
						break;
					case 1:
						DuelForm::onDisplay($player, ["ranked" => false]);
						break;
					case 2:
						DuelRequestForm::onDisplay($player);
						break;
					case 3:
						DuelInboxForm::onDisplay($player);
						break;
					case 4:
						DuelHistoryForm::onDisplay($player);
						break;
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Play " . TextFormat::WHITE . "Duels"));
		$form->setContent("");
		$ranked = 0;
		$unranked = 0;
		foreach(KitsManager::getDuelKits(true) as $kit){
			$ranked += DuelHandler::getPlayersInQueue(true, $kit);
			$unranked += DuelHandler::getPlayersInQueue(false, $kit);
		}
		$form->addButton(PracticeCore::COLOR . "Ranked " . TextFormat::WHITE . "Duel\n" . TextFormat::WHITE . $ranked . PracticeCore::COLOR . " In-Queued", 0, "zeqa/textures/ui/more/ranked_duel.png");
		$form->addButton(PracticeCore::COLOR . "Unranked " . TextFormat::WHITE . "Duel\n" . TextFormat::WHITE . $unranked . PracticeCore::COLOR . " In-Queued", 0, "zeqa/textures/ui/more/unranked_duel.png");
		$form->addButton(PracticeCore::COLOR . "Request " . TextFormat::WHITE . "Duel", 0, "textures/items/paper.png");
		$form->addButton(PracticeCore::COLOR . "Incoming " . TextFormat::WHITE . "Invites", 0, "zeqa/textures/ui/more/duelinbox.png");
		$form->addButton(PracticeCore::COLOR . "Match " . TextFormat::WHITE . "History", 0, "zeqa/textures/ui/more/duel_history.png");
		$player->sendForm($form);
	}
}
