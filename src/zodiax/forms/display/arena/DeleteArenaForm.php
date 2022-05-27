<?php

declare(strict_types=1);

namespace zodiax\forms\display\arena;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\arena\ArenaManager;
use zodiax\forms\types\SimpleForm;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function implode;

class DeleteArenaForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($arena = $args[0]) === null){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data === 0){
				$arena = $extraData["arena"];
				if(ArenaManager::deleteArena($name = $arena->getName())){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Successfully deleted arena " . TextFormat::GRAY . $name);
				}else{
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Can not delete an arena");
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Delete " . TextFormat::WHITE . "Arena"));
		$content = ["Are you sure you want to delete the {$arena->getName()} arena from the server?", "", "Arena: " . $arena->getName(), "", "Select " . TextFormat::BOLD . "yes" . TextFormat::RESET . " to delete, or " . TextFormat::BOLD . "no" . TextFormat::RESET . " to cancel"];
		$form->setContent(implode("\n", $content));
		$form->addButton(TextFormat::BOLD . "Yes", 0, "textures/ui/confirm.png");
		$form->addButton(TextFormat::BOLD . "No", 0, "textures/ui/cancel.png");
		$form->addExtraData("arena", $arena);
		$player->sendForm($form);
	}
}