<?php

declare(strict_types=1);

namespace zodiax\data\timings;

use pocketmine\timings\TimingsHandler;

class PracticeTimings{

	public static TimingsHandler $coreTick;
	public static TimingsHandler $duelsTick;
	public static TimingsHandler $replaysTick;
	public static TimingsHandler $botsTick;
	public static TimingsHandler $partiesTick;
	public static TimingsHandler $eventsTick;
	public static TimingsHandler $playersTick;

	public static function initialize() : void{
		self::$coreTick = new TimingsHandler("Core Tick");
		self::$duelsTick = new TimingsHandler("Duels Tick", self::$coreTick);
		self::$replaysTick = new TimingsHandler("Replays Tick", self::$coreTick);
		self::$botsTick = new TimingsHandler("Bots Tick", self::$coreTick);
		self::$partiesTick = new TimingsHandler("Parties Tick", self::$coreTick);
		self::$eventsTick = new TimingsHandler("Events Tick", self::$coreTick);
		self::$playersTick = new TimingsHandler("Players Tick", self::$coreTick);
	}
}