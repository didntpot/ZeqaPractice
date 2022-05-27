<?php

declare(strict_types=1);

namespace zodiax\forms\display\cosmetic\shop\fragmentize;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\player\info\ItemInfo;
use zodiax\player\misc\cosmetic\misc\CosmeticItem;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class FragmentizeConfirm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(PlayerManager::getSession($player) === null || !isset($args[0]) || ($item = $args[0]) === null || !$item instanceof CosmeticItem){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data === 0 && isset($extraData["item"])){
				PlayerManager::getSession($player)->getItemInfo()->alterCosmeticItem($player, $extraData["item"], true, true, true);
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Recycle " . TextFormat::WHITE . "Confirmation"));
		$form->setContent("Do you want to recycle : \n" . $item->getDisplayName(true) . " for" . TextFormat::AQUA . " " . ItemInfo::getFramentizeAmount($item->getRarity()) . TextFormat::RESET . " shards");
		$form->addButton(TextFormat::BOLD . "Yes", 0, "textures/ui/confirm.png");
		$form->addButton(TextFormat::BOLD . "No", 0, "textures/ui/cancel.png");
		$form->addExtraData("item", $item);
		$player->sendForm($form);
	}
}