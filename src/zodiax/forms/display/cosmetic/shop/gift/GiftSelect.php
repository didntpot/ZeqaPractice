<?php

declare(strict_types=1);

namespace zodiax\forms\display\cosmetic\shop\gift;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\player\misc\cosmetic\CosmeticManager;
use zodiax\player\misc\cosmetic\gacha\Gacha;
use zodiax\player\misc\cosmetic\gacha\GachaHandler;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function is_int;

class GiftSelect{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($gacha = $args[0]) === null || !$gacha instanceof Gacha || !isset($args[1]) || !is_int($currency = $args[1])){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["players"]) && isset($extraData["gacha"]) && isset($extraData["currency"]) && ($session = PlayerManager::getSession($player)) !== null){
				$name = $extraData["players"][$data[0]];
				$gacha = $extraData["gacha"];
				$currency = $extraData["currency"];
				if(($sessionTo = PlayerManager::getSession($to = PlayerManager::getPlayerExact($name, true))) !== null){
					$paymentSuccess = $session->getStatsInfo()->addCurrency($currency, -$gacha->getCurrency($currency));
					if($paymentSuccess){
						$msg = PracticeCore::PREFIX . TextFormat::GRAY . "You have successfully sent " . TextFormat::RESET . $gacha->getName() . TextFormat::GRAY . " to " . TextFormat::RESET . $to->getDisplayName() . TextFormat::RESET;
						if(($item = GachaHandler::randomItemFromGacha($gacha->getId())) !== null){
							$sessionTo->getItemInfo()->alterCosmeticItem($to, $item, false, true, true);
							if($item->getRarity() === CosmeticManager::SR || $item->getRarity() === CosmeticManager::UR){
								$type = match ($item->getType()) {
									CosmeticManager::CAPE => "cape",
									CosmeticManager::ARTIFACT => "artifact",
									CosmeticManager::PROJECTILE => "projectile",
									CosmeticManager::KILLPHRASE => "killphrase"
								};
								$announce = PracticeCore::PREFIX . TextFormat::GREEN . $to->getDisplayName() . TextFormat::GRAY . " has obtained an extremely rare $type " . $item->getDisplayName(true);
								foreach(PlayerManager::getOnlinePlayers() as $p){
									$p->sendMessage($announce);
								}
							}
						}
					}else{
						$msg = PracticeCore::PREFIX . TextFormat::RED . "Purchased Fail, Not enough coin(s) or shard(s)";
					}
				}else{
					$msg = PracticeCore::PREFIX . TextFormat::RED . "Purchased Fail, Player not found";
				}
				$player->sendMessage($msg);
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Gifting " . TextFormat::WHITE . "Selection"));
		$form->addDropdown("Give to:", $dropdownArr = PlayerManager::getListDisplayNames());
		$form->addExtraData("players", $dropdownArr);
		$form->addExtraData("gacha", $gacha);
		$form->addExtraData("currency", $currency);
		$player->sendForm($form);
	}
}