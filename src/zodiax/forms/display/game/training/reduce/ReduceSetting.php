<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\training\reduce;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\training\types\ReducePractice;

class ReduceSetting{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($reduce = $args[0]) === null || !$reduce instanceof ReducePractice){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInReduce() && $data !== null && isset($extraData["reduce"])){
				$reduce = $extraData["reduce"];
				if($reduce instanceof ReducePractice){
					$reduce->setExtraHitDelaySeconds((int) $data[0]);
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Reduce" . TextFormat::WHITE . " Settings"));
		$form->addSlider("Extra Hit Cooldown", 0, 5, 1, $reduce->getExtraHitDelaySeconds());
		$form->addExtraData("reduce", $reduce);
		$player->sendForm($form);
	}
}