<?php

declare(strict_types=1);

namespace zodiax\forms\display\cosmetic\shop;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\display\cosmetic\shop\battlepass\BattlePassDetail;
use zodiax\forms\display\cosmetic\shop\fragmentize\FragmentizeMenu;
use zodiax\forms\display\cosmetic\shop\gacha\GachaShop;
use zodiax\forms\display\cosmetic\shop\trade\TradeMenu;
use zodiax\forms\types\SimpleForm;
use zodiax\player\info\StatsInfo;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class ShopMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		if(($session = PlayerManager::getSession($player)) === null){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null){
				switch($data){
					case 0:
						GachaShop::onDisplay($player);
						break;
					/*case 1:
						GachaShop::onDisplay($player, true);
						break;*/
					case 1:
						FragmentizeMenu::onDisplay($player);
						break;
					case 2:
						BattlePassDetail::onDisplay($player);
						break;
					case 3:
						TradeMenu::onDisplay($player);
						break;
				}
			}
		});

		$statsInfo = $session->getStatsInfo();
		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Shop " . TextFormat::WHITE . "Menu"));
		$content = TextFormat::GREEN . TextFormat::BOLD . "BALANCE\n" . TextFormat::RESET;
		$content .= "- " . TextFormat::YELLOW . "Coin" . TextFormat::WHITE . ": " . $statsInfo->getCurrency(StatsInfo::COIN) . "\n" . TextFormat::RESET;
		$content .= "- " . TextFormat::AQUA . "Shard" . TextFormat::WHITE . ": " . $statsInfo->getCurrency(StatsInfo::SHARD) . "\n\n";
		$form->setContent($content);
		$form->addButton(PracticeCore::COLOR . "Cosmetic " . TextFormat::WHITE . "Shop", 0, "zeqa/textures/ui/items/shop.png");
		//$form->addButton(PracticeCore::COLOR . "Gift " . TextFormat::WHITE . "Shop", 0, PracticeCore::isPackEnable() ? "zeqa/textures/ui/more/gift" : "textures/ui/gift_square.png");
		$form->addButton(PracticeCore::COLOR . "Recycle " . TextFormat::WHITE . "Cosmetic", 0, "zeqa/textures/ui/more/recycle.png");
		$form->addButton(PracticeCore::COLOR . "Battle " . TextFormat::WHITE . "Pass", 0, "zeqa/textures/ui/more/zeqa_pass.png");
		$form->addButton(PracticeCore::COLOR . "Trade " . TextFormat::WHITE . "Menu", 0, "zeqa/textures/ui/more/trade.png");
		$player->sendForm($form);
	}
}