<?php

declare(strict_types=1);

namespace zodiax\forms\display\kits\edit\effects;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\kits\DefaultKit;
use zodiax\kits\KitsManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\utils\EffectInformation;
use function implode;

class RemoveKitEffect{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($kit = $args[0]) === null || !$kit instanceof DefaultKit){
			return;
		}
		if(!isset($args[1]) || ($effect = $args[1]) === null || !$effect instanceof EffectInstance){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data === 0 && isset($extraData["kit"], $extraData["effect"])){
				$kit = $extraData["kit"];
				$effect = $extraData["effect"];
				$kit->getEffectsInfo()->removeEffect($effect);
				KitsManager::saveKit($kit);
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully removed {$kit->getName()}'s effect " . TextFormat::GRAY . $kit->getName());
			}
		});

		$effectInformation = EffectInformation::getInformation($effect);
		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Remove " . TextFormat::WHITE . "Effect"));
		$content = ["Are you sure you want to remove the effect from the {$kit->getName()} kit?", "", "Effect: " . $effectInformation->getName(), "", "Select " . TextFormat::BOLD . "yes" . TextFormat::RESET . " to remove, or " . TextFormat::BOLD . "no" . TextFormat::RESET . " to cancel"];
		$form->setContent(implode("\n", $content));
		$form->addButton(TextFormat::BOLD . "Yes", 0, "textures/ui/confirm.png");
		$form->addButton(TextFormat::BOLD . "No", 0, "textures/ui/cancel.png");
		$form->addExtraData("kit", $kit);
		$form->addExtraData("effect", $effect);
		$player->sendForm($form);
	}
}