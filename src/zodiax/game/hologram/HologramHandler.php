<?php

declare(strict_types=1);

namespace zodiax\game\hologram;

use JsonException;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\world\Position;
use poggit\libasynql\SqlThread;
use Webmozart\PathUtil\Path;
use zodiax\data\database\DatabaseManager;
use zodiax\game\hologram\tasks\LoadHologramContentTask;
use zodiax\game\hologram\tasks\UpdateHologramTask;
use zodiax\game\hologram\types\EloHologram;
use zodiax\game\hologram\types\RankHologram;
use zodiax\game\hologram\types\RuleHologram;
use zodiax\game\hologram\types\StatsHologram;
use zodiax\kits\DefaultKit;
use zodiax\PracticeCore;
use function file_exists;
use function round;

class HologramHandler{

	/** @var array<int|string, string[]> */
	private static array $eloHologramContent = [];
	private static ?EloHologram $eloHologram = null;
	/** @var array<int|string, string[]> */
	private static array $statsHologramContent = [];
	private static ?StatsHologram $statsHologram = null;
	private static ?RankHologram $rankHologram = null;
	private static ?RuleHologram $ruleHologram = null;
	private static string $path;
	private static Config $hologramConfig;

	/**
	 * @throws JsonException
	 */
	public static function initialize() : void{
		self::$hologramConfig = new Config(self::$path = Path::join(PracticeCore::getInstance()->getDataFolder(), "hologram.yml"), Config::YAML, []);
		if(!file_exists(self::$path)){
			self::$hologramConfig->save();
		}else{
			/** @var string[] $holograms */
			$holograms = self::$hologramConfig->getAll(true);
			foreach($holograms as $name){
				/** @var string[] $data */
				$data = self::$hologramConfig->get($name);
				if(isset($data["x"], $data["y"], $data["z"], $data["world"]) && ($world = Server::getInstance()->getWorldManager()->getWorldByName($data["world"])) !== null){
					switch($name){
						case "stats":
							self::$statsHologram = new StatsHologram(new Vector3((float) $data["x"], (float) $data["y"], (float) $data["z"]), $world, true);
							break;
						case "elo":
							self::$eloHologram = new EloHologram(new Vector3((float) $data["x"], (float) $data["y"], (float) $data["z"]), $world, true);
							break;
						case "rank":
							self::$rankHologram = new RankHologram(new Vector3((float) $data["x"], (float) $data["y"], (float) $data["z"]), $world, true);
							break;
						case "rule":
							self::$ruleHologram = new RuleHologram(new Vector3((float) $data["x"], (float) $data["y"], (float) $data["z"]), $world, true);
							break;
					}
				}
			}
		}

		new UpdateHologramTask();
		new LoadHologramContentTask();
	}

	public static function loadHologramContent() : void{
		if(self::$eloHologram !== null){
			self::$eloHologramContent = [];
			foreach(self::getHologramKeys() as $key){
				DatabaseManager::getMainDatabase()->executeImplRaw([0 => "SELECT name, $key FROM PlayerElo ORDER BY $key DESC LIMIT 10"], [0 => []], [0 => SqlThread::MODE_SELECT], function(array $rows) use ($key){
					foreach($rows[0]->getRows() as $row){
						$name = $row["name"];
						$elo = $row[$key] ?? 1000;
						DatabaseManager::getExtraDatabase()->executeImplRaw([0 => "SELECT sensitivename FROM PlayersData WHERE name = '$name'"], [0 => []], [0 => SqlThread::MODE_SELECT], function(array $rows) use ($name, $elo, $key){
							self::$eloHologramContent[$key][$rows[0]->getRows()[0]["sensitivename"] ?? $name] = $elo;
						}, null);
					}
				}, null);
			}
		}
		if((self::$statsHologram) !== null){
			self::$statsHologramContent = [];
			foreach(self::getHologramKeys(false) as $key){
				DatabaseManager::getMainDatabase()->executeImplRaw([0 => "SELECT name, $key FROM PlayerStats ORDER BY $key DESC LIMIT 10"], [0 => []], [0 => SqlThread::MODE_SELECT], function(array $rows) use ($key){
					foreach($rows[0]->getRows() as $row){
						$name = $row["name"];
						$stat = $row[$key] ?? 0;
						DatabaseManager::getExtraDatabase()->executeImplRaw([0 => "SELECT sensitivename FROM PlayersData WHERE name = '$name'"], [0 => []], [0 => SqlThread::MODE_SELECT], function(array $rows) use ($name, $stat, $key){
							self::$statsHologramContent[$key][$rows[0]->getRows()[0]["sensitivename"] ?? $name] = $stat;
						}, null);
					}
				}, null);
			}
		}
	}

	public static function updateHolograms() : void{
		self::$eloHologram?->updateHologram();
		self::$statsHologram?->updateHologram();
		self::$rankHologram?->updateHologram();
		self::$ruleHologram?->updateHologram();
	}

	/**
	 * @return string[]
	 */
	public static function getHologramKeys(bool $elo = true) : array{
		if($elo){
			return ["battlerush", "bedfight", "boxing", "bridge", "builduhc", "classic", "combo", "fist", "gapple", "mlgrush", "nodebuff", "soup", "spleef", "stickfight", "sumo"];
		}else{
			return ["kills", "deaths", "coins", "shards", "bp"];
		}
	}

	/**
	 * @param string|DefaultKit $kit
	 *
	 * @return string[]
	 */
	public static function getEloHologramContentOf(string|DefaultKit $kit) : array{
		return self::$eloHologramContent[$kit instanceof DefaultKit ? $kit->getLocalName() : $kit] ?? [];
	}

	/**
	 * @return string[]
	 */
	public static function getStatsHologramContentOf(string $key) : array{
		return self::$statsHologramContent[$key] ?? [];
	}

	/**
	 * @throws JsonException
	 */
	public static function setLeaderboardHologram(string $type, Position $pos) : void{
		$vec3 = $pos->asVector3();
		$world = $pos->getWorld();
		switch($type){
			case "elo":
				if(self::$eloHologram !== null){
					self::$eloHologram->moveHologram($vec3, $world);
				}else{
					self::$eloHologram = new EloHologram($vec3, $world, true);
				}
				break;
			case "stats":
				if(self::$statsHologram !== null){
					self::$statsHologram->moveHologram($vec3, $world);
				}else{
					self::$statsHologram = new StatsHologram($vec3, $world, true);
				}
				break;
			case "rank":
				if(self::$rankHologram !== null){
					self::$rankHologram->moveHologram($vec3, $world);
				}else{
					self::$rankHologram = new RankHologram($vec3, $world, true);
				}
				break;
			case "rule":
				if(self::$ruleHologram !== null){
					self::$ruleHologram->moveHologram($vec3, $world);
				}else{
					self::$ruleHologram = new RuleHologram($vec3, $world, true);
				}
				break;
		}
		self::$hologramConfig = new Config(self::$path, Config::YAML, []);
		self::$hologramConfig->set($type, ["x" => round($vec3->x, 2), "y" => round($vec3->y, 2), "z" => round($vec3->z, 2), "world" => $world->getDisplayName()]);
		self::$hologramConfig->save();
	}
}