<?php

declare(strict_types=1);

namespace zodiax\forms\display\cosmetic\types;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class EditPotColorForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null && ($session = PlayerManager::getSession($player)) !== null){
				if((int) $data[1] < 0 || (int) $data[1] > 255){
					$data[1] = 0;
				}
				if((int) $data[2] < 0 || (int) $data[2] > 255){
					$data[2] = 0;
				}
				if((int) $data[3] < 0 || (int) $data[3] > 255){
					$data[3] = 0;
				}
				$session->getItemInfo()->setPotColor([(int) $data[1], (int) $data[2], (int) $data[3]]);
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully edited pot's color");
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Pot " . TextFormat::WHITE . "Color"));
		$form->addLabel("Edit the pot color");
		$form->addInput("R", "0-255");
		$form->addInput("G", "0-255");
		$form->addInput("B", "0-255");
		$player->sendForm($form);
	}
}