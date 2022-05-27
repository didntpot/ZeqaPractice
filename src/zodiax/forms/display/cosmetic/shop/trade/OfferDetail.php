<?php

declare(strict_types=1);

namespace zodiax\forms\display\cosmetic\shop\trade;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\player\misc\cosmetic\trade\TradeHandler;
use zodiax\player\misc\cosmetic\trade\TradeOffer;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class OfferDetail{

	public static function onDisplay(Player $player, ...$args) : void{
		if(PlayerManager::getSession($player) === null || !isset($args[0]) || ($offer = $args[0]) === null || !$offer instanceof TradeOffer){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData) use ($offer){
			if($data !== null && isset($extraData["offer"])){
				if($data === 0){
					if(($offer->getState() === TradeOffer::FIRST && $player->getName() === $offer->getTo()) || ($offer->getState() === TradeOffer::SECOND && $player->getName() === $offer->getFrom())){
						if($offer->getState() === TradeOffer::FIRST){
							CreateOffer::onDisplay($player, TradeOffer::FIRST, $offer);
						}elseif($offer->getState() === TradeOffer::SECOND){
							$offer->offerConfirm();
							TradeHandler::removeOffer($offer);
						}
					}
				}elseif($data === 1){
					if(($offer->getState() === TradeOffer::FIRST && $player->getName() === $offer->getTo()) || ($offer->getState() === TradeOffer::SECOND && $player->getName() === $offer->getFrom())){
						if($offer->getState() === TradeOffer::FIRST){
							$from = PlayerManager::getPlayerExact($offer->getFrom());
							$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You have declined your offer to {$from?->getDisplayName()}");
							$from?->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "{$player->getDisplayName()} declined your offer");
						}elseif($offer->getState() === TradeOffer::SECOND){
							$to = PlayerManager::getPlayerExact($offer->getTo());
							$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You have declined your offer to {$to?->getDisplayName()}");
							$to?->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "{$player->getDisplayName()} declined your offer");
						}
					}else{
						if($offer->getState() === TradeOffer::FIRST){
							$to = PlayerManager::getPlayerExact($offer->getTo());
							$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You have canceled your offer to {$to?->getDisplayName()}");
							$to?->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "{$player->getDisplayName()} canceled your offer");
						}elseif($offer->getState() === TradeOffer::SECOND){
							$from = PlayerManager::getPlayerExact($offer->getFrom());
							$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You have canceled your offer to {$from?->getDisplayName()}");
							$from?->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "{$player->getDisplayName()} canceled your offer");
						}
					}
					TradeHandler::removeOffer($offer);
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Offer " . TextFormat::WHITE . "Detail"));
		$form->setContent($offer->getDetail($player) . "\n");
		if(($offer->getState() === TradeOffer::FIRST && $player->getName() === $offer->getTo()) || ($offer->getState() === TradeOffer::SECOND && $player->getName() === $offer->getFrom())){
			$form->addButton(TextFormat::BOLD . "Accept", 0, "textures/ui/confirm.png");
			$form->addButton(TextFormat::BOLD . "Decline", 0, "textures/ui/cancel.png");
		}else{
			$form->addButton(TextFormat::BOLD . "Done", 0, "textures/ui/confirm.png");
			$form->addButton(TextFormat::BOLD . "Cancel", 0, "textures/ui/cancel.png");
		}
		$form->addExtraData("offer", $offer);
		$player->sendForm($form);
	}
}