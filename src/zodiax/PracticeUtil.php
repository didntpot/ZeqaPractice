<?php

declare(strict_types=1);

namespace zodiax;

use FilesystemIterator;
use pocketmine\data\bedrock\EffectIdMap;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Webmozart\PathUtil\Path;
use zodiax\data\queue\AsyncTaskQueue;
use zodiax\game\world\AsyncCreateWorld;
use zodiax\game\world\AsyncDeleteWorld;
use zodiax\game\world\PracticeChunkLoader;
use zodiax\player\PlayerManager;
use function atan2;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function floor;
use function is_array;
use function is_string;
use function max;
use function mkdir;
use function rmdir;
use function round;
use function rtrim;
use function scandir;
use function sqrt;
use function str_pad;
use function str_replace;
use function str_split;
use function strtolower;
use function substr_count;
use function trim;
use function unlink;
use function usort;

class PracticeUtil{

	const PADDING_LINE = 0;
	const PADDING_CENTER = 1;
	const lineLength = 30;
	const charWidth = 6;
	const spaceChar = " ";
	const charWidths = [
		" " => 4,
		"!" => 2,
		"'" => 5,
		"\'" => 3,
		"(" => 5,
		")" => 5,
		"*" => 5,
		"," => 2,
		"." => 2,
		":" => 2,
		";" => 2,
		"<" => 5,
		">" => 5,
		"@" => 7,
		"I" => 4,
		"[" => 4,
		"]" => 4,
		"f" => 5,
		"i" => 2,
		"k" => 5,
		"l" => 3,
		"t" => 4,
		"" => 5,
		"|" => 2,
		"~" => 7,
		"█" => 9,
		"░" => 8,
		"▒" => 9,
		"▓" => 9,
		"▌" => 5,
		"─" => 9
	];
	const letterToUnicode = [
		" " => "  ",
		"a" => "",
		"b" => "",
		"c" => "",
		"d" => "",
		"e" => "",
		"f" => "",
		"g" => "",
		"h" => "",
		"i" => "",
		"j" => "",
		"k" => "",
		"l" => "",
		"m" => "",
		"n" => "",
		"o" => "",
		"p" => "",
		"q" => "",
		"r" => "",
		"s" => "",
		"t" => "",
		"u" => "",
		"v" => "",
		"w" => "",
		"x" => "",
		"y" => "",
		"z" => "",
		"1" => "",
		"2" => "",
		"3" => "",
		"4" => "",
		"5" => "",
		"6" => "",
		"7" => "",
		"8" => "",
		"9" => "",
		"0" => ""
	];
	const kitToUnicode = [
		"fist" => "",
		"nodebuff" => "",
		"combo" => "",
		"oitc" => "",
		"resistance" => "",
		"sumo" => "",
		"knock" => "",
		"stickfight" => "",
		"bedfight" => "",
		"soup" => "",
		"classic" => "",
		"builduhc" => "",
		"bridge" => "",
		"battlerush" => "",
		"gapple" => "",
		"boxing" => "",
		"spleef" => "",
		"mlgrush" => ""
	];

	////////////////////////////////////////////////////////////Time////////////////////////////////////////////////////

	public static function secondsToTicks(int $secs) : int{
		return $secs * 20;
	}

	public static function minutesToTicks(int $mins) : int{
		return $mins * 1200;
	}

	public static function hoursToTicks(int $hours) : int{
		return $hours * 72000;
	}

	public static function ticksToSeconds(int $ticks) : int{
		return (int) ($ticks / 20);
	}

	public static function ticksToMinutes(int $ticks) : int{
		return (int) ($ticks / 1200);
	}

	public static function ticksToHours(int $ticks) : int{
		return (int) ($ticks / 72000);
	}

	/////////////////////////////////////////////////////////Message////////////////////////////////////////////////////

	public static function formatTitle(string $title) : string{
		if(PracticeCore::isPackEnable()){
			return self::formatUnicodeTitle($title);
		}
		return TextFormat::BOLD . TextFormat::DARK_GRAY . "» " . $title . TextFormat::DARK_GRAY . " «";
	}

	private static function formatUnicodeTitle(string $title) : string{
		$result = "";
		foreach(str_split(TextFormat::clean($title)) as $c){
			$result .= self::letterToUnicode(strtolower($c)) . " ";
		}
		return trim($result);
	}

	private static function letterToUnicode(string $c) : string{
		return self::letterToUnicode[$c] ?? "";
	}

