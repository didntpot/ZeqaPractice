<?php

declare(strict_types=1);

namespace zodiax\player\misc;

use pocketmine\player\Player;

class SettingsHandler{

	const NO_DEVICE = 0;
	const DEVICE = 1;
	const CPS = 2;
	const PING = 3;
	const CPS_PING = 4;

	private static array $settings = [self::NO_DEVICE => [], self::DEVICE => [], self::CPS => [], self::PING => [], self::CPS_PING => []];

	public static function addOrRemoveFromCache(Player $player, int $type, bool $add) : void{
		if($add){
			self::$settings[$type][$player->getName()] = $player;
		}else{
			unset(self::$settings[$type][$player->getName()]);
		}
	}

	public static function getPlayersFromType(int $type) : array{
		return self::$settings[$type];
	}

	public static function clearCache(Player $player) : void{
		$name = $player->getName();
		foreach(self::$settings as $type => $players){
			unset(self::$settings[$type][$name]);
		}
	}
}
