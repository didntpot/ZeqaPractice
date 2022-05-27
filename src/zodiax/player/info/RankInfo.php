<?php

declare(strict_types=1);

namespace zodiax\player\info;

use Closure;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\XpCollectSound;
use poggit\libasynql\SqlThread;
use zodiax\data\database\DatabaseManager;
use zodiax\player\misc\PlayerTrait;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\ranks\PermissionHandler;
use zodiax\ranks\Rank;
use zodiax\ranks\RankHandler;
use function array_values;
use function count;
use function is_string;
use function strtolower;
use function substr;

class RankInfo{
	use PlayerTrait;

	private array $ranks = [];

	public function __construct(Player $player){
		$this->player = $player->getName();
		$this->ranks[1] = RankHandler::getDefaultRank()->getName();
	}

	public function init(array $data) : void{
		$ranks = [];
		for($i = 1; $i <= 5; $i++){
			$ranks[$i] = $data["rank$i"] ?? "";
		}
		if($ranks[1] === ""){
			$ranks[1] = RankHandler::getDefaultRank()->getName();
		}
		$i = 1;
		foreach($ranks as $rank){
			if(is_string($rank)){
				if(($rank = RankHandler::getRank($rank)) !== null){
					$this->ranks[$i] = $rank->getName();
					$i++;
				}
			}
		}
	}

	public function getRank() : Rank{
		return RankHandler::getRank($this->ranks[1]) ?? RankHandler::getDefaultRank();
	}

	public function getRanks(bool $asString = false) : array{
		if($asString){
			return $this->ranks;
		}
		$result = [];
		foreach($this->ranks as $rank){
			if(($rank = RankHandler::getRank($rank)) !== null){
				$result[] = $rank;
			}
		}
		return $result;
	}

	public function setRanks(array $ranks = []) : void{
		$bypass = false;
		if(count($ranks) === 1 && ($ranks[0]->getName() === "Booster" || $ranks[0]->getName() === "Voter")){
			$this->addRank($ranks[0]);
			return;
		}
		if(($session = $this->getSession()) !== null){
			$count = 1;
			foreach($ranks as $rank){
				if(RankHandler::isBypassAbleRank($name = $rank->getName())){
					$bypass = true;
				}
				$this->ranks[$count] = $name;
				$count++;
			}
			for($i = $count; $i <= 5; $i++){
				if(isset($this->ranks[$count])){
					unset($this->ranks[$count]);
				}
			}
			$session->getPlayer()->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Your rank has been updated");
			$session->getPlayer()->broadcastSound(new XpCollectSound(), [$session->getPlayer()]);
			$session->updateNameTag();
			PermissionHandler::updatePlayerPermissions($session->getPlayer());
			if($bypass){
				PlayerManager::setBypassAble($this->player, true);
			}
		}
	}

	public function addRank($rank) : void{
		$newrank = RankHandler::getRank($rank instanceof Rank ? $rank->getName() : $rank);
		if($newrank instanceof Rank && ($newrank->getName() === "Voter" || $newrank->getName() === "Booster") && $this->ranks[1] === RankHandler::getDefaultRank()->getName()){
			$this->ranks[1] = $newrank->getName();
		}
		$i = (count($this->ranks) + 1);
		if($i <= 5 && $newrank instanceof Rank){
			$name = $newrank->getName();
			foreach($this->ranks as $rank){
				if($name === $rank){
					return;
				}
			}
			$this->ranks[$i] = $name;
		}
		if(($session = $this->getSession()) !== null){
			$session->getPlayer()->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Your rank has been updated");
			$session->getPlayer()->broadcastSound(new XpCollectSound(), [$session->getPlayer()]);
			$session->updateNameTag();
			PermissionHandler::updatePlayerPermissions($session->getPlayer());
			if(RankHandler::isBypassAbleRank($newrank->getName())){
				PlayerManager::setBypassAble($this->player, true);
			}
		}
	}

	public function removeRank($rank) : void{
		$bypass = RankHandler::isBypassAbleRank($rank);
		foreach($this->ranks as $key => $r){
			if($r === $rank){
				if(count($this->ranks) === 1){
					$this->ranks[1] = RankHandler::getDefaultRank()->getName();
				}else{
					unset($this->ranks[$key]);
				}
			}
		}
		$ranks = array_values($this->ranks);
		$count = 1;
		foreach($ranks as $rank){
			$this->ranks[$count] = $rank;
			$count++;
		}
		if(($session = $this->getSession()) !== null){
			$session->getPlayer()->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Your rank has been updated");
			$session->getPlayer()->broadcastSound(new XpCollectSound(), [$session->getPlayer()]);
			$session->updateNameTag();
			PermissionHandler::updatePlayerPermissions($session->getPlayer());
			if($bypass){
				PlayerManager::setBypassAble($this->player, false);
			}
		}
	}

	public function hasRank(string $rank) : bool{
		$rank = strtolower($rank);
		foreach($this->ranks as $r){
			if(strtolower($r) === $rank){
				return true;
			}
		}
		return false;
	}

