<?php

declare(strict_types=1);

namespace zodiax\commands\staff;

use pocketmine\command\CommandSender;
use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use zodiax\commands\PracticeCommand;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\ranks\RankHandler;
use function array_shift;
use function count;
use function implode;
use function round;
use function substr;
use function trim;

class TeleportCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("teleport", "Teleport to another player", "Usage: /teleport <player>", ["tp"]);
		parent::setPermission("practice.permission.teleport");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player && ($session = PlayerManager::getSession($sender)) !== null){
			if($this->canUseCommand($sender)){
				if($session->getRankInfo()->hasOwnerPermissions()){
					switch(count($args)){
						case 1: // /tp targetPlayer
						case 3: // /tp x y z
						case 5: // /tp x y z yaw pitch - TODO: 5 args could be target x y z yaw :(
							$subject = $sender;
							$targetArgs = $args;
							break;
						case 2: // /tp player1 player2
						case 4: // /tp player1 x y z - TODO: 4 args could be x y z yaw :(
						case 6: // /tp player1 x y z yaw pitch
							if(($subject = PlayerManager::getPlayerByPrefix($args[0])) === null){
								$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Can not find player $args[0]");
								return true;
							}
							$targetArgs = $args;
							array_shift($targetArgs);
							break;
						default:
							throw new InvalidCommandSyntaxException();
					}

					switch(count($targetArgs)){
						case 1:
							if(($targetPlayer = PlayerManager::getPlayerByPrefix($targetArgs[0])) === null){
								$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Can not find player $targetArgs[0]");
								return true;
							}
							$pos = $targetPlayer->getLocation();
							PracticeUtil::onChunkGenerated($pos->getWorld(), $pos->getFloorX() >> 4, $pos->getFloorZ() >> 4, function() use ($subject, $pos){
								PracticeUtil::teleport($subject, $pos);
							});
							$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Teleported to {$targetPlayer->getDisplayName()}");
							return true;
						case 3:
						case 5:
							$base = $subject->getLocation();
							if(count($targetArgs) === 5){
								$yaw = (float) $targetArgs[3];
								$pitch = (float) $targetArgs[4];
							}else{
								$yaw = $base->yaw;
								$pitch = $base->pitch;
							}

							$x = $this->getRelativeDouble($base->x, $sender, $targetArgs[0]);
							$y = $this->getRelativeDouble($base->y, $sender, $targetArgs[1], World::Y_MIN, World::Y_MAX);
							$z = $this->getRelativeDouble($base->z, $sender, $targetArgs[2]);
							$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Teleported to " . round($x, 2) . ", " . round($y, 2) . ", " . round($z, 2));
							$pos = new Location($x, $y, $z, $base->getWorld(), $yaw, $pitch);
							PracticeUtil::onChunkGenerated($pos->getWorld(), $pos->getFloorX() >> 4, $pos->getFloorZ() >> 4, function() use ($subject, $pos){
								PracticeUtil::teleport($subject, $pos);
							});
							return true;
						default:
							throw new AssumptionFailedError("This branch should be unreachable (for now)");
					}
				}
				if(count($args) >= 1){
					if(($player = PlayerManager::getPlayerByPrefix($name = trim(implode(" ", $args)))) !== null){
						$sender->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Teleported to {$player->getDisplayName()}");
						$pos = $player->getPosition();
						PracticeUtil::onChunkGenerated($pos->getWorld(), $pos->getFloorX() >> 4, $pos->getFloorZ() >> 4, function() use ($sender, $pos){
							PracticeUtil::teleport($sender, $pos);
						});
						return true;
					}
					$sender->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Can not find player $name");
					return true;
				}
				$sender->sendMessage(PracticeCore::PREFIX . $this->getUsage());
			}
		}
		return true;
	}

	private function getRelativeDouble(float $original, CommandSender $sender, string $input, float $min = VanillaCommand::MIN_COORD, float $max = VanillaCommand::MAX_COORD) : float{
		if($input[0] === "~"){
			$value = $this->getDouble($sender, substr($input, 1));
			return $original + $value;
		}
		return $this->getDouble($sender, $input, $min, $max);
	}

	private function getDouble(CommandSender $sender, string $value, float $min = VanillaCommand::MIN_COORD, float $max = VanillaCommand::MAX_COORD) : float{
		$i = (double) $value;
		if($i < $min){
			$i = $min;
		}elseif($i > $max){
			$i = $max;
		}
		return $i;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_HELPER;
	}
}
