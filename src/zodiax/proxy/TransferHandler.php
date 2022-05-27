<?php

declare(strict_types=1);

namespace zodiax\proxy;

use zodiax\player\PlayerManager;

class TransferHandler{

	private static array $transfers;

	public static function isTransferring(string $player) : bool{
		return isset(self::$transfers[$player]);
	}

	public static function add(string $player, string $server) : void{
		self::$transfers[$player] = ["count" => 0, "server" => $server];
	}

	public static function update(string $player) : void{
		if(++self::$transfers[$player]["count"] === 7){
			PlayerManager::getPlayerExact($player)?->transfer(self::$transfers[$player]["server"]);
			unset(self::$transfers[$player]);
		}
	}
}
