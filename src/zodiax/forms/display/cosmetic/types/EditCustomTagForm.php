<?php

declare(strict_types=1);

namespace zodiax\forms\display\cosmetic\types;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class EditCustomTagForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(($session = PlayerManager::getSession($player)) === null){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["tags"]) && ($session = PlayerManager::getSession($player)) !== null){
				if(($tag = $extraData["tags"][$data[0]]) === "Default"){
					$session->getItemInfo()->setTag();
				}else{
					$session->getItemInfo()->setTag($tag);
				}
				$session->updateNameTag();
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully edited custom tag");
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Select " . TextFormat::WHITE . "CustomTag"));
		$tags = $session->getItemInfo()->getOwnedTag();
		$flag = false;
		foreach($tags as $key => $tag){
			if($tag === ""){
				$tags[$key] = "Default";
				$flag = true;
				break;
			}
		}
		if(!$flag){
			$tags[] = "Default";
		}
		$form->addDropdown("Choose:", $tags);
		$form->addExtraData("tags", $tags);
		$player->sendForm($form);
	}
}