<?php

declare(strict_types=1);

namespace zodiax\player\misc\cosmetic\gacha;

use pocketmine\utils\TextFormat;
use zodiax\player\info\StatsInfo;
use zodiax\player\misc\cosmetic\CosmeticManager;
use zodiax\player\misc\cosmetic\misc\CosmeticItem;
use function rand;

class GachaHandler{

	private static array $gacha;

	public static function initialize() : void{
		self::$gacha = [];

		self::addGacha(
			"1",
			TextFormat::AQUA . "ANGEL " . TextFormat::DARK_AQUA . "RISING" . TextFormat::RESET,
			[StatsInfo::COIN => 1500, StatsInfo::SHARD => 150],
			[
				[CosmeticManager::ARTIFACT, "21"],
				[CosmeticManager::KILLPHRASE, "5"],

				[CosmeticManager::ARTIFACT, "25"],
				[CosmeticManager::ARTIFACT, "18"],
				[CosmeticManager::KILLPHRASE, "8"],
				[CosmeticManager::KILLPHRASE, "12"],

				[CosmeticManager::ARTIFACT, "24"],
				[CosmeticManager::ARTIFACT, "15"],
				[CosmeticManager::ARTIFACT, "30"],
				[CosmeticManager::KILLPHRASE, "2"],
				[CosmeticManager::KILLPHRASE, "4"],
				[CosmeticManager::KILLPHRASE, "7"],

				[CosmeticManager::ARTIFACT, "1"],
				[CosmeticManager::ARTIFACT, "2"],
				[CosmeticManager::ARTIFACT, "3"],
				[CosmeticManager::ARTIFACT, "4"],
				[CosmeticManager::KILLPHRASE, "3"],
				[CosmeticManager::KILLPHRASE, "6"],
				[CosmeticManager::KILLPHRASE, "10"],
				[CosmeticManager::KILLPHRASE, "9"]
			],
			"textures/ui/icon_balloon.png"
		);

		self::addGacha(
			"2",
			TextFormat::DARK_PURPLE . "ENCHANTED " . TextFormat::YELLOW . "MONEY" . TextFormat::RESET,
			[StatsInfo::COIN => 1500, StatsInfo::SHARD => 150],
			[
				[CosmeticManager::CAPE, "28"],
				[CosmeticManager::PROJECTILE, "11"],

				[CosmeticManager::CAPE, "23"],
				[CosmeticManager::CAPE, "26"],
				[CosmeticManager::PROJECTILE, "7"],
				[CosmeticManager::PROJECTILE, "10"],

				[CosmeticManager::CAPE, "15"],
				[CosmeticManager::CAPE, "16"],
				[CosmeticManager::CAPE, "21"],
				[CosmeticManager::PROJECTILE, "8"],
				[CosmeticManager::PROJECTILE, "9"],
				[CosmeticManager::PROJECTILE, "1"],

				[CosmeticManager::CAPE, "1"],
				[CosmeticManager::CAPE, "2"],
				[CosmeticManager::CAPE, "3"],
				[CosmeticManager::CAPE, "4"],
				[CosmeticManager::CAPE, "5"],
				[CosmeticManager::PROJECTILE, "2"],
				[CosmeticManager::PROJECTILE, "3"],
				[CosmeticManager::PROJECTILE, "4"]
			],
			"textures/ui/MCoin.png"
		);

		self::addGacha(
			"3",
			TextFormat::RED . "SUN " . TextFormat::WHITE . "LAND" . TextFormat::RESET,
			[StatsInfo::COIN => 1500, StatsInfo::SHARD => 150],
			[
				[CosmeticManager::ARTIFACT, "59"],
				[CosmeticManager::ARTIFACT, "60"],
				[CosmeticManager::ARTIFACT, "61"],
				[CosmeticManager::ARTIFACT, "62"],
				[CosmeticManager::ARTIFACT, "63"],
				[CosmeticManager::ARTIFACT, "64"],
				[CosmeticManager::ARTIFACT, "65"],
				[CosmeticManager::ARTIFACT, "66"],
				[CosmeticManager::ARTIFACT, "67"],
				[CosmeticManager::ARTIFACT, "68"],
				[CosmeticManager::ARTIFACT, "69"],

				[CosmeticManager::CAPE, "52"],
				[CosmeticManager::CAPE, "53"],
				[CosmeticManager::CAPE, "54"],
				[CosmeticManager::CAPE, "55"],
				[CosmeticManager::CAPE, "56"],
				[CosmeticManager::CAPE, "60"],

				[CosmeticManager::KILLPHRASE, "20"],
				[CosmeticManager::KILLPHRASE, "21"],
				[CosmeticManager::KILLPHRASE, "22"],
			],
			"textures/ui/time_4sunset.png"
		);

		self::addGacha(
			"4",
			TextFormat::GREEN . "MEME " . TextFormat::LIGHT_PURPLE . "TIME" . TextFormat::RESET,
			[StatsInfo::COIN => 1500, StatsInfo::SHARD => 150],
			[
				[CosmeticManager::ARTIFACT, "80"],
				[CosmeticManager::ARTIFACT, "73"],
				[CosmeticManager::ARTIFACT, "75"],
				[CosmeticManager::ARTIFACT, "77"],
				[CosmeticManager::ARTIFACT, "C11"],
				[CosmeticManager::ARTIFACT, "C10"],
				[CosmeticManager::ARTIFACT, "C14"],

				[CosmeticManager::CAPE, "68"],
				[CosmeticManager::CAPE, "69"],
				[CosmeticManager::CAPE, "70"],
				[CosmeticManager::CAPE, "71"],
				[CosmeticManager::CAPE, "72"],
				[CosmeticManager::CAPE, "73"],

				[CosmeticManager::KILLPHRASE, "24"],
				[CosmeticManager::KILLPHRASE, "25"],
				[CosmeticManager::KILLPHRASE, "26"],
			],
			"textures/ui/promo_creeper.png"
		);

		self::addGacha(
			"5",
			TextFormat::DARK_GRAY . "COMPUTER " . TextFormat::DARK_GREEN . "GEEK" . TextFormat::RESET,
			[StatsInfo::COIN => 1500, StatsInfo::SHARD => 150],
			[
				[CosmeticManager::ARTIFACT, "C15"],
				[CosmeticManager::ARTIFACT, "86"],
				[CosmeticManager::CAPE, "75"],
				[CosmeticManager::CAPE, "76"],

				[CosmeticManager::ARTIFACT, "87"],
				[CosmeticManager::ARTIFACT, "83"],
				[CosmeticManager::CAPE, "78"],
				[CosmeticManager::CAPE, "80"],

				[CosmeticManager::ARTIFACT, "85"],
				[CosmeticManager::CAPE, "79"],
				[CosmeticManager::KILLPHRASE, "27"],
				[CosmeticManager::KILLPHRASE, "29"],

				[CosmeticManager::ARTIFACT, "84"],
				[CosmeticManager::CAPE, "77"],
				[CosmeticManager::KILLPHRASE, "28"],
				[CosmeticManager::KILLPHRASE, "30"],
			],
			"textures/blocks/command_block.png"
		);

		// self::addGacha("xm2021", TextFormat::GREEN . "Christmas " . TextFormat::RED . "2021" . TextFormat::RESET, [StatsInfo::COIN => 2800, StatsInfo::SHARD => 600],
		// 	[[CosmeticManager::CAPE, "48"],
		// 		[CosmeticManager::CAPE, "49"],
		// 		[CosmeticManager::CAPE, "50"],
		// 		[CosmeticManager::CAPE, "51"]],
		// "textures/ui/promo_gift_big.png");

		// self::addGacha(
		// 	"val2022",
		// 	TextFormat::LIGHT_PURPLE . "VALENTINE " . TextFormat::RED . "2022" . TextFormat::RESET,
		// 	[StatsInfo::COIN => 1800, StatsInfo::SHARD => 300],
		// 	[
		// 		[CosmeticManager::CAPE, "62"],
		// 		[CosmeticManager::CAPE, "63"],
		// 		[CosmeticManager::CAPE, "65"],
		// 		[CosmeticManager::CAPE, "66"],
		// 		[CosmeticManager::CAPE, "67"]
		// 	],
		// 	"textures/ui/heart.png"
		// );

		// self::addGacha(
		//	"east2022",
		//	TextFormat::LIGHT_PURPLE . "EASTER " . TextFormat::AQUA . "20" . TextFormat::YELLOW . "22" . TextFormat::RESET,
		//	[StatsInfo::COIN => 2500, StatsInfo::SHARD => 250],
		//	[
		//		[CosmeticManager::CAPE, "81"],
		//		[CosmeticManager::CAPE, "82"],
		//		[CosmeticManager::CAPE, "83"],
		//		[CosmeticManager::CAPE, "84"],
		//	],
		//	"zeqa/textures/ui/more/egg.png"
		// );

		self::addGacha(
			"ts",
			TextFormat::RED . "TWINGING " . TextFormat::YELLOW . "STAR" . TextFormat::RESET,
			[StatsInfo::SHARD => 2400],
			[
				[CosmeticManager::ARTIFACT, "31"],
				[CosmeticManager::ARTIFACT, "34"],
				[CosmeticManager::ARTIFACT, "38"],
				[CosmeticManager::ARTIFACT, "42"],
				[CosmeticManager::ARTIFACT, "49"],
				[CosmeticManager::ARTIFACT, "40"]
			],
			"textures/ui/filledStar.png"
		);

		self::addGacha(
			"mc",
			TextFormat::GREEN . "MEME " . TextFormat::AQUA . "COSTUME" . TextFormat::RESET,
			[StatsInfo::SHARD => 2400],
			[
				[CosmeticManager::ARTIFACT, "70"],
				[CosmeticManager::ARTIFACT, "C5"],
				[CosmeticManager::ARTIFACT, "C7"],
				[CosmeticManager::ARTIFACT, "C4"],
				[CosmeticManager::ARTIFACT, "C6"],
				[CosmeticManager::ARTIFACT, "C12"]
			],
			"textures/ui/warning_alex.png"
		);

		self::addGacha(
			"gur",
			TextFormat::WHITE . "GUARANTEE " . TextFormat::GOLD . "LEGENDARY" . TextFormat::RESET,
			[StatsInfo::SHARD => 2400],
			[
				[CosmeticManager::ARTIFACT, "21"],
				[CosmeticManager::KILLPHRASE, "5"],
				[CosmeticManager::CAPE, "28"],
				[CosmeticManager::PROJECTILE, "11"],
				[CosmeticManager::CAPE, "56"],
				[CosmeticManager::ARTIFACT, "68"],
				[CosmeticManager::ARTIFACT, "64"],
				[CosmeticManager::ARTIFACT, "65"],
				[CosmeticManager::ARTIFACT, "59"],
				[CosmeticManager::CAPE, "72"],
				[CosmeticManager::CAPE, "73"],
				[CosmeticManager::ARTIFACT, "75"],
				[CosmeticManager::ARTIFACT, "C10"],
			],
			"textures/ui/promo_gift_small_yellow.png"
		);

		self::addGacha(
			"gsr",
			TextFormat::WHITE . "GUARANTEE " . TextFormat::LIGHT_PURPLE . "EPIC" . TextFormat::RESET,
			[StatsInfo::SHARD => 1050],
			[
				[CosmeticManager::ARTIFACT, "25"],
				[CosmeticManager::ARTIFACT, "18"],
				[CosmeticManager::KILLPHRASE, "8"],
				[CosmeticManager::KILLPHRASE, "12"],
				[CosmeticManager::CAPE, "23"],
				[CosmeticManager::CAPE, "26"],
				[CosmeticManager::PROJECTILE, "7"],
				[CosmeticManager::PROJECTILE, "10"],
				[CosmeticManager::KILLPHRASE, "21"],
				[CosmeticManager::CAPE, "52"],
				[CosmeticManager::ARTIFACT, "60"],
				[CosmeticManager::ARTIFACT, "62"],
				[CosmeticManager::ARTIFACT, "66"],
				[CosmeticManager::ARTIFACT, "77"],
				[CosmeticManager::ARTIFACT, "C11"],
				[CosmeticManager::CAPE, "70"],
				[CosmeticManager::CAPE, "71"],
			],
			"textures/ui/promo_gift_small_blue.png"
		);
	}

