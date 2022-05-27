<?php

declare(strict_types=1);

namespace zodiax\forms\display\cosmetic\shop\gacha;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\data\log\LogMonitor;
use zodiax\forms\types\SimpleForm;
use zodiax\player\info\StatsInfo;
use zodiax\player\misc\cosmetic\CosmeticManager;
use zodiax\player\misc\cosmetic\gacha\Gacha;
use zodiax\player\misc\cosmetic\gacha\GachaHandler;
use zodiax\player\misc\cosmetic\gacha\GachaTask;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class GachaDetail{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($gacha = $args[0]) === null || !$gacha instanceof Gacha){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["gacha"]) && isset($extraData["available_currency"]) && ($session = PlayerManager::getSession($player)) !== null){
				$gacha = $extraData["gacha"];
				$currency = $extraData["available_currency"];
				$actual_currency = (int) ($data / 2);
				$paymentSuccess = $session->getStatsInfo()->addCurrency($currency[$actual_currency], -$gacha->getCurrency($currency[$actual_currency]) * ($data % 2 == 0 ? 1 : 10));
				if($paymentSuccess){
					LogMonitor::cosmeticLog("Shop : {$player->getName()} buy {$gacha->getName()}");
					$msg = PracticeCore::PREFIX . TextFormat::GRAY . "You have successfully purchased " . TextFormat::RESET . $gacha->getName() . ($data % 2 == 0 ? " (1)" : " (10+1)");
					$times = ($data % 2 == 0 ? 1 : 11);
					for($i = 0; $i < $times; $i++){
						if(PracticeCore::isPackEnable()){
							new GachaTask($player, $gacha);
						}else{
							if(($item = GachaHandler::randomItemFromGacha($gacha->getId())) !== null){
								$session->getItemInfo()->alterCosmeticItem($player, $item, false, true, true, !$session->getSettingsInfo()->isAutoRecycle());
								if($item->getRarity() === CosmeticManager::SR || $item->getRarity() === CosmeticManager::UR){
									$type = match ($item->getType()) {
										CosmeticManager::CAPE => "cape",
										CosmeticManager::ARTIFACT => "artifact",
										CosmeticManager::PROJECTILE => "projectile",
										CosmeticManager::KILLPHRASE => "killphrase"
									};
									$announce = PracticeCore::PREFIX . TextFormat::GREEN . $player->getDisplayName() . TextFormat::GRAY . " has obtained an extremely rare $type " . $item->getDisplayName(true);
									foreach(PlayerManager::getOnlinePlayers() as $p){
										$p->sendMessage($announce);
									}
								}
							}
						}
					}
				}else{
					$msg = PracticeCore::PREFIX . TextFormat::RED . "Purchased Fail, Not enough coin(s) or shard(s)";
				}
				$player->sendMessage($msg);
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Gacha " . TextFormat::WHITE . "Detail"));
		$content = TextFormat::YELLOW . TextFormat::BOLD . "CRATE DROPRATE\n\n" . TextFormat::RESET;
		$content .= $gacha->getGachaDetailText();
		$content .= "\n";
		$form->setContent($content);
		$available_currency = [];
		foreach($gacha->getCurrency() as $curren => $amount){
			$available_currency[] = $curren;
			switch($curren){
				case StatsInfo::COIN:
					$form->addButton(TextFormat::WHITE . "Buy (1) " . PracticeCore::COLOR . $amount . TextFormat::WHITE . " Coins");
					$form->addButton(TextFormat::WHITE . "Buy (10+1) " . PracticeCore::COLOR . ($amount * 10) . TextFormat::WHITE . " Coins");
					break;
				case StatsInfo::SHARD:
					$form->addButton(TextFormat::WHITE . "Buy (1) " . TextFormat::AQUA . $amount . TextFormat::WHITE . " Shards");
					$form->addButton(TextFormat::WHITE . "Buy (10+1) " . TextFormat::AQUA . ($amount * 10) . TextFormat::WHITE . " Shards");
					break;
			}
		}
		$form->addExtraData("gacha", $gacha);
		$form->addExtraData("available_currency", $available_currency);
		$player->sendForm($form);
	}
}