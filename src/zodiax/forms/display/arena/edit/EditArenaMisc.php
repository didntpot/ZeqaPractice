<?php

declare(strict_types=1);

namespace zodiax\forms\display\arena\edit;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\arena\types\Arena;
use zodiax\arena\types\DuelArena;
use zodiax\arena\types\FFAArena;
use zodiax\forms\types\CustomForm;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class EditArenaMisc{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($arena = $args[0]) === null || !$arena instanceof Arena){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["arena"])){
				$arena = $extraData["arena"];
				if($arena instanceof FFAArena){
					$arena->setCanInterrupt($data[1]);
				}elseif($arena instanceof DuelArena){
					$maxHeight = (int) $data[1];
					if($maxHeight >= 0 && $maxHeight <= 255){
						$arena->setMaxHeight($maxHeight);
					}else{
						$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Max height must be between 0 and 255");
						return;
					}
				}else{
					return;
				}
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully edited {$arena->getName()}'s misc " . TextFormat::GRAY . $arena->getName());
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Edit " . TextFormat::WHITE . "Misc"));
		$form->addLabel("Edit the information of the {$arena->getName()} arena");
		if($arena instanceof FFAArena){
			$form->addToggle("canInterrupt", $arena->canInterrupt());
		}elseif($arena instanceof DuelArena){
			$form->addInput("maxHeight", (string) $arena->getMaxHeight(), (string) $arena->getMaxHeight());
		}
		$form->addExtraData("arena", $arena);
		$player->sendForm($form);
	}
}