	public static function addGacha(string $id, string $name, array $currency, array $items, string $texture = null) : void{
		self::$gacha[$id] = new Gacha($id, $name, $currency, $texture);
		foreach($items as $item){
			switch($item[0]){
				case CosmeticManager::ARTIFACT:
					self::$gacha[$id]->addItem(CosmeticManager::getArtifactFromId($item[1]));
					break;
				case CosmeticManager::CAPE:
					self::$gacha[$id]->addItem(CosmeticManager::getCapeFromId($item[1]));
					break;
				case CosmeticManager::PROJECTILE:
					self::$gacha[$id]->addItem(CosmeticManager::getProjectileFromId($item[1]));
					break;
				case CosmeticManager::KILLPHRASE:
					self::$gacha[$id]->addItem(CosmeticManager::getKillPhraseFromId($item[1]));
					break;
			}
		}
		self::$gacha[$id]->calculateDroprate();
	}

	public static function randomItemFromGacha(string $gid) : ?CosmeticItem{
		if(!isset(self::$gacha[$gid])){
			return null;
		}
		$gacha = self::$gacha[$gid];
		$items = $gacha->getItems();
		$droprate = $gacha->getDroprate();
		$result = rand(1, $gacha->getDroprateRange());
		$prevpos = 0;
		foreach($items as $item){
			if($result > $prevpos && $result <= $prevpos + $droprate[$item->getUid()]){
				return $item;
			}
			$prevpos = $prevpos + $droprate[$item->getUid()];
		}
		return null;
	}

	public static function getGacha() : array{
		return self::$gacha;
	}

	public static function getGachaById(string $id) : ?Gacha{
		return self::$gacha[$id] ?? null;
	}
}
