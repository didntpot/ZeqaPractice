<?php

declare(strict_types=1);

namespace zodiax\kits;

use Webmozart\PathUtil\Path;
use zodiax\kits\info\EffectsInfo;
use zodiax\kits\info\KnockbackInfo;
use zodiax\kits\info\MiscKitInfo;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function fclose;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function fopen;
use function json_decode;
use function json_encode;
use function mkdir;
use function scandir;
use function strtolower;
use function unlink;

class KitsManager{

	private static array $kits = [];
	private static string $defaultPath;

	public static function initialize() : void{
		@mkdir(self::$defaultPath = Path::join(PracticeCore::getDataFolderPath(), "defaultkits"));
		$kitsData = [];
		$files = scandir(self::$defaultPath);
		foreach($files as $file){
			if(Path::getExtension($file) !== "json"){
				continue;
			}
			$kitsData[Path::getFilenameWithoutExtension($file)] = json_decode(file_get_contents(Path::join(self::$defaultPath, $file)), true);
		}
		self::initKits($kitsData);
	}

	private static function initKits(array $kitsData) : void{
		foreach($kitsData as $data){
			if(isset($data["name"], $data["items"], $data["armor"], $data["effect"], $data["misc"], $data["kb"])){
				$encodedItems = $data["items"];
				$outputItems = [];
				foreach($encodedItems as $slot => $item){
					if(($exportedItem = PracticeUtil::arrToItem($item)) !== null){
						$outputItems[$slot] = $exportedItem;
					}
				}
				$items = $outputItems;
				$encodedArmors = $data["armor"];
				$outputArmors = [];
				foreach($encodedArmors as $slot => $item){
					if(($exportedArmor = PracticeUtil::arrToItem($item)) !== null){
						$outputArmors[PracticeUtil::convertArmorIndex($slot)] = $exportedArmor;
					}
				}
				$armor = $outputArmors;
				self::$kits[strtolower($name = $data["name"])] = new DefaultKit($name, $items, $armor, EffectsInfo::decode($data["effect"]), MiscKitInfo::decode($data["misc"]), KnockbackInfo::decode($data["kb"]));
			}
		}
	}

	public static function getKits(bool $asString = false) : array{
		if($asString){
			$result = [];
			foreach(self::$kits as $kit){
				$result[] = $kit->getName();
			}
			return $result;
		}
		return self::$kits;
	}

	public static function add(DefaultKit $kit) : bool{
		if(isset(self::$kits[$localName = strtolower($kit->getName())])){
			return false;
		}
		self::$kits[$localName] = $kit;
		self::saveKit($kit);
		return true;
	}

	public static function delete(DefaultKit|string $kit) : void{
		$name = $kit instanceof DefaultKit ? $kit->getName() : $kit;
		if(isset(self::$kits[$localName = strtolower($name)])){
			$kit = self::$kits[$localName];
			@unlink(Path::join(self::$defaultPath, "{$kit->getName()}.json"));
			unset(self::$kits[$localName]);
		}
	}

	public static function isKit(string $name) : bool{
		return self::getKit($name) !== null;
	}

	public static function getKit(string $name) : ?DefaultKit{
		if(isset(self::$kits[$name])){
			return self::$kits[$name];
		}
		$localName = strtolower($name);
		foreach(self::$kits as $kit){
			if(strtolower($kit->getName()) === $localName){
				return $kit;
			}
		}
		return null;
	}

	public static function getFFAKits(bool $asString = false) : array{
		$result = [];
		if($asString){
			foreach(self::$kits as $kit){
				if($kit->getMiscKitInfo()->isFFAEnabled()){
					$result[] = $kit->getName();
				}
			}
			return $result;
		}
		foreach(self::$kits as $kit){
			if($kit->getMiscKitInfo()->isFFAEnabled()){
				$result[] = $kit;
			}
		}
		return $result;
	}

	public static function getDuelKits(bool $asString = false) : array{
		$result = [];
		if($asString){
			foreach(self::$kits as $kit){
				if($kit->getMiscKitInfo()->isDuelsEnabled()){
					$result[] = $kit->getName();
				}
			}
			return $result;
		}
		foreach(self::$kits as $kit){
			if($kit->getMiscKitInfo()->isDuelsEnabled()){
				$result[] = $kit;
			}
		}
		return $result;
	}

	public static function getBotKits(bool $asString = false) : array{
		$result = [];
		if($asString){
			foreach(self::$kits as $kit){
				if($kit->getMiscKitInfo()->isBotEnabled()){
					$result[] = $kit->getName();
				}
			}
			return $result;
		}
		foreach(self::$kits as $kit){
			if($kit->getMiscKitInfo()->isBotEnabled()){
				$result[] = $kit;
			}
		}
		return $result;
	}

	public static function getEventKits(bool $asString = false) : array{
		$result = [];
		if($asString){
			foreach(self::$kits as $kit){
				if($kit->getMiscKitInfo()->isEventEnabled()){
					$result[] = $kit->getName();
				}
			}
			return $result;
		}
		foreach(self::$kits as $kit){
			if($kit->getMiscKitInfo()->isEventEnabled()){
				$result[] = $kit;
			}
		}
		return $result;
	}

	public static function getTrainingKits(bool $asString = false) : array{
		$result = [];
		if($asString){
			foreach(self::$kits as $kit){
				if($kit->getMiscKitInfo()->isTrainingEnabled()){
					$result[] = $kit->getName();
				}
			}
			return $result;
		}
		foreach(self::$kits as $kit){
			if($kit->getMiscKitInfo()->isTrainingEnabled()){
				$result[] = $kit;
			}
		}
		return $result;
	}

	public static function saveKit(DefaultKit $kit) : void{
		if(!file_exists($filePath = Path::join(self::$defaultPath, "{$kit->getName()}.json"))){
			fclose(fopen($filePath, "w"));
		}
		file_put_contents($filePath, json_encode($kit->export()));
	}
}
