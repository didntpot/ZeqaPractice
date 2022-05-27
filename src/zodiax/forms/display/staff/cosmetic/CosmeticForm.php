<?php

declare(strict_types=1);

namespace zodiax\forms\display\staff\cosmetic;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\player\info\StatsInfo;
use zodiax\player\misc\cosmetic\CosmeticManager;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function array_values;
use function is_string;
use function preg_match;

class CosmeticForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($type = $args[0]) === null || !is_string($type)){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["type"]) && isset($extraData["players"])){
				$name = $extraData["players"][$data[0]];
				if(($session = PlayerManager::getSession($to = PlayerManager::getPlayerExact($name, true))) !== null){
					$type = $extraData["type"];
					if($type === "coins" || $type === "shards"){
						$currency = match ($type) {
							"coins" => StatsInfo::COIN,
							"shards" => StatsInfo::SHARD
						};
						if(isset($data[1]) && preg_match("/[+-]?[0-9]+/", $data[1])){
							$amount = (int) $data[1];
							if($amount > 1000000){
								$amount = 1000000;
							}
							$session->getStatsInfo()->addCurrency($currency, $amount);
							$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully gave $amount $type to $name");
						}
					}elseif($type === "tag"){
						$session->getItemInfo()->alterOwnedTag($to, $tag = $data[1]);
						$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully gave $tag" . TextFormat::GREEN . " $type to $name");
					}elseif($type === "bp"){
						$session->getItemInfo()->setPremiumBp($data[1] === 1);
						$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully change $name battlepass");
					}elseif(isset($extraData["items"])){
						$item = $extraData["items"][$data[1]];
						$session->getItemInfo()->alterCosmeticItem($to, $item);
						$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully gave {$item->getDisplayName()} $type to $name");
					}
				}else{
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Can not find player $name");
				}
			}
		});

		$title = match ($type) {
			"coins" => "Coins",
			"shards" => "Shards",
			"cape" => "Cape",
			"artifact" => "Artifact",
			"projectile" => "Projectile",
			"killphrase" => "KillPhrase",
			"tag" => "CustomTag",
			"bp" => "BattlePass",
		};
		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "$title " . TextFormat::WHITE . "Manager"));
		$form->addDropdown("Give to:", $dropdownArr = PlayerManager::getListDisplayNames());
		if($type === "coins" || $type === "shards"){
			$form->addInput("Please provide the amount of $type:");
		}elseif($type === "tag"){
			$form->addInput("Please provide the custom tag:");
		}elseif($type === "bp"){
			$form->addDropdown("Give to:", ["Free Pass", "Zeqa Pass"]);
		}else{
			$items = match ($type) {
				"cape" => CosmeticManager::getAllCape(),
				"artifact" => CosmeticManager::getAllArtifact(),
				"projectile" => CosmeticManager::getAllProjectile(),
				"killphrase" => CosmeticManager::getAllKillPhrase()
			};
			$itemsName = [];
			foreach($items as $key => $item){
				if($item->getId() === "0"){
					unset($items[$key]);
					continue;
				}
				$itemsName[] = $item->getDisplayName();
			}
			$form->addDropdown("Choose:", $itemsName);
			$form->addExtraData("items", array_values($items));
		}
		$form->addExtraData("type", $type);
		$form->addExtraData("players", $dropdownArr);
		$player->sendForm($form);
	}
}