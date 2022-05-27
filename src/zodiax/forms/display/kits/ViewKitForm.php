<?php

declare(strict_types=1);

namespace zodiax\forms\display\kits;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\kits\DefaultKit;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\utils\EffectInformation;
use function implode;

class ViewKitForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($kit = $args[0]) === null || !$kit instanceof DefaultKit){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){

		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Kit " . TextFormat::WHITE . "Info"));
		$effects = "\n";
		foreach($kit->getEffectsInfo() as $effect){
			$effectInformation = EffectInformation::getInformation($effect);
			$effects .= "Effect: " . $effectInformation->getName() . "\n";
			$effects .= "Effect Duration (In Seconds): " . $effect->getDuration() / 20 . "\n";
			$effects .= "Effect Strength (Amplifier): " . $effect->getAmplifier() . "\n";
		}
		$misckitInfo = $kit->getMiscKitInfo();
		$misc = "\n";
		$misc .= "isFFAEnabled: " . ($misckitInfo->isFFAEnabled() ? "True" : "False") . "\n";
		$misc .= "isDuelsEnabled: " . ($misckitInfo->isDuelsEnabled() ? "True" : "False") . "\n";
		$misc .= "isReplaysEnabled: " . ($misckitInfo->isReplaysEnabled() ? "True" : "False") . "\n";
		$misc .= "isBotEnabled: " . ($misckitInfo->isBotEnabled() ? "True" : "False") . "\n";
		$misc .= "isEventEnabled: " . ($misckitInfo->isEventEnabled() ? "True" : "False") . "\n";
		$misc .= "canDamagePlayers: " . ($misckitInfo->canDamagePlayers() ? "True" : "False") . "\n";
		$misc .= "canBuild: " . ($misckitInfo->canBuild() ? "True" : "False") . "\n";
		$misc .= "Texture: " . $misckitInfo->getTexture() . "\n";
		$knockbackInfo = $kit->getKnockbackInfo();
		$knockback = "\n";
		$knockback .= "Horizontal (X, Z) Knockback: " . $knockbackInfo->getHorizontalKb() . "\n";
		$knockback .= "Vertical (Y) Knockback: " . $knockbackInfo->getVerticalKb() . "\n";
		$knockback .= "Max Height: " . $knockbackInfo->getMaxHeight() . "\n";
		$knockback .= "Can Revert (Revert knockback once it hit height limit): " . ($knockbackInfo->canRevert() ? "True" : "False") . "\n";
		$knockback .= "Attack Delay: " . $knockbackInfo->getSpeed() . "\n";
		$content = ["Kit: " . $kit->getName(), "", "Effects: " . $effects, "", "Misc: " . $misc, "", "Knockback: " . $knockback];
		$form->setContent(implode("\n", $content));
		$form->addButton(TextFormat::BOLD . "Submit");
		$player->sendForm($form);
	}
}