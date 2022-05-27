<?php

declare(strict_types=1);

namespace zodiax\forms\display\cosmetic\types;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\player\misc\cosmetic\CosmeticManager;
use zodiax\player\misc\cosmetic\misc\CosmeticItem;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function array_values;
use function is_int;

class EditCosmeticForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(($session = PlayerManager::getSession($player)) === null){
			return;
		}
		if(!isset($args[0]) || ($type = $args[0]) === null || !is_int($type)){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["type"]) && isset($extraData["items"]) && isset($extraData["items"][$data]) && ($session = PlayerManager::getSession($player)) !== null){
				$type = $extraData["type"];
				$item = $extraData["items"][$data];
				if($item instanceof CosmeticItem){
					$session->getItemInfo()->setCosmetic($type, $item->getId());
					CosmeticManager::setStrippedSkin($player, $player->getSkin());
					$msg = match ($type) {
						CosmeticManager::ARTIFACT => "Applying artifact ",
						CosmeticManager::CAPE => "Applying cape ",
						CosmeticManager::PROJECTILE => "You have successfully changed projectile to ",
						CosmeticManager::KILLPHRASE => "You have successfully changed killphrase to ",
					};
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . $msg . $item->getDisplayName(true));
				}else{
					$session->getItemInfo()->setCosmetic($type, "0");
				}
			}
		});

		$ItemInfo = $session->getItemInfo();
		$title = match ($type) {
			CosmeticManager::ARTIFACT => PracticeCore::COLOR . "Select " . TextFormat::WHITE . "Artifact",
			CosmeticManager::CAPE => PracticeCore::COLOR . "Select " . TextFormat::WHITE . "Cape",
			CosmeticManager::PROJECTILE => PracticeCore::COLOR . "Select " . TextFormat::WHITE . "Projectile",
			CosmeticManager::KILLPHRASE => PracticeCore::COLOR . "Select " . TextFormat::WHITE . "KillPhrase",
		};
		$itemsIds = match ($type) {
			CosmeticManager::ARTIFACT => $ItemInfo->getOwnedArtifact(),
			CosmeticManager::CAPE => $ItemInfo->getOwnedCape(),
			CosmeticManager::PROJECTILE => $ItemInfo->getOwnedProjectile(),
			CosmeticManager::KILLPHRASE => $ItemInfo->getOwnedKillPhrase(),
		};
		$items = CosmeticManager::getCosmeticItemFromList($itemsIds, $type);
		$form->setTitle(PracticeUtil::formatTitle($title));
		$form->setContent("");
		foreach($items as $item){
			$form->addButton($item->getDisplayName(true));
		}
		$form->addExtraData("type", $type);
		$form->addExtraData("items", array_values($items));
		$player->sendForm($form);
	}
}