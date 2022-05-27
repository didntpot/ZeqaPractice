<?php

declare(strict_types=1);

namespace zodiax\forms\display\kits\edit\effects;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\kits\DefaultKit;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\utils\EffectInformation;
use function count;

class KitEffectSelectorMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($kit = $args[0]) === null || !$kit instanceof DefaultKit){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["formType"], $extraData["kit"], $extraData["effects"])){
				$kit = $extraData["kit"];
				$action = (string) $extraData["formType"];
				$effects = $extraData["effects"];
				if(count($effects) <= 0){
					return;
				}
				$effect = $effects[(int) $data];
				switch($action){
					case "add" :
						AddKitEffect::onDisplay($player, $kit, $effect);
						break;
					case "edit" :
						EditKitEffect::onDisplay($player, $kit, $effect);
						break;
					case "remove" :
						RemoveKitEffect::onDisplay($player, $kit, $effect);
						break;
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Select " . TextFormat::WHITE . "Effect"));
		$form->setContent("Select the effect to add/remove/edit");
		if(isset($args[1])){
			$outputMenu = $args[1];
		}
		if(isset($outputMenu) && $outputMenu !== "add"){
			self::setEffects($form, $kit);
		}else{
			self::setEffects($form);
		}
		$form->addExtraData("kit", $kit);
		if(isset($outputMenu)){
			$form->addExtraData("formType", $outputMenu);
		}
		$player->sendForm($form);
	}

	private static function setEffects(SimpleForm $form, ?DefaultKit $kit = null) : void{
		if($kit !== null){
			$inputEffects = [];
			$effects = $kit->getEffectsInfo()->getEffects();
			if(count($effects) <= 0){
				$form->addButton(TextFormat::GRAY . "None");
				$form->addExtraData("effects", []);
				return;
			}
			foreach($effects as $effect){
				$effectInformation = EffectInformation::getInformation($effect);
				if($effectInformation !== null){
					$texture = $effectInformation->getFormTexture();
					if($texture !== ""){
						$form->addButton($effectInformation->getName(), 0, $texture);
					}else{
						$form->addButton($effectInformation->getName());
					}
					$inputEffects[] = $effect;
				}
			}
			$form->addExtraData("effects", $inputEffects);
			return;
		}

		$effects = [];
		$effectsInformation = EffectInformation::getAll();
		foreach($effectsInformation as $effectInformation){
			$instance = $effectInformation->createInstance();
			if($instance !== null){
				$texture = $effectInformation->getFormTexture();
				if($texture !== ""){
					$form->addButton($effectInformation->getName(), 0, $texture);
				}else{
					$form->addButton($effectInformation->getName());
				}
				$effects[] = $instance;
			}
		}
		$form->addExtraData("effects", $effects);
	}
}