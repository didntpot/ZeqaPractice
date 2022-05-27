<?php

declare(strict_types=1);

namespace zodiax\forms\display\kits\edit\effects;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\kits\DefaultKit;
use zodiax\kits\KitsManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\utils\EffectInformation;
use function is_numeric;

class EditKitEffect{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($kit = $args[0]) === null || !$kit instanceof DefaultKit){
			return;
		}
		if(!isset($args[1]) || ($effect = $args[1]) === null || !$effect instanceof EffectInstance){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["kit"], $extraData["effect"])){
				$kit = $extraData["kit"];
				$effect = $extraData["effect"];
				if(!is_numeric($duration = (int) $data[2]) || !is_numeric($amplifier = (int) $data[3])){
					return;
				}
				$duration = $duration * 20;
				if($effect->getAmplifier() !== $amplifier){
					$effect->setAmplifier($amplifier);
				}
				if($effect->getDuration() !== $duration){
					$effect->setDuration($duration);
				}
				$kit->getEffectsInfo()->addEffect($effect);
				KitsManager::saveKit($kit);
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully edited {$kit->getName()}'s effect " . TextFormat::GRAY . $kit->getName());
			}
		});

		$effectInformation = EffectInformation::getInformation($effect);
		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Edit " . TextFormat::WHITE . "Effect"));
		$form->addLabel("Edits the selected effect and saves it to the {$kit->getName()} kit");
		$form->addLabel("Effect: " . $effectInformation->getName());
		$form->addInput("Effect Duration (In Seconds)", (string) ($effect->getDuration() / 20));
		$form->addInput("Effect Strength (Amplifier)", (string) $effect->getAmplifier());
		$form->addExtraData("effect", $effect);
		$form->addExtraData("kit", $kit);
		$player->sendForm($form);
	}
}