	public static function formatUnicodeKit(string $kit) : string{
		return self::kitToUnicode[strtolower($kit)] ?? $kit;
	}

	public static function centerLine(string $input) : string{
		return self::centerText($input, self::lineLength * self::charWidth);
	}

	public static function centerText(string $input, int $maxLength = 0, bool $addRightPadding = false) : string{
		$lines = explode("\n", trim($input));
		$sortedLines = $lines;
		usort($sortedLines, static function(string $a, string $b){
			return self::getPixelLength($b) <=> self::getPixelLength($a);
		});
		$longest = $sortedLines[0];
		if($maxLength === 0){
			$maxLength = self::getPixelLength($longest);
		}
		$result = "";
		$spaceWidth = self::getCharWidth(self::spaceChar);
		foreach($lines as $sortedLine){
			$len = max($maxLength - self::getPixelLength($sortedLine), 0);
			$padding = (int) round($len / (2 * $spaceWidth));
			$paddingRight = (int) floor($len / (2 * $spaceWidth));
			$result .= str_pad(self::spaceChar, $padding) . $sortedLine . ($addRightPadding ? str_pad(self::spaceChar, $paddingRight) : "") . "\n";
		}
		return rtrim($result, "\n");
	}

	public static function getPixelLength(string $line) : int{
		$length = 0;
		foreach(str_split(TextFormat::clean($line)) as $c){
			$length += self::getCharWidth($c);
		}
		$length += substr_count($line, TextFormat::BOLD);
		return $length;
	}

	private static function getCharWidth(string $c) : int{
		return self::charWidths[$c] ?? self::charWidth;
	}

	////////////////////////////////////////////////////////////Item////////////////////////////////////////////////////

	public static function itemToArr(Item $item) : array{
		$output = ["id" => $item->getId(), "meta" => $item->getMeta(), "count" => $item->getCount()];
		if($item->hasEnchantments()){
			$enchantments = $item->getEnchantments();
			$inputEnchantments = [];
			foreach($enchantments as $enchantment){
				$inputEnchantments[] = ["id" => EnchantmentIdMap::getInstance()->toId($enchantment->getType()), "level" => $enchantment->getLevel()];
			}
			$output["enchants"] = $inputEnchantments;
		}
		if($item->hasCustomName()){
			$output["customName"] = $item->getCustomName();
		}
		return $output;
	}

	public static function effectToArr(EffectInstance $instance, ?int $duration = null) : array{
		return ["id" => EffectIdMap::getInstance()->toId($instance->getType()), "amplifier" => $instance->getAmplifier(), "duration" => $duration ?? $instance->getDuration()];
	}

