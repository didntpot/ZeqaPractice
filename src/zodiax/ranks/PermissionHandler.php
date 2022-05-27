<?php

declare(strict_types=1);

namespace zodiax\ranks;

use pocketmine\player\Player;
use pocketmine\Server;
use zodiax\commands\PracticeCommand;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use function array_keys;
use function array_merge;
use function array_shift;
use function in_array;

class PermissionHandler{

	private static array $permissions;

	public static function initialize() : void{
		$permissions = RankHandler::PERMISSION_INDEXES;
		foreach($permissions as $permission){
			self::$permissions[$permission] = [];
		}
		$commands = Server::getInstance()->getCommandMap()->getCommands();
		foreach($commands as $command){
			if($command instanceof PracticeCommand){
				self::$permissions[$command->getRankPermission()][$command->getPermission()] = true;
			}
		}
		$permissions = RankHandler::PERMISSION_INDEXES;
		while(!empty($permissions)){
			$permission = array_shift($permissions);
			foreach($permissions as $data){
				self::$permissions[$permission] = array_merge(self::$permissions[$permission], self::$permissions[$data]);
				self::$permissions[$permission]["practice.permission.$permission"] = true;
			}
		}
		foreach([RankHandler::PERMISSION_OWNER, RankHandler::PERMISSION_ADMIN, RankHandler::PERMISSION_MOD, RankHandler::PERMISSION_HELPER] as $permission){
			self::$permissions[$permission]["practice.permission.spambypass"] = true;
		}
	}

	public static function updatePlayerPermissions(Player $player) : void{
		if(($session = PlayerManager::getSession($player)) !== null){
			$rankInfo = $session->getRankInfo();
			$permissions = match (true) {
				$rankInfo->hasOwnerPermissions(false) => self::$permissions[RankHandler::PERMISSION_OWNER],
				$rankInfo->hasAdminPermissions(false) => self::$permissions[RankHandler::PERMISSION_ADMIN],
				$rankInfo->hasModPermissions(false) => self::$permissions[RankHandler::PERMISSION_MOD],
				$rankInfo->hasHelperPermissions(false) => self::$permissions[RankHandler::PERMISSION_HELPER],
				$rankInfo->hasBuilderPermissions(false) => self::$permissions[RankHandler::PERMISSION_BUILDER],
				$rankInfo->hasCreatorPermissions(false) => self::$permissions[RankHandler::PERMISSION_CONTENT_CREATOR],
				$rankInfo->hasVipPlusPermissions(false) => self::$permissions[RankHandler::PERMISSION_VIPPL],
				$rankInfo->hasVipPermissions(false) => self::$permissions[RankHandler::PERMISSION_VIP],
				default => self::$permissions[RankHandler::PERMISSION_NONE]
			};
			if($rankInfo->hasRank("Host")){
				$permissions["practice.permission.spambypass"] = true;
				$permissions["practice.permission.globalmute"] = true;
				$permissions["practice.permission.kick"] = true;
				$permissions["practice.permission.whitelist"] = true;
			}
			$core = PracticeCore::getInstance();
			foreach($permissions as $perm => $data){
				$player->addAttachment($core, $perm, true);
			}
			$permissions = array_keys($permissions);
			foreach($player->getEffectivePermissions() as $permission){
				if(!in_array($permission->getPermission(), $permissions, true) && ($attachment = $permission->getAttachment()) !== null){
					$player->removeAttachment($attachment);
				}
			}
			$player->getNetworkSession()->syncAvailableCommands();
		}
	}
}
