<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\duel;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\duel\DuelHandler;
use zodiax\forms\types\SimpleForm;
use zodiax\kits\DefaultKit;
use zodiax\kits\KitsManager;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function count;

class DuelForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && $data !== null && isset($extraData["kits"]) && isset($extraData["kits"][$data]) && isset($extraData["ranked"])){
				$kit = $extraData["kits"][$data];
				if($kit instanceof DefaultKit){
					DuelHandler::placeInQueue($player, $kit->getName(), $extraData["ranked"]);
				}
			}
		});

		$ranked = $args[0]["ranked"];
		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . ($ranked ? "Ranked" : "Unranked") . TextFormat::WHITE . " Duel"));
		$form->setContent("");
		if(count($duels = KitsManager::getDuelKits()) <= 0){
			$form->addButton(TextFormat::GRAY . "None");
		}else{
			foreach($duels as $duel){
				$button = TextFormat::GRAY . $duel->getName() . "\n" . TextFormat::WHITE . DuelHandler::getPlayersInQueue($ranked, $duel->getName()) . PracticeCore::COLOR . " In-Queued";
				$form->addButton($button, 0, $duel->getMiscKitInfo()->getTexture());
			}
			$form->addExtraData("kits", $duels);
			$form->addExtraData("ranked", $ranked);
		}
		$player->sendForm($form);
	}
}