<?php

declare(strict_types=1);

namespace zodiax\forms\display\cosmetic;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\display\cosmetic\types\DisguiseForm;
use zodiax\forms\display\cosmetic\types\EditCosmeticForm;
use zodiax\forms\display\cosmetic\types\EditCustomTagForm;
use zodiax\forms\display\cosmetic\types\EditPotColorForm;
use zodiax\forms\types\SimpleForm;
use zodiax\player\misc\cosmetic\CosmeticManager;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class CosmeticMenuForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(($session = PlayerManager::getSession($player)) === null){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null){
				switch($data){
					case 0:
						EditCosmeticForm::onDisplay($player, CosmeticManager::CAPE);
						break;
					case 1:
						EditCosmeticForm::onDisplay($player, CosmeticManager::ARTIFACT);
						break;
					case 2:
						EditCosmeticForm::onDisplay($player, CosmeticManager::PROJECTILE);
						break;
					case 3:
						EditCosmeticForm::onDisplay($player, CosmeticManager::KILLPHRASE);
						break;
					case 4:
						EditPotColorForm::onDisplay($player);
						break;
					case 5:
						EditCustomTagForm::onDisplay($player);
						break;
					case 6:
						DisguiseForm::onDisplay($player);
						break;
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Cosmetic " . TextFormat::WHITE . "Settings"));
		$ItemInfo = $session->getItemInfo();
		$content = "Cape: " . CosmeticManager::getCapeFromId($ItemInfo->getCape())->getDisplayName(true) . "\n";
		$content .= "Artifact: " . CosmeticManager::getArtifactFromId($ItemInfo->getArtifact())->getDisplayName(true) . "\n";
		$content .= "Projectile: " . CosmeticManager::getProjectileFromId($ItemInfo->getProjectile())->getDisplayName(true) . "\n";
		$content .= "KillPhrase: " . CosmeticManager::getKillPhraseFromId($ItemInfo->getKillPhrase())->getDisplayName(true) . "\n";
		$potColor = $ItemInfo->getPotColor();
		$content .= "PotColor: $potColor[0] (R) $potColor[1] (G) $potColor[2] (B)\n";
		$content .= "CustomTag: " . ($ItemInfo->getTag() === "" ? "Default" : $ItemInfo->getTag()) . "\n\n";
		$form->setContent($content);
		$form->addButton(PracticeCore::COLOR . "Change " . TextFormat::WHITE . "Cape", 0, "zeqa/textures/ui/more/changecape.png");
		$form->addButton(PracticeCore::COLOR . "Change " . TextFormat::WHITE . "Artifact", 0, "textures/ui/dressing_room_skins.png");
		$form->addButton(PracticeCore::COLOR . "Change " . TextFormat::WHITE . "Projectile", 0, "zeqa/textures/ui/more/changeprojectiletrail.png");
		$form->addButton(PracticeCore::COLOR . "Change " . TextFormat::WHITE . "KillPhrase", 0, "zeqa/textures/ui/more/killphrase.png");
		$form->addButton(PracticeCore::COLOR . "Change " . TextFormat::WHITE . "PotColor", 0, "textures/items/potion_bottle_splash_heal.png");
		$form->addButton(PracticeCore::COLOR . "Change " . TextFormat::WHITE . "CustomTag", 0, "zeqa/textures/ui/more/customtag.png");
		if($session->getRankInfo()->hasCreatorPermissions() || $session->getRankInfo()->hasHelperPermissions()){
			$form->addButton(PracticeCore::COLOR . "Disguise " . TextFormat::WHITE . "Setting", 0, "zeqa/textures/ui/more/disguise.png");
		}
		$player->sendForm($form);
	}
}