<?php

declare(strict_types=1);

namespace zodiax\player\misc;

use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\player\Player;
use zodiax\player\PlayerManager;

class VanishHandler{

	private static array $vanish = [];

	public static function isVanish(Player $player) : bool{
		return isset(self::$vanish[$player->getName()]);
	}

	public static function isVanishStaff(Player $player) : bool{
		return isset(self::$vanish[$name = $player->getName()]) && self::$vanish[$name];
	}

	public static function addToVanish(Player $player, bool $staff = false) : void{
		self::$vanish[$player->getName()] = $staff;
		foreach(PlayerManager::getOnlinePlayers() as $name => $p){
			if($staff){
				if(!isset(self::$vanish[$name]) || !self::$vanish[$name]){
					$p->hidePlayer($player);
					$p->getNetworkSession()->onPlayerRemoved($player);
				}
				if(isset(self::$vanish[$name])){
					$player->showPlayer($p);
				}
			}else{
				if(!isset(self::$vanish[$name])){
					$p->hidePlayer($player);
				}
				if(isset(self::$vanish[$name]) && !self::$vanish[$name]){
					$player->showPlayer($p);
				}
			}
		}

		$session = PlayerManager::getSession($player);
		$session->updateNameTag();
		$session->getExtensions()->enableFlying(true);
		$player->setHasBlockCollision(false);
		$player->setSilent(true);
		$player->onGround = false;
		$player->getNetworkSession()->syncMovement($player->getLocation(), null, null, MovePlayerPacket::MODE_TELEPORT);
	}

	public static function removeFromVanish(Player $player) : void{
		if(isset(self::$vanish[$name = $player->getName()])){
			$isStaff = self::$vanish[$name];
			unset(self::$vanish[$name]);
			if(($session = PlayerManager::getSession($player)) !== null){
				$session->updateNameTag();
				$session->getExtensions()->enableFlying(false);
				$player->setHasBlockCollision(true);
				$player->setSilent(false);
				foreach(PlayerManager::getOnlinePlayers() as $name => $p){
					$p->showPlayer($player);
					if(isset(self::$vanish[$name])){
						$player->hidePlayer($p);
					}
					if($isStaff){
						$p->getNetworkSession()->onPlayerAdded($player);
					}
				}
			}
		}
	}

	public static function hideVanishes(Player $player) : void{
		foreach(self::$vanish as $name => $data){
			if(($vanish = PlayerManager::getPlayerExact($name)) !== null){
				$player->hidePlayer($vanish);
				if($data){
					$player->getNetworkSession()->onPlayerRemoved($vanish);
				}
			}else{
				unset($vanish[$name]);
			}
		}
	}
}