<?php

declare(strict_types=1);

namespace zodiax\player\info;

use Closure;
use pocketmine\player\Player;
use poggit\libasynql\SqlThread;
use stdClass;
use zodiax\data\database\DatabaseManager;
use zodiax\kits\KitsManager;
use zodiax\player\info\client\ClientInfo;
use zodiax\player\misc\PlayerTrait;
use zodiax\utils\Math;
use function abs;
use function count;
use function pow;
use function strtolower;
use function substr;

class EloInfo{
	use PlayerTrait;

	private array $elo = [];
	private float $averageElo = 1000;

	public function __construct(Player $player){
		$this->player = $player->getName();
	}

	public function init(array $data) : void{
		foreach(KitsManager::getDuelKits() as $kit){
			$this->elo[$kit->getLocalName()] = $data[$kit->getLocalName()] ?? 1000;
		}
		foreach(["battlerush", "bedfight", "boxing", "bridge", "builduhc", "classic", "combo", "fist", "gapple", "mlgrush", "nodebuff", "soup", "spleef", "stickfight", "sumo"] as $key){
			$this->elo[$key] = (int) ($data[$key] ?? 1000);
		}
		$averageElo = 0;
		foreach($this->elo as $elo){
			$averageElo += $elo;
		}
		$kits = count($this->elo);
		if($kits > 0){
			$this->averageElo = $averageElo / $kits;
		}else{
			$this->averageElo = 1000;
		}
	}

	public static function calculateElo(int $winnerElo, int $loserElo, ClientInfo $winnerInfo, ClientInfo $loserInfo) : stdClass{
		$kFactor = 32;
		$winnerExpectedScore = 1.0 / (1.0 + pow(10, (float) (($loserElo - $winnerElo) / 400)));
		$loserExpectedScore = abs(1.0 / (1.0 + pow(10, (float) (($winnerElo - $loserElo) / 400))));
		$newWinnerElo = $winnerElo + (int) ($kFactor * (1 - $winnerExpectedScore));
		$newLoserElo = $loserElo + (int) ($kFactor * (0 - $loserExpectedScore));
		$winnerEloChange = $newWinnerElo - $winnerElo;
		$loserEloChange = abs($loserElo - $newLoserElo);
		$winnerDevice = $winnerInfo->getDeviceOS();
		$loserDevice = $loserInfo->getDeviceOS();
		if($winnerDevice === client\IDeviceIds::WINDOWS_10 && $loserDevice !== client\IDeviceIds::WINDOWS_10){
			$loserEloChange = (int) ($loserEloChange * 0.9);
		}elseif($winnerDevice !== client\IDeviceIds::WINDOWS_10 && $loserDevice === client\IDeviceIds::WINDOWS_10){
			$winnerEloChange = (int) ($winnerEloChange * 1.1);
		}
		$newLElo = Math::floor($loserElo - $loserEloChange, 700);
		$result = new stdClass();
		$result->loserEloChange = $loserElo - $newLElo;
		$result->winnerEloChange = $winnerEloChange;
		return $result;
	}

	public function getElo() : array{
		return $this->elo;
	}

	public function setElo(string $kit, int $elo) : void{
		$this->elo[$kit] = $elo;
		$this->updateAverageElo();
	}

	private function updateAverageElo() : void{
		$averageElo = 0;
		foreach($this->elo as $elo){
			$averageElo += $elo;
		}
		$kits = count($this->elo);
		if($kits > 0){
			$this->averageElo = $averageElo / $kits;
		}else{
			$this->averageElo = 1000;
		}
	}

	public function getEloFromKit(string $kit = "global") : ?int{
		if($kit === "global"){
			return (int) $this->averageElo;
		}
		if(isset($this->elo[$lowerCase = strtolower($kit)])){
			return $this->elo[$lowerCase];
		}
		if(KitsManager::getKit($kit) === null){
			return null;
		}
		$this->elo[$lowerCase] = 1000;
		return 1000;
	}

	public function save(string $xuid, string $name, Closure $closure) : void{
		$values = "'$xuid', '$name', ";
		$update = "name = '$name', ";
		foreach(["battlerush", "bedfight", "boxing", "bridge", "builduhc", "classic", "combo", "fist", "gapple", "mlgrush", "nodebuff", "soup", "spleef", "stickfight", "sumo"] as $key){
			$value = $this->elo[$key] ?? 1000;
			$values .= "'$value', ";
			$update .= "$key = '$value', ";
		}
		$values = substr($values, 0, -2);
		$update = substr($update, 0, -2);
		DatabaseManager::getMainDatabase()->executeImplRaw([0 => "INSERT INTO PlayerElo (xuid, name, battlerush, bedfight, boxing, bridge, builduhc, classic, combo, fist, gapple, mlgrush, nodebuff, soup, spleef, stickfight, sumo) VALUES ($values) ON DUPLICATE KEY UPDATE $update"], [0 => []], [0 => SqlThread::MODE_GENERIC], $closure, $closure);
	}
}