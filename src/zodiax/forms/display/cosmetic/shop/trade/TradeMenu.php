<?php

declare(strict_types=1);

namespace zodiax\forms\display\cosmetic\shop\trade;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\player\misc\cosmetic\trade\TradeOffer;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class TradeMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null){
				switch($data){
					case 0:
						CreateOffer::onDisplay($player, TradeOffer::PREPARE);
						break;
					case 1:
						OngoingOffer::onDisplay($player);
						break;
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Trade " . TextFormat::WHITE . "Menu"));
		$form->setContent("");
		$form->addButton(PracticeCore::COLOR . "Create " . TextFormat::WHITE . "Offer", 0, "textures/items/banner_pattern.png");
		$form->addButton(PracticeCore::COLOR . "Ongoing " . TextFormat::WHITE . "Offer", 0, "textures/items/book_written.png");
		$player->sendForm($form);
	}
}