<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\duel;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class ReplayForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new CustomForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && $data !== null && isset($extraData["replay"])){
				$replay = $extraData["replay"];
				$replay->setReplaySecs((int) $data[0]);
			}
		});

		$replay = $args[0]["replay"];
		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Replay " . TextFormat::WHITE . "Settings"));
		$form->addSlider(PracticeCore::COLOR . "Skip " . TextFormat::WHITE . "Seconds", 1, 10, 1, (int) $replay->getReplaySecs());
		$form->setExtraData(["replay" => $replay]);
		$player->sendForm($form);
	}
}