<?php

declare(strict_types=1);

namespace zodiax\data\database;

use libasynCurl\Curl;
use pocketmine\utils\Config;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlThread;
use Webmozart\PathUtil\Path;
use zodiax\PracticeCore;
use zodiax\ranks\RankHandler;

class DatabaseManager{

	private static DataConnector $mainDB;
	private static DataConnector $extraDB;

	public static function initialize() : void{
		$databaseInfo = (new Config(Path::join(PracticeCore::getDataFolderPath(), "settings.yml")))->get("database");

		self::$mainDB = libasynql::create(PracticeCore::getInstance(), ["type" => "mysql", "mysql" => ["host" => $databaseInfo["main"]["host"], "username" => $databaseInfo["main"]["username"], "password" => $databaseInfo["main"]["password"], "schema" => $databaseInfo["main"]["schema"], "port" => $databaseInfo["main"]["port"], "worker-limit" => $databaseInfo["worker-limit"]]], ["mysql" => "mysql.sql"]);
		self::$extraDB = libasynql::create(PracticeCore::getInstance(), ["type" => "mysql", "mysql" => ["host" => $databaseInfo["extra"]["host"], "username" => $databaseInfo["extra"]["username"], "password" => $databaseInfo["extra"]["password"], "schema" => $databaseInfo["extra"]["schema"], "port" => $databaseInfo["extra"]["port"], "worker-limit" => $databaseInfo["worker-limit"]]], ["mysql" => "mysql.sql"]);

		self::$mainDB->executeImplRaw([0 => "CREATE TABLE IF NOT EXISTS RanksData (name VARCHAR(30) NOT NULL UNIQUE, format TEXT, color TEXT, permission TEXT, isdefault BOOLEAN)"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);
		self::$mainDB->executeImplRaw([0 => "CREATE TABLE IF NOT EXISTS BansData (name VARCHAR(30) NOT NULL UNIQUE, reason TEXT, duration TEXT, staff TEXT)"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);

		self::$mainDB->executeImplRaw([0 => "CREATE TABLE IF NOT EXISTS PlayerDuration (xuid VARCHAR(16) NOT NULL UNIQUE, name TEXT, lastvoted TEXT, lastdonated TEXT, lasthosted TEXT, lastmuted TEXT, lastplayed TEXT, totalonline INT, warned INT)"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);
		self::$mainDB->executeImplRaw([0 => "CREATE TABLE IF NOT EXISTS PlayerElo (xuid VARCHAR(16) NOT NULL UNIQUE, name TEXT, battlerush INT, bedfight INT, boxing INT, bridge INT, builduhc INT, classic INT, combo INT, fist INT, gapple INT, mlgrush INT, nodebuff INT, soup INT, spleef INT, stickfight INT, sumo INT)"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);
		self::$mainDB->executeImplRaw([0 => "CREATE TABLE IF NOT EXISTS PlayerItems (xuid VARCHAR(16) NOT NULL UNIQUE, name TEXT, potcolor TEXT, projectile TEXT, tag TEXT, artifact TEXT, cape TEXT, killphrase TEXT, ownedprojectile TEXT, ownedtag TEXT, ownedartifact TEXT, ownedcape TEXT, ownedkillphrase TEXT, premium_bp BOOLEAN, free_bp_progress INT, premium_bp_progress INT)"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);
		self::$mainDB->executeImplRaw([0 => "CREATE TABLE IF NOT EXISTS PlayerRanks (xuid VARCHAR(16) NOT NULL UNIQUE, name TEXT, rank1 TEXT, rank2 TEXT, rank3 TEXT, rank4 TEXT, rank5 TEXT)"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);
		self::$mainDB->executeImplRaw([0 => "CREATE TABLE IF NOT EXISTS PlayerSettings (xuid VARCHAR(16) NOT NULL UNIQUE, name TEXT, disguise TEXT, scoreboard BOOLEAN, fairqueue BOOLEAN, pingrange BOOLEAN, cpspopup BOOLEAN, arenarespawn BOOLEAN, morecrit BOOLEAN, blood BOOLEAN, lightning BOOLEAN, autorecycle BOOLEAN, hidenonopponents BOOLEAN, devicedisplay BOOLEAN, cpsdisplay BOOLEAN, pingdisplay BOOLEAN, autosprint BOOLEAN, silentstaff BOOLEAN)"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);
		self::$mainDB->executeImplRaw([0 => "CREATE TABLE IF NOT EXISTS PlayerStats (xuid VARCHAR(16) NOT NULL UNIQUE, name TEXT, kills INT, deaths INT, coins INT, shards INT, bp INT)"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);
		self::$mainDB->executeImplRaw([0 => "CREATE TABLE IF NOT EXISTS StaffStats (xuid VARCHAR(16) NOT NULL UNIQUE, name TEXT, bans INT, kicks INT, mutes INT, tickets INT, reports INT)"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);

		self::$extraDB->executeImplRaw([0 => "CREATE TABLE IF NOT EXISTS PlayersData (name VARCHAR(30) NOT NULL UNIQUE, sensitivename TEXT, alias LONGTEXT, skin LONGTEXT)"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);
		self::$extraDB->executeImplRaw([0 => "CREATE TABLE IF NOT EXISTS KitsData (xuid VARCHAR(16) NOT NULL UNIQUE, name TEXT, attacker BLOB, battlerush BLOB, bedfight BLOB, boxing BLOB, bridge BLOB, build BLOB, builduhc BLOB, classic BLOB, clutch BLOB, combo BLOB, defender BLOB, fist BLOB, gapple BLOB, knock BLOB, mlgrush BLOB, nodebuff BLOB, nodebuffevent BLOB, oitc BLOB, reduce BLOB, resistance BLOB, soup BLOB, soupevent BLOB, spleef BLOB, stickfight BLOB, sumo BLOB, sumoffa BLOB)"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);
		self::$extraDB->executeImplRaw([0 => "CREATE TABLE IF NOT EXISTS ReplaysData (id int NOT NULL AUTO_INCREMENT, data LONGBLOB, PRIMARY KEY (id))"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);

		Curl::register(PracticeCore::getInstance(), 0, $databaseInfo["worker-limit"], 1, 36000);

		RankHandler::initialize();
	}

	public static function getMainDatabase() : DataConnector{
		return self::$mainDB;
	}

	public static function getExtraDatabase() : DataConnector{
		return self::$extraDB;
	}
}