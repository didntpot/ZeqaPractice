<?php

declare(strict_types=1);

namespace zodiax\forms\display\kits;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\kits\DefaultKit;
use zodiax\kits\KitsManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function implode;

class DeleteKitForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($kit = $args[0]) === null || !$kit instanceof DefaultKit){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data === 0){
				$kit = $extraData["kit"];
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Successfully deleted kit " . TextFormat::GRAY . $kit->getName());
				KitsManager::delete($kit);
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Delete " . TextFormat::WHITE . "Kit"));
		$content = ["Are you sure you want to delete the {$kit->getName()} kit from the server?", "", "Kit: " . $kit->getName(), "", "Select " . TextFormat::BOLD . "yes" . TextFormat::RESET . " to delete, or " . TextFormat::BOLD . "no" . TextFormat::RESET . " to cancel"];
		$form->setContent(implode("\n", $content));
		$form->addButton(TextFormat::BOLD . "Yes", 0, "textures/ui/confirm.png");
		$form->addButton(TextFormat::BOLD . "No", 0, "textures/ui/cancel.png");
		$form->addExtraData("kit", $kit);
		$player->sendForm($form);
	}
}