	public function hasVipPermissions(bool $defaultPermission = true) : bool{
		if($defaultPermission && $this->getPlayer()->hasPermission("practice.permission." . RankHandler::PERMISSION_VIP)){
			return true;
		}
		foreach($this->getRanks() as $rank){
			switch($rank->getPermission()){
				case RankHandler::PERMISSION_VIP:
				case RankHandler::PERMISSION_VIPPL:
				case RankHandler::PERMISSION_CONTENT_CREATOR:
					return true;
			}
		}
		return Server::getInstance()->isOp($this->player);
	}

	public function hasVipPlusPermissions(bool $defaultPermission = true) : bool{
		if($defaultPermission && $this->getPlayer()->hasPermission("practice.permission." . RankHandler::PERMISSION_VIPPL)){
			return true;
		}
		foreach($this->getRanks() as $rank){
			switch($rank->getPermission()){
				case RankHandler::PERMISSION_VIPPL:
				case RankHandler::PERMISSION_CONTENT_CREATOR:
					return true;
			}
		}
		return Server::getInstance()->isOp($this->player);
	}

	public function hasCreatorPermissions(bool $defaultPermission = true) : bool{
		if($defaultPermission && $this->getPlayer()->hasPermission("practice.permission." . RankHandler::PERMISSION_CONTENT_CREATOR)){
			return true;
		}
		foreach($this->getRanks() as $rank){
			switch($rank->getPermission()){
				case RankHandler::PERMISSION_CONTENT_CREATOR:
					return true;
			}
		}
		return Server::getInstance()->isOp($this->player);
	}

	public function hasBuilderPermissions(bool $defaultPermission = true) : bool{
		if($defaultPermission && $this->getPlayer()->hasPermission("practice.permission." . RankHandler::PERMISSION_BUILDER)){
			return true;
		}
		foreach($this->getRanks() as $rank){
			switch($rank->getPermission()){
				case RankHandler::PERMISSION_BUILDER:
				case RankHandler::PERMISSION_ADMIN:
				case RankHandler::PERMISSION_OWNER:
					return true;
			}
		}
		return Server::getInstance()->isOp($this->player);
	}

	public function hasHelperPermissions(bool $defaultPermission = true) : bool{
		if($defaultPermission && $this->getPlayer()->hasPermission("practice.permission." . RankHandler::PERMISSION_HELPER)){
			return true;
		}
		foreach($this->getRanks() as $rank){
			switch($rank->getPermission()){
				case RankHandler::PERMISSION_HELPER:
				case RankHandler::PERMISSION_MOD:
				case RankHandler::PERMISSION_ADMIN:
				case RankHandler::PERMISSION_OWNER:
					return true;
			}
		}
		return Server::getInstance()->isOp($this->player);
	}

	public function hasModPermissions(bool $defaultPermission = true) : bool{
		if($defaultPermission && $this->getPlayer()->hasPermission("practice.permission." . RankHandler::PERMISSION_MOD)){
			return true;
		}
		foreach($this->getRanks() as $rank){
			switch($rank->getPermission()){
				case RankHandler::PERMISSION_MOD:
				case RankHandler::PERMISSION_ADMIN:
				case RankHandler::PERMISSION_OWNER:
					return true;
			}
		}
		return Server::getInstance()->isOp($this->player);
	}

	public function hasAdminPermissions(bool $defaultPermission = true) : bool{
		if($defaultPermission && $this->getPlayer()->hasPermission("practice.permission." . RankHandler::PERMISSION_ADMIN)){
			return true;
		}
		foreach($this->getRanks() as $rank){
			switch($rank->getPermission()){
				case RankHandler::PERMISSION_ADMIN:
				case RankHandler::PERMISSION_OWNER:
					return true;
			}
		}
		return Server::getInstance()->isOp($this->player);
	}

	public function hasOwnerPermissions(bool $defaultPermission = true) : bool{
		if($defaultPermission && $this->getPlayer()->hasPermission("practice.permission." . RankHandler::PERMISSION_OWNER)){
			return true;
		}
		foreach($this->getRanks() as $rank){
			switch($rank->getPermission()){
				case RankHandler::PERMISSION_OWNER:
					return true;
			}
		}
		return Server::getInstance()->isOp($this->player);
	}

	public function save(string $xuid, string $name, Closure $closure) : void{
		$values = "'$xuid', '$name', ";
		$update = "name = '$name', ";
		for($i = 1; $i <= 5; $i++){
			if(isset($this->ranks[$i])){
				$values .= "'{$this->ranks[$i]}', ";
				$update .= "rank$i = '{$this->ranks[$i]}', ";
			}else{
				$values .= "'', ";
				$update .= "rank$i = '', ";
			}
		}
		$values = substr($values, 0, -2);
		$update = substr($update, 0, -2);
		DatabaseManager::getMainDatabase()->executeImplRaw([0 => "INSERT INTO PlayerRanks (xuid, name, rank1, rank2, rank3, rank4, rank5) VALUES ($values) ON DUPLICATE KEY UPDATE $update"], [0 => []], [0 => SqlThread::MODE_GENERIC], $closure, $closure);
	}
}