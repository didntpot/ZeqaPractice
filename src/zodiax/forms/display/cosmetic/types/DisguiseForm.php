<?php

declare(strict_types=1);

namespace zodiax\forms\display\cosmetic\types;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function preg_match;
use function strlen;
use function strtolower;

class DisguiseForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null){
				$session = PlayerManager::getSession($player);
				if(!$session->isInHub()){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Please return to the lobby to disguise");
					return;
				}
				if(strtolower($newname = TextFormat::clean((string) $data[1])) === "reset"){
					$session->getDisguiseInfo()->setDisguised($player->getName());
				}else{
					if(!preg_match("/[^A-Za-z0-9]/", $newname)){
						if(strlen($newname) > 4 && strlen($newname) <= 12){
							if(PlayerManager::getPlayerExact($newname) !== null || PlayerManager::getPlayerExact($newname, true) !== null || PlayerManager::getPlayerByPrefix($newname) !== null){
								$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You can not disguise as online player");
								return;
							}
							$session->getDisguiseInfo()->setDisguised($newname);
						}else{
							$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You can only disguise using 5-12 characters");
						}
					}else{
						$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Only english characters");
					}
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Disguise " . TextFormat::WHITE . "Setting"));
		$form->addLabel("No Impersonation\nNo Toxic, Racism, Slurs\n\nPut 'Reset' for reset to default");
		$form->addInput("", $player->getDisplayName(), "");
		$player->sendForm($form);
	}
}