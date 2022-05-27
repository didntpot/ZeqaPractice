<?php

declare(strict_types=1);

namespace zodiax\commands\basic;

use pocketmine\block\VanillaBlocks;
use pocketmine\command\CommandSender;
use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\entity\Attribute as NetworkAttribute;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use zodiax\arena\ArenaManager;
use zodiax\arena\types\BlockInArena;
use zodiax\commands\PracticeCommand;
use zodiax\data\log\LogMonitor;
use zodiax\event\EventDuel;
use zodiax\game\entity\DeathEntity;
use zodiax\player\info\StatsInfo;
use zodiax\player\PlayerManager;
use zodiax\PracticeUtil;
use zodiax\ranks\RankHandler;
use function array_map;
use function rand;

class SuicideCommand extends PracticeCommand{

	public function __construct(){
		parent::__construct("suicide", "Suicide yourself", "Usage: /suicide", ["forfeit", "ff", "surrender", "leave"]);
		parent::setPermission("practice.permission.suicide");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player && ($session = PlayerManager::getSession($sender)) !== null && !$session->isInHub()){
			$killer = null;
			$senderLocation = $sender->getLocation();
			$session->setThrowPearl(true, false);
			$session->setGapple(true, false);
			$session->setShootArrow(true, false);
			if(($arena = $session->getArena()) !== null){
				if(($killer = $session->getTarget()) !== null && ($ksession = PlayerManager::getSession($killer)) !== null && ($karena = $ksession->getArena()) !== null && $arena->getName() === $karena->getName()){
					$optionalKiller = " ";
					$optionalVictim = " ";
					if($arena->getKit()?->getName() === "Nodebuff"){
						$optionalKiller = 0;
						$optionalVictim = 0;
						foreach($killer->getInventory()->getContents() as $item){
							if($item->getId() === ItemIds::SPLASH_POTION){
								$optionalKiller++;
							}
						}
						foreach($sender->getInventory()->getContents() as $item){
							if($item->getId() === ItemIds::SPLASH_POTION){
								$optionalVictim++;
							}
						}
						$optionalKiller = TextFormat::DARK_GRAY . " [" . TextFormat::WHITE . $optionalKiller . TextFormat::DARK_GRAY . "] " . TextFormat::RESET;
						$optionalVictim = TextFormat::DARK_GRAY . " [" . TextFormat::WHITE . $optionalVictim . TextFormat::DARK_GRAY . "] " . TextFormat::RESET;
					}
					$ksession->setThrowPearl(true, false);
					$ksession->setGapple(true, false);
					$ksession->setShootArrow(true, false);
					$session->setInCombat(null, false);
					$ksession->setInCombat(null, false);
					$ksession->getKitHolder()->setKit($arena->getKit());
					$msg = TextFormat::DARK_GRAY . "» " . $ksession->getItemInfo()->getKillPhraseMessage($killer, $sender, $optionalKiller, $optionalVictim);
					$killer->sendMessage($msg);
					$sender->sendMessage($msg);
					$session->respawn();
					$ksession->getStatsInfo()->addKill();
					$ksession->getStatsInfo()->addCurrency(StatsInfo::COIN, rand(StatsInfo::MIN_FFA_KILLER_COIN, StatsInfo::MAX_FFA_KILLER_COIN));
					$ksession->getStatsInfo()->addBp(rand(StatsInfo::MIN_FFA_KILLER_COIN, StatsInfo::MAX_FFA_KILLER_COIN));
					$session->getStatsInfo()->addDeath();
					$session->getStatsInfo()->addCurrency(StatsInfo::COIN, rand(StatsInfo::MIN_FFA_VICTIM_COIN, StatsInfo::MAX_FFA_VICTIM_COIN));
					$session->getStatsInfo()->addBp(rand(StatsInfo::MIN_FFA_VICTIM_COIN, StatsInfo::MAX_FFA_VICTIM_COIN));
				}else{
					$session->respawn();
				}
			}elseif(($duel = $session->getDuel()) !== null){
				$killer = $duel->getOpponent($sender);
				$duel->setEnded($killer);
				$session->getKitHolder()->clearKit();
			}elseif(($bot = $session->getBotDuel()) !== null){
				$bot->setEnded($bot->getBot());
				$session->getKitHolder()->clearKit();
			}elseif(($event = $session->getEvent()) !== null){
				$game = $event->getCurrentGame();
				if($game instanceof EventDuel && $game->isPlayer($sender)){
					$game->setEnded($killer = $game->getOpponent($sender));
				}
			}elseif(($partyDuel = $session->getPartyDuel()) !== null){
				if(($ev = $sender->getLastDamageCause()) !== null && $ev instanceof EntityDamageByEntityEvent && ($damager = $ev->getDamager()) !== null && $damager instanceof Player){
					$killer = $damager;
				}
				$partyDuel->removeFromTeam($sender);
			}elseif(($blockIn = $session->getBlockIn()) !== null){
				if($blockIn->isRunning()){
					if(($ev = $sender->getLastDamageCause()) !== null && $ev instanceof EntityDamageByEntityEvent && ($damager = $ev->getDamager()) !== null && $damager instanceof Player){
						$killer = $damager;
					}
					$team = $blockIn->getTeam($sender);
					if($team === $blockIn->getTeam1()){
						$blockIn->onAttackerDeath($sender);
					}elseif($team === $blockIn->getTeam2()){
						if(!$blockIn->deathCountdown($sender)){
							$killer = null;
						}
					}
					if(($ksession = PlayerManager::getSession($killer)) != null){
						$msg = TextFormat::DARK_GRAY . "» " . $ksession->getItemInfo()->getKillPhraseMessage($killer, $sender);
						$killer->sendMessage($msg);
						$sender->sendMessage($msg);
						$killer->setLastDamageCause(new EntityDamageEvent($killer, EntityDamageEvent::CAUSE_MAGIC, 0));
					}
				}else{
					/** @var BlockInArena $arena */
					$arena = ArenaManager::getArena($blockIn->getArena());
					$team = $blockIn->getTeam($sender);
					if($team === $blockIn->getTeam1()){
						PracticeUtil::teleport($sender, Position::fromObject($arena->getP1Spawn(), $sender->getWorld()), $arena->getCoreSpawn());
					}elseif($team === $blockIn->getTeam2()){
						PracticeUtil::teleport($sender, Position::fromObject($arena->getP2Spawn(), $sender->getWorld()), $arena->getCoreSpawn());
					}
				}
			}elseif(($training = $session->getClutch() ?? $session->getReduce()) !== null){
				$training->setEnded();
			}else{
				LogMonitor::debugLog("DEATH: {$sender->getName()} is trying to die on {$sender->getWorld()->getFolderName()} ({$sender->getWorld()->getDisplayName()})");
			}

			if(($ksession = PlayerManager::getSession($killer)) !== null){
				if($senderLocation->getY() > 0){
					$killerLocation = $killer->getLocation();
					$deathEntity = new DeathEntity($senderLocation, $sender->getSkin());
					$deathEntity->spawnToSpecifyPlayer($killer);
					$deathEntity->knockBack($senderLocation->getX() - $killerLocation->getX(), $senderLocation->getZ() - $killerLocation->getZ());
					$deathEntity->kill();
				}
				$settingInfo = $ksession->getSettingsInfo();
				$packets = [];
				if($settingInfo->isBlood()){
					$packets[] = LevelEventPacket::create(LevelEvent::PARTICLE_DESTROY, RuntimeBlockMapping::getInstance()->toRuntimeId(VanillaBlocks::REDSTONE()->getFullId(), $killer->getNetworkSession()->getProtocolId()), $senderLocation);
				}
				if($settingInfo->isLightning()){
					$packets[] = LevelSoundEventPacket::create(LevelSoundEvent::THUNDER, $senderLocation, -1, "minecraft:lightning_bolt", false, false);
					$packets[] = AddActorPacket::create($id = Entity::nextRuntimeId(), $id, "minecraft:lightning_bolt", $senderLocation, new Vector3(0, 0, 0), 0, 0, 0, array_map(function(Attribute $attr) : NetworkAttribute{
						return new NetworkAttribute($attr->getId(), $attr->getMinValue(), $attr->getMaxValue(), $attr->getValue(), $attr->getDefaultValue());
					}, $killer->getAttributeMap()->getAll()), [], []);
				}
				if(!empty($packets)){
					$killer->getServer()->broadcastPackets([$killer], $packets);
				}
			}

			$sender->setLastDamageCause(new EntityDamageEvent($sender, EntityDamageEvent::CAUSE_MAGIC, 0));
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_NONE;
	}
}