	public static function arrToItem(array $input) : ?Item{
		if(!isset($input["id"], $input["meta"], $input["count"])){
			return null;
		}
		$item = ItemFactory::getInstance()->get($input["id"], $input["meta"], $input["count"]);
		if(isset($input["customName"])){
			$item->setCustomName($input["customName"]);
		}
		if(isset($input["enchants"])){
			$enchantments = $input["enchants"];
			foreach($enchantments as $enchantment){
				if(!isset($enchantment["id"], $enchantment["level"])){
					continue;
				}
				$item->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId($enchantment["id"]), $enchantment["level"]));
			}
		}
		return $item;
	}

	public static function arrToEffect($input) : ?EffectInstance{
		if(!is_array($input) || !isset($input["id"], $input["amplifier"], $input["duration"])){
			return null;
		}
		return new EffectInstance(EffectIdMap::getInstance()->fromId($input["id"]), $input["duration"], $input["amplifier"]);
	}

	public static function convertArmorIndex(int|string $index) : int|string{
		if(is_string($index)){
			return match (strtolower($index)) {
				"boots" => 3,
				"leggings" => 2,
				"chestplate", "chest" => 1,
				"helmet" => 0
			};
		}
		return match ($index % 4) {
			0 => "helmet",
			1 => "chestplate",
			2 => "leggings",
			3 => "boots",
			default => 0,
		};
	}

	///////////////////////////////////////////////////////////World////////////////////////////////////////////////////

	public static function copyDirectory(string $from, string $to) : void{
		@mkdir($to, 0777, true);
		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
		foreach($files as $fileInfo){
			$target = str_replace($from, $to, $fileInfo->getPathname());
			if($fileInfo->isDir()){
				@mkdir($target, 0777, true);
			}else{
				$contents = file_get_contents($fileInfo->getPathname());
				file_put_contents($target, $contents);
			}
		}
	}

	public static function removeDirectory(string $dir) : void{
		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
		foreach($files as $fileInfo){
			if($fileInfo->isDir()){
				rmdir($fileInfo->getPathname());
			}else{
				unlink($fileInfo->getPathname());
			}
		}
		rmdir($dir);
	}

	public static function isWithinProtection(Vector3 $target, Vector3 $first, Vector3 $second) : bool{
		return self::isWithinBounds($target->x, $first->x, $second->x) && self::isWithinBounds($target->y, $first->y, $second->y) && self::isWithinBounds($target->z, $first->z, $second->z);
	}

	public static function isWithinBounds(float $target, float $first, float $second) : bool{
		[$max, $min] = self::maxMin($first, $second);
		return $target >= $min && $target <= $max;
	}

	public static function maxMin(float $first, float $second) : array{
		return $first > $second ? [$first, $second] : [$second, $first];
	}

	public static function posToArray(Vector3 $pos) : array{
		return ["x" => round($pos->x, 2), "y" => round($pos->y, 2), "z" => round($pos->z, 2)];
	}

	public static function getWorldsFromFolder() : array{
		$worlds = Path::join(Server::getInstance()->getDataPath(), "worlds");
		$ret = scandir($worlds);
		if($ret === false){
			return [];
		}
		return $ret;
	}

	public static function createWorld(int $worldId, string $arena) : void{
		AsyncTaskQueue::addTaskToQueue(new AsyncCreateWorld($worldId, $arena));
	}

	public static function deleteWorld(World|null|string $world) : void{
		if($world === null){
			return;
		}
		$server = Server::getInstance();
		if($world instanceof World){
			$server->getWorldManager()->unloadWorld($world, true);
			$world = $world->getFolderName();
		}
		AsyncTaskQueue::addTaskToQueue(new AsyncDeleteWorld(Path::join($server->getDataPath(), "worlds", $world)));
	}

	public static function teleport(Entity $entity, Vector3 $pos, ?Vector3 $lookAt = null) : void{
		[$yaw, $pitch] = self::lookAt($entity, $pos, $lookAt);
		$entity->teleport($pos, $yaw, $pitch);
		Server::getInstance()->broadcastPackets($entity->getViewers(), [MoveActorAbsolutePacket::create($entity->getId(), $entity->getOffsetPosition($location = $entity->getLocation()), $location->pitch, $location->yaw, $location->yaw, (MoveActorAbsolutePacket::FLAG_TELEPORT | ($entity->onGround ? MoveActorAbsolutePacket::FLAG_GROUND : 0)))]);
		if($entity instanceof Player && ($session = PlayerManager::getSession($entity)) !== null){
			if($session->isVanish()){
				$entity->onGround = false;
				$entity->getNetworkSession()->syncMovement($entity->getLocation(), null, null, MovePlayerPacket::MODE_TELEPORT);
			}
		}
	}

	private static function lookAt(Entity $entity, Vector3 $pos, ?Vector3 $lookAt = null) : array{
		if($lookAt === null){
			return [null, null];
		}
		$horizontal = sqrt(($lookAt->x - $pos->x) ** 2 + ($lookAt->z - $pos->z) ** 2);
		$vertical = $lookAt->y - ($pos->y + $entity->getEyeHeight());
		$pitch = -atan2($vertical, $horizontal) / M_PI * 180; //negative is up, positive is down

		$xDist = $lookAt->x - $pos->x;
		$zDist = $lookAt->z - $pos->z;

		$yaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
		if($yaw < 0){
			$yaw += 360.0;
		}
		return [$yaw, $pitch];
	}

	public static function getViewersForPosition(Player $player) : array{
		$players = [];
		$position = $player->getPosition();
		$world = $position->getWorld();
		foreach($world->getViewersForPosition($position) as $p){
			if($p->canSee($player)){
				$players[] = $p;
			}
		}
		return $players;
	}

	public static function onChunkGenerated(World $world, int $x, int $z, callable $callable) : void{
		if(!$world->isLoaded()){
			return;
		}
		if($world->isChunkPopulated($x, $z)){
			($callable)();
			return;
		}
		$chunkLoader = new PracticeChunkLoader($world, $x, $z, $callable);
		$world->registerChunkListener($chunkLoader, $x, $z);
		$world->registerChunkLoader($chunkLoader, $x, $z, true);
		$world->orderChunkPopulation($x, $z, $chunkLoader);
	}
}