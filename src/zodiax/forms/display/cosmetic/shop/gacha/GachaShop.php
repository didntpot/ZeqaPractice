<?php

declare(strict_types=1);

namespace zodiax\forms\display\cosmetic\shop\gacha;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\display\cosmetic\shop\gift\GiftDetail;
use zodiax\forms\types\SimpleForm;
use zodiax\player\misc\cosmetic\gacha\GachaHandler;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function array_values;
use function count;
use function is_bool;

class GachaShop{

	public static function onDisplay(Player $player, ...$args) : void{
		$isGiftShop = false;
		if(isset($args[0])){
			if(!is_bool($args[0])){
				return;
			}
			$isGiftShop = $args[0];
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData) use ($isGiftShop){
			if($data !== null && isset($extraData["gachas"]) && isset($extraData["gachas"][$data])){
				if($isGiftShop){
					GiftDetail::onDisplay($player, $extraData["gachas"][$data]);
				}else{
					GachaDetail::onDisplay($player, $extraData["gachas"][$data]);
				}
			}
		});

		if($isGiftShop){
			$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Gift " . TextFormat::WHITE . "Shop"));
		}else{
			$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Cosmetic " . TextFormat::WHITE . "Shop"));
		}
		$form->setContent("");
		if(count($gachas = GachaHandler::getGacha()) > 0){
			foreach($gachas as $gacha){
				$form->addButton(TextFormat::BOLD . $gacha->getName(), 0, $gacha->getTexture());
			}
			$form->addExtraData("gachas", array_values($gachas));
		}else{
			$form->addButton(TextFormat::GRAY . "None");
		}
		$player->sendForm($form);
	}
}