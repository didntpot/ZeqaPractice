<?php

declare(strict_types=1);

namespace zodiax\forms\display\cosmetic\shop\trade;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\player\misc\cosmetic\trade\TradeHandler;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function array_values;
use function count;

class OngoingOffer{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["tradeoffers"]) && isset($extraData["tradeoffers"][$data])){
				OfferDetail::onDisplay($player, $extraData["tradeoffers"][$data]);
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Ongoing " . TextFormat::WHITE . "Trade"));
		$form->setContent("");
		$tradeOffers = TradeHandler::getTrade();
		foreach($tradeOffers as $key => $trade){
			if($player->getName() !== $trade->getFrom() && $player->getName() !== $trade->getTo()){
				unset($tradeOffers[$key]);
			}
		}
		if(count($tradeOffers) > 0){
			foreach($tradeOffers as $trade){
				$form->addButton($trade->getTitle($player));
			}
			$form->addExtraData("tradeoffers", array_values($tradeOffers));
		}else{
			$form->addButton(TextFormat::GRAY . "None");
		}

		$player->sendForm($form);
	}
}