<?php

declare(strict_types=1);

namespace zodiax\forms\display\cosmetic\shop\fragmentize;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\player\misc\cosmetic\CosmeticManager;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function array_values;
use function count;
use function is_int;

class FragmentizeDetail{

	public static function onDisplay(Player $player, ...$args) : void{
		if(($session = PlayerManager::getSession($player)) === null){
			return;
		}
		if(!isset($args[0]) || ($type = $args[0]) === null || !is_int($type)){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["items"]) && isset($extraData["items"][$data])){
				FragmentizeConfirm::onDisplay($player, $extraData["items"][$data]);
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Recycle " . TextFormat::WHITE . "Detail"));
		$form->setContent("");
		$itemInfo = $session->getItemInfo();
		$itemIds = match ($type) {
			CosmeticManager::ARTIFACT => $itemInfo->getOwnedArtifact(),
			CosmeticManager::CAPE => $itemInfo->getOwnedCape(),
			CosmeticManager::PROJECTILE => $itemInfo->getOwnedProjectile(),
			CosmeticManager::KILLPHRASE => $itemInfo->getOwnedKillPhrase(),
			default => [],
		};
		$items = CosmeticManager::getCosmeticItemFromList($itemIds, $type);
		foreach($items as $key => $item){
			if($item->getId() === "0" || !$item->isTradable() || $item->getRarity() == CosmeticManager::DEFAULT){
				unset($items[$key]);
			}
		}
		if(count($items) > 0){
			foreach($items as $item){
				$form->addButton($item->getDisplayName(true));
			}
			$form->addExtraData("items", array_values($items));
		}else{
			$form->addButton(TextFormat::GRAY . "None");
		}
		$player->sendForm($form);
	}
}