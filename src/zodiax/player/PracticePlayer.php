<?php

declare(strict_types=1);

namespace zodiax\player;

use DateTime;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\EnderPearl as EPearl;
use pocketmine\item\ItemIds;
use pocketmine\item\PotionType;
use pocketmine\item\Snowball as PMSnowball;
use pocketmine\item\SplashPotion as SplashPot;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\entity\Attribute as NetworkAttribute;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\sound\ThrowSound;
use pocketmine\world\sound\XpCollectSound;
use pocketmine\world\sound\XpLevelUpSound;
use poggit\libasynql\SqlThread;
use zodiax\arena\ArenaManager;
use zodiax\arena\types\BlockInArena;
use zodiax\arena\types\DuelArena;
use zodiax\arena\types\EventArena;
use zodiax\arena\types\FFAArena;
use zodiax\arena\types\TrainingArena;
use zodiax\data\database\DatabaseManager;
use zodiax\data\log\LogMonitor;
use zodiax\duel\BotHandler;
use zodiax\duel\DuelHandler;
use zodiax\duel\ReplayHandler;
use zodiax\duel\types\BotDuel;
use zodiax\duel\types\PlayerDuel;
use zodiax\event\EventDuel;
use zodiax\event\EventHandler;
use zodiax\event\PracticeEvent;
use zodiax\game\behavior\fishing\FishingBehavior;
use zodiax\game\behavior\fishing\IFishingBehaviorEntity;
use zodiax\game\behavior\kits\IKitHolderEntity;
use zodiax\game\entity\DeathEntity;
use zodiax\game\entity\projectile\EnderPearl;
use zodiax\game\entity\projectile\Snowball;
use zodiax\game\entity\projectile\SplashPotion;
use zodiax\game\items\ItemHandler;
use zodiax\game\npc\NPCManager;
use zodiax\kits\KitsManager;
use zodiax\party\duel\PartyDuel;
use zodiax\party\duel\PartyDuelHandler;
use zodiax\party\PartyManager;
use zodiax\party\PracticeParty;
use zodiax\player\info\ClicksInfo;
use zodiax\player\info\client\ClientInfo;
use zodiax\player\info\client\IDeviceIds;
use zodiax\player\info\disguise\DisguiseInfo;
use zodiax\player\info\duel\DuelInfo;
use zodiax\player\info\duel\ReplayInfo;
use zodiax\player\info\DurationInfo;
use zodiax\player\info\EloInfo;
use zodiax\player\info\ItemInfo;
use zodiax\player\info\PlayerExtensions;
use zodiax\player\info\PlayerKitHolder;
use zodiax\player\info\RankInfo;
use zodiax\player\info\scoreboard\ScoreboardInfo;
use zodiax\player\info\settings\SettingsInfo;
use zodiax\player\info\StaffStatsInfo;
use zodiax\player\info\StatsInfo;
use zodiax\player\misc\cosmetic\CosmeticManager;
use zodiax\player\misc\queue\QueueHandler;
use zodiax\player\misc\SettingsHandler;
use zodiax\player\misc\tasks\AgroTask;
use zodiax\player\misc\tasks\ArrowTask;
use zodiax\player\misc\tasks\ChatTask;
use zodiax\player\misc\tasks\CombatTask;
use zodiax\player\misc\tasks\EnderPearlTask;
use zodiax\player\misc\tasks\FrozenTask;
use zodiax\player\misc\tasks\GappleTask;
use zodiax\player\misc\tasks\SkinTask;
use zodiax\player\misc\VanishHandler;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\proxy\TransferHandler;
use zodiax\ranks\PermissionHandler;
use zodiax\ranks\RankHandler;
use zodiax\ranks\VoteHandler;
use zodiax\training\TrainingHandler;
use zodiax\training\types\BlockInPractice;
use zodiax\training\types\ClutchPractice;
use zodiax\training\types\ReducePractice;
use zodiax\utils\ScoreboardUtil;
use function array_intersect;
use function array_keys;
use function array_map;
use function array_rand;
use function count;
use function date_format;
use function implode;
use function rand;
use function str_replace;
use function strlen;
use function strtolower;

class PracticePlayer implements IKitHolderEntity, IFishingBehaviorEntity{

	private string $player;
	private bool $isInHub = true;
	private bool $isDefaultTag = true;
	private string $frozen = "";
	private bool $loadedData = false;
	private bool $gotDestroy = false;
	private int $pingMS = 0;
	private bool $canPearl = true;
	private bool $isAgro = false;
	private bool $canGapple = true;
	private bool $canArrow = true;
	private bool $canChat = true;
	private bool $canSkin = true;
	private ?CombatTask $combat = null;
	private string $target = "";
	private string $lastReplied = "";
	/** @var array<array<string, DuelInfo|ReplayInfo>> $duelHistory */
	private array $duelHistory = [];

	private ?PlayerExtensions $extensions;
	private ?StatsInfo $statsInfo;
	private ?EloInfo $eloInfo;
	private ?SettingsInfo $settingsInfo;
	private ?ItemInfo $itemInfo;
	private ?DurationInfo $durationInfo;
	private ?RankInfo $rankInfo;
	private ?ClicksInfo $clicksInfo;
	private ?ClientInfo $clientInfo;
	private ?DisguiseInfo $disguiseInfo;
	private ?ScoreboardInfo $scoreboardInfo;
	private ?PlayerKitHolder $kitHolderInfo;
	private ?FishingBehavior $fishingBehavior;
	private ?StaffStatsInfo $staffStatsInfo;

	public function __construct(Player $player){
		$this->player = $player->getName();
		$this->extensions = new PlayerExtensions($player);
		$this->statsInfo = new StatsInfo($player);
		$this->eloInfo = new EloInfo($player);
		$this->settingsInfo = new SettingsInfo($player);
		$this->itemInfo = new ItemInfo();
		$this->durationInfo = new DurationInfo();
		$this->rankInfo = new RankInfo($player);
		$this->clicksInfo = new ClicksInfo($player);
		$this->clientInfo = new ClientInfo();
		$this->disguiseInfo = new DisguiseInfo($player);
		$this->scoreboardInfo = new ScoreboardInfo($player);
		$this->kitHolderInfo = new PlayerKitHolder($this);
		$this->fishingBehavior = new FishingBehavior($this);
		$this->staffStatsInfo = null;
	}

	public function getPlayer() : ?Player{
		return PlayerManager::getPlayerExact($this->player);
	}

	public function updateNameTag() : void{
		if(($player = $this->getPlayer()) !== null){
			$this->isDefaultTag = true;
			$player->setNameTag(RankHandler::formatRanksForTag($player));
			/** @var ClientInfo $clientInfo */
			$clientInfo = $this->getClientInfo();
			$player->sendData(SettingsHandler::getPlayersFromType(SettingsHandler::DEVICE), [EntityMetadataProperties::SCORE_TAG => new StringMetadataProperty(PracticeCore::COLOR . $clientInfo->getDeviceOS(true, PracticeCore::isPackEnable()) . TextFormat::GRAY . " | " . TextFormat::WHITE . $clientInfo->getInputAtLogin(true))]);
			$player->sendData(SettingsHandler::getPlayersFromType(SettingsHandler::NO_DEVICE), [EntityMetadataProperties::SCORE_TAG => new StringMetadataProperty("")]);
		}
	}

	public function setNoDefaultTag() : void{
		$this->isDefaultTag = false;
	}

	public function isDefaultTag() : bool{
		return $this->isDefaultTag;
	}

	public function getExtensions() : ?PlayerExtensions{
		return $this->extensions;
	}

	public function getStatsInfo() : ?StatsInfo{
		return $this->statsInfo;
	}

	public function getEloInfo() : ?EloInfo{
		return $this->eloInfo;
	}

	public function getSettingsInfo() : ?SettingsInfo{
		return $this->settingsInfo;
	}

	public function getItemInfo() : ?ItemInfo{
		return $this->itemInfo;
	}

	public function getDurationInfo() : ?DurationInfo{
		return $this->durationInfo;
	}

	public function getRankInfo() : ?RankInfo{
		return $this->rankInfo;
	}

	public function getClicksInfo() : ?ClicksInfo{
		return $this->clicksInfo;
	}

	public function getClientInfo() : ?ClientInfo{
		return $this->clientInfo;
	}

	public function getDisguiseInfo() : ?DisguiseInfo{
		return $this->disguiseInfo;
	}

	public function getScoreboardInfo() : ?ScoreboardInfo{
		return $this->scoreboardInfo;
	}

	public function getKitHolder() : ?PlayerKitHolder{
		return $this->kitHolderInfo;
	}

	public function getKitHolderEntity() : ?Player{
		return $this->getPlayer();
	}

	public function getFishingBehavior() : ?FishingBehavior{
		return $this->fishingBehavior;
	}

	public function getFishingEntity() : ?Player{
		return $this->getPlayer();
	}

	public function getStaffStatsInfo() : ?StaffStatsInfo{
		return $this->staffStatsInfo;
	}

	public function isInHub() : bool{
		return $this->isInHub;
	}

	public function setInHub(bool $inHub = true) : void{
		$this->isInHub = $inHub;
	}

	public function isFrozen() : bool{
		return $this->frozen !== "";
	}

	public function setFrozen(string $staff = "") : void{
		$this->frozen = $staff;
		/** @var Player $player */
		$player = $this->getPlayer();
		$player->setImmobile($this->isFrozen());
		if($this->isFrozen()){
			if($this->isInHub()){
				if(($party = $this->getParty()) !== null){
					$party->removePlayer($player);
				}elseif($this->isInQueue()){
					DuelHandler::removeFromQueue($player);
					ItemHandler::spawnHubItems($player);
				}elseif($this->isInBotQueue()){
					BotHandler::removeFromQueue($player);
					ItemHandler::spawnHubItems($player);
				}elseif(QueueHandler::isInQueue($player)){
					QueueHandler::removePlayer($player);
				}elseif(($kitHolder = $this->getKitHolder()) !== null && $kitHolder->isEditingKit()){
					$kitHolder->setFinishedEditingKit(true);
				}
			}elseif(($duel = $this->getBotDuel() ?? ReplayHandler::getReplayFrom($player)) !== null){
				$duel->setEnded();
			}elseif(($event = $this->getEvent()) !== null){
				$event->removePlayer($player->getDisplayName());
			}elseif(($ffa = $this->getSpectateArena()) !== null){
				$ffa->removeSpectator($player->getName());
			}elseif(($duel = (DuelHandler::getDuelFromSpec($player) ?? BotHandler::getDuelFromSpec($player) ?? PartyDuelHandler::getDuelFromSpec($player) ?? EventHandler::getEventFromSpec($player) ?? TrainingHandler::getClutchFromSpec($player) ?? TrainingHandler::getReduceFromSpec($player) ?? TrainingHandler::getBlockInFromSpec($player))) !== null){
				$duel->removeSpectator($player->getDisplayName());
			}else{
				$killer = null;
				$playerLocation = $player->getLocation();
				$this->setThrowPearl(true, false);
				$this->setGapple(true, false);
				$this->setShootArrow(true, false);
				if(($arena = $this->getArena()) !== null){
					if(($killer = $this->getTarget()) !== null && ($ksession = PlayerManager::getSession($killer)) !== null && ($karena = $ksession->getArena()) !== null && $arena->getName() === $karena->getName()){
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
							foreach($player->getInventory()->getContents() as $item){
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
						$this->setInCombat(null, false);
						$ksession->setInCombat(null, false);
						$ksession->getKitHolder()?->setKit($arena->getKit());
						$msg = TextFormat::DARK_GRAY . "» " . $ksession->getItemInfo()?->getKillPhraseMessage($killer, $player, $optionalKiller, $optionalVictim);
						$killer->sendMessage($msg);
						$player->sendMessage($msg);
						$this->respawn();
						/** @var StatsInfo $kStatsInfo */
						$kStatsInfo = $ksession->getStatsInfo();
						$kStatsInfo->addKill();
						$kStatsInfo->addCurrency(StatsInfo::COIN, rand(StatsInfo::MIN_FFA_KILLER_COIN, StatsInfo::MAX_FFA_KILLER_COIN));
						$kStatsInfo->addBp(rand(StatsInfo::MIN_FFA_KILLER_COIN, StatsInfo::MAX_FFA_KILLER_COIN));
						/** @var StatsInfo $statsInfo */
						$statsInfo = $this->getStatsInfo();
						$statsInfo->addDeath();
						$statsInfo->addCurrency(StatsInfo::COIN, rand(StatsInfo::MIN_FFA_VICTIM_COIN, StatsInfo::MAX_FFA_VICTIM_COIN));
						$statsInfo->addBp(rand(StatsInfo::MIN_FFA_VICTIM_COIN, StatsInfo::MAX_FFA_VICTIM_COIN));
					}else{
						$this->respawn();
					}
				}elseif(($duel = $this->getDuel()) !== null){
					$killer = $duel->getOpponent($player);
					$duel->setEnded($killer);
					$this->getKitHolder()?->clearKit();
				}elseif(($bot = $this->getBotDuel()) !== null){
					$bot->setEnded($bot->getBot());
					$this->getKitHolder()?->clearKit();
				}elseif(($event = $this->getEvent()) !== null){
					$game = $event->getCurrentGame();
					if($game instanceof EventDuel && $game->isPlayer($player)){
						$game->setEnded($killer = $game->getOpponent($player));
					}
				}elseif(($partyDuel = $this->getPartyDuel()) !== null){
					if(($ev = $player->getLastDamageCause()) !== null && $ev instanceof EntityDamageByEntityEvent && ($damager = $ev->getDamager()) !== null && $damager instanceof Player){
						$killer = $damager;
					}
					$partyDuel->removeFromTeam($player);
				}else{
					LogMonitor::debugLog("DEATH: {$player->getName()} is trying to die on {$player->getWorld()->getFolderName()} ({$player->getWorld()->getDisplayName()})");
				}

				/** @var Player $killer */
				if(($ksession = PlayerManager::getSession($killer)) !== null){
					if($playerLocation->getY() > 0){
						$killerLocation = $killer->getLocation();
						$deathEntity = new DeathEntity($playerLocation, $player->getSkin());
						$deathEntity->spawnToSpecifyPlayer($killer);
						$deathEntity->knockBack($playerLocation->getX() - $killerLocation->getX(), $playerLocation->getZ() - $killerLocation->getZ());
						$deathEntity->kill();
					}
					$settingInfo = $ksession->getSettingsInfo();
					$packets = [];
					if($settingInfo?->isBlood()){
						$packets[] = LevelEventPacket::create(LevelEvent::PARTICLE_DESTROY, RuntimeBlockMapping::getInstance()->toRuntimeId(VanillaBlocks::REDSTONE()->getFullId(), $killer->getNetworkSession()->getProtocolId()), $playerLocation); // @phpstan-ignore-line
					}
					if($settingInfo?->isLightning()){
						$packets[] = LevelSoundEventPacket::create(LevelSoundEvent::THUNDER, $playerLocation, -1, "minecraft:lightning_bolt", false, false);
						$packets[] = AddActorPacket::create($id = Entity::nextRuntimeId(), $id, "minecraft:lightning_bolt", $playerLocation, new Vector3(0, 0, 0), 0, 0, 0, array_map(function(Attribute $attr) : NetworkAttribute{
							return new NetworkAttribute($attr->getId(), $attr->getMinValue(), $attr->getMaxValue(), $attr->getValue(), $attr->getDefaultValue());
						}, $killer->getAttributeMap()->getAll()), [], []);
					}
					if(!empty($packets)){
						$killer->getServer()->broadcastPackets([$killer], $packets);
					}
				}

				$player->setLastDamageCause(new EntityDamageEvent($player, EntityDamageEvent::CAUSE_MAGIC, 0));
			}

			PracticeUtil::teleport(PlayerManager::getPlayerExact($staff), $player->getPosition()); // @phpstan-ignore-line
			$msg = PracticeCore::PREFIX . TextFormat::RED . $player->getDisplayName() . PracticeCore::COLOR . " is being frozen, Do not kick or ban till " . TextFormat::RED . $staff . PracticeCore::COLOR . " dealt";
			foreach(PlayerManager::getOnlineStaffs() as $s){
				$s->sendMessage($msg);
			}
			new FrozenTask($this);
		}else{
			$player->sendTitle(TextFormat::GREEN . "You are now able to move");
		}
		$this->updateNameTag();
	}

	public function getFrozen() : string{
		return $this->frozen;
	}

	public function isInArena() : bool{
		return $this->getArena() !== null;
	}

	public function getArena() : ?FFAArena{
		foreach(ArenaManager::getFFAArenas(true) as $arena){
			if($arena->isPlayer($this->player)){
				return $arena;
			}
		}
		return null;
	}

	public function isSpectateArena() : bool{
		return $this->getSpectateArena() !== null;
	}

	public function getSpectateArena() : ?FFAArena{
		foreach(ArenaManager::getFFAArenas(true) as $arena){
			if($arena->isSpectator($this->player)){
				return $arena;
			}
		}
		return null;
	}

	public function isInCombat() : bool{
		return $this->getTarget() !== null;
	}

	public function getTarget() : ?Player{
		return PlayerManager::getPlayerExact($this->target);
	}

	public function setInCombat(Player $target = null, bool $message = true) : void{
		if(($player = $this->getPlayer()) !== null){
			if($target === null){
				if($this->target !== ""){
					if($this->getSettingsInfo()->isHideNonOpponents() && ($arena = $this->getArena()) !== null && !$arena->canInterrupt()){
						foreach($arena->getPlayers() as $p){
							if(($opponent = PlayerManager::getPlayerExact($p)) !== null){
								$player->showPlayer($opponent);
							}
						}
					}
					if($message){
						$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "You are no longer in combat");
					}
				}
				/** @var ScoreboardInfo $sbInfo */
				$sbInfo = $this->getScoreboardInfo();
				if($sbInfo->getScoreboardType() === ScoreboardInfo::SCOREBOARD_FFA){
					$sbInfo->updateLineOfScoreboard(2, TextFormat::YELLOW . " Combat: " . TextFormat::WHITE . "0s");
					$sbInfo->updateLineOfScoreboard(5, TextFormat::RED . " Their Ping: " . TextFormat::WHITE . "0ms");
				}
				$this->combat?->getHandler()?->cancel();
				$this->combat = null;
				$this->updateNameTag();
				$target = $this->target;
				$this->target = "";
				if(($tSession = PlayerManager::getSession(PlayerManager::getPlayerExact($target))) !== null && $tSession->getTarget()?->getName() === $this->player){
					$tSession->setInCombat(null, $message);
				}
			}else{
				$name = $target->getName();
				if($this->target === ""){
					if($this->getSettingsInfo()->isHideNonOpponents() && ($arena = $this->getArena()) !== null && !$arena->canInterrupt()){
						foreach($arena->getPlayers() as $p){
							if(($opponent = PlayerManager::getPlayerExact($p)) !== null && $opponent->getName() !== $name){
								$player->hidePlayer($opponent);
							}
						}
					}
					if($message){
						$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You are in combat");
					}
				}
				$opponentMS = PlayerManager::getSession($target)?->getPing();
				/** @var ScoreboardInfo $sbInfo */
				$sbInfo = $this->getScoreboardInfo();
				$sbInfo->updateLineOfScoreboard(2, TextFormat::YELLOW . " Combat: " . TextFormat::WHITE . "10s");
				$sbInfo->updateLineOfScoreboard(5, TextFormat::RED . " Their Ping: " . TextFormat::WHITE . "{$opponentMS}ms");
				if($this->combat === null){
					$this->combat = new CombatTask($this);
				}else{
					$this->combat->resetTimer();
				}
				$this->target = $name;
			}
		}
	}

	public function isInQueue() : bool{
		return DuelHandler::getQueueOf($this->getPlayer()) !== null; // @phpstan-ignore-line
	}

	public function isInDuel() : bool{
		return $this->getDuel() !== null;
	}

	public function getDuel() : ?PlayerDuel{
		return DuelHandler::getDuel($this->getPlayer());
	}

	public function isInBotQueue() : bool{
		return BotHandler::getQueueOf($this->getPlayer()) !== null; // @phpstan-ignore-line
	}

	public function isInBotDuel() : bool{
		return $this->getBotDuel() !== null;
	}

	public function getBotDuel() : ?BotDuel{
		return BotHandler::getDuel($this->getPlayer());
	}

	public function isInClutch() : bool{
		return $this->getClutch() !== null;
	}

	public function getClutch() : ?ClutchPractice{
		return TrainingHandler::getClutch($this->getPlayer());
	}

	public function isInReduce() : bool{
		return $this->getReduce() !== null;
	}

	public function getReduce() : ?ReducePractice{
		return TrainingHandler::getReduce($this->getPlayer());
	}

	public function isInBlockIn() : bool{
		return $this->getBlockIn() !== null;
	}

	public function getBlockIn() : ?BlockInPractice{
		return TrainingHandler::getBlockIn($this->getPlayer());
	}

	public function isInEvent() : bool{
		return $this->getEvent() !== null;
	}

	public function isInGame() : bool{
		return $this->getEvent()?->isInGame($this->getPlayer()) ?? false; // @phpstan-ignore-line
	}

	public function getEvent() : ?PracticeEvent{
		return EventHandler::getEventFromPlayer($this->getPlayer()); // @phpstan-ignore-line
	}

	public function isInParty() : bool{
		return $this->getParty() !== null;
	}

	public function getParty() : ?PracticeParty{
		return PartyManager::getPartyFromPlayer($this->getPlayer()); // @phpstan-ignore-line
	}

	public function isInPartyDuel() : bool{
		return $this->getPartyDuel() !== null;
	}

	public function getPartyDuel() : ?PartyDuel{
		return PartyDuelHandler::getDuel($this->getParty());
	}

	public function isInSpectate() : bool{
		return DuelHandler::getDuelFromSpec($this->getPlayer()) !== null || BotHandler::getDuelFromSpec($this->getPlayer()) !== null || PartyDuelHandler::getDuelFromSpec($this->getPlayer()) !== null || EventHandler::getEventFromSpec($this->getPlayer()) !== null || TrainingHandler::getClutchFromSpec($this->getPlayer()) !== null || TrainingHandler::getReduceFromSpec($this->getPlayer()) !== null || TrainingHandler::getBlockInFromSpec($this->getPlayer()) !== null; // @phpstan-ignore-line
	}

	public function isInReplay() : bool{
		return ReplayHandler::getReplayFrom($this->getPlayer()) !== null; // @phpstan-ignore-line
	}

	public function addToDuelHistory(DuelInfo $info) : void{
		$this->duelHistory[] = ["info" => $info];
	}

	public function getDuelHistory() : array{ // @phpstan-ignore-line
		return $this->duelHistory;
	}

	public function getLatestDuelHistory() : array{ // @phpstan-ignore-line
		return $this->duelHistory[count($this->duelHistory) - 1];
	}

	public function addReplayInfo(ReplayInfo $info) : void{
		if(isset($this->duelHistory[$index = count($this->duelHistory) - 1])){
			$this->duelHistory[$index]["replay"] = $info;
		}
	}

	public function getDuelInfo(int $id) : ?array{ // @phpstan-ignore-line
		return $this->duelHistory[$id] ?? null;
	}

	public function isVanish() : bool{
		return VanishHandler::isVanish($this->getPlayer()); //@phpstan-ignore-line
	}

	public function onDeath(bool $void = false) : void{
		$killer = null;
		/** @var Player $player */
		$player = $this->getPlayer();
		$playerLocation = $player->getLocation();
		$this->setThrowPearl(true, false);
		$this->setGapple(true, false);
		$this->setShootArrow(true, false);
		if(($arena = $this->getArena()) !== null){
			if(($killer = $this->getTarget()) !== null && ($ksession = PlayerManager::getSession($killer)) !== null && ($karena = $ksession->getArena()) !== null && $arena->getName() === $karena->getName()){
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
					foreach($player->getInventory()->getContents() as $item){
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
				$this->setInCombat(null, false);
				$ksession->setInCombat(null, false);
				$ksession->getKitHolder()?->setKit($arena->getKit());
				$msg = TextFormat::DARK_GRAY . "» " . $ksession->getItemInfo()?->getKillPhraseMessage($killer, $player, $optionalKiller, $optionalVictim);
				$killer->sendMessage($msg);
				$player->sendMessage($msg);
				$this->respawn();
				/** @var StatsInfo $kStatsInfo */
				$kStatsInfo = $ksession->getStatsInfo();
				$kStatsInfo->addKill();
				$kStatsInfo->addCurrency(StatsInfo::COIN, rand(StatsInfo::MIN_FFA_KILLER_COIN, StatsInfo::MAX_FFA_KILLER_COIN));
				$kStatsInfo->addBp(rand(StatsInfo::MIN_FFA_KILLER_COIN, StatsInfo::MAX_FFA_KILLER_COIN));
				/** @var StatsInfo $statsInfo */
				$statsInfo = $this->getStatsInfo();
				$statsInfo->addDeath();
				$statsInfo->addCurrency(StatsInfo::COIN, rand(StatsInfo::MIN_FFA_VICTIM_COIN, StatsInfo::MAX_FFA_VICTIM_COIN));
				$statsInfo->addBp(rand(StatsInfo::MIN_FFA_VICTIM_COIN, StatsInfo::MAX_FFA_VICTIM_COIN));
			}else{
				$this->respawn();
			}
		}elseif(($duel = $this->getDuel()) !== null && $duel->isRunning()){
			/** @var Player $killer */
			$killer = $duel->getOpponent($player);
			if($duel->getKit() === "Bridge" || $duel->getKit() === "BattleRush" || $duel->getKit() === "MLGRush"){
				$name = $player->getName();
				/** @var DuelArena $arena */
				$arena = ArenaManager::getArena($duel->getArena());
				if($name === $duel->getPlayer1()){
					PracticeUtil::teleport($player, Position::fromObject($arena->getP1Spawn(), $player->getWorld()), $arena->getP2Spawn());
					$this->getKitHolder()?->setKit($duel->getKit());
					$duel->adaptKitItems($player);
				}elseif($name === $duel->getPlayer2()){
					PracticeUtil::teleport($player, Position::fromObject($arena->getP2Spawn(), $player->getWorld()), $arena->getP1Spawn());
					$this->getKitHolder()?->setKit($duel->getKit());
					$duel->adaptKitItems($player);
				}
				if(($ev = $player->getLastDamageCause()) !== null && $ev instanceof EntityDamageByEntityEvent && ($damager = $ev->getDamager()) !== null && $damager instanceof Player){
					if(($ksession = PlayerManager::getSession($killer)) != null){
						$msg = TextFormat::DARK_GRAY . "» " . $ksession->getItemInfo()?->getKillPhraseMessage($killer, $player);
						$killer->sendMessage($msg);
						$player->sendMessage($msg);
						if($duel->getKit() === "MLGRush"){
							$killer->broadcastSound(new XpCollectSound(), [$killer]);
						}
						$killer->setLastDamageCause(new EntityDamageEvent($killer, EntityDamageEvent::CAUSE_MAGIC, 0));
						$duel->addKillTo($killer);
						if($duel->getKit() === "Bridge" && ($inv = $killer->getInventory()) !== null && !$inv->contains($arrow = VanillaItems::ARROW())){
							$inv->addItem($arrow);
						}
					}
				}else{
					$killer = null;
				}
			}elseif($duel->getKit() === "BedFight" || $duel->getKit() === "StickFight"){
				if($duel->deathCountdown($player)){
					if(($ev = $player->getLastDamageCause()) !== null && $ev instanceof EntityDamageByEntityEvent && ($damager = $ev->getDamager()) !== null && $damager instanceof Player){
						if(($ksession = PlayerManager::getSession($killer)) != null){
							$msg = TextFormat::DARK_GRAY . "» " . $ksession->getItemInfo()?->getKillPhraseMessage($killer, $player);
							$killer->sendMessage($msg);
							$player->sendMessage($msg);
							$killer->setLastDamageCause(new EntityDamageEvent($killer, EntityDamageEvent::CAUSE_MAGIC, 0));
							$duel->addKillTo($killer);
						}
					}else{
						$killer = null;
					}
				}
			}else{
				$duel->setEnded($killer);
				$this->getKitHolder()?->clearKit();
			}
		}elseif(($bot = $this->getBotDuel()) !== null && $bot->isRunning()){
			$bot->setEnded($bot->getBot());
			$this->getKitHolder()?->clearKit();
		}elseif(($event = $this->getEvent()) !== null){
			if($event instanceof PracticeEvent){
				$game = $event->getCurrentGame();
				if($game instanceof EventDuel && $game->isPlayer($player)){
					$game->setEnded($killer = $game->getOpponent($player));
				}
			}
		}elseif(($partyDuel = $this->getPartyDuel()) !== null && $partyDuel->isRunning()){
			if(($ev = $player->getLastDamageCause()) !== null && $ev instanceof EntityDamageByEntityEvent && ($damager = $ev->getDamager()) !== null && $damager instanceof Player){
				$killer = $damager;
			}
			if($partyDuel->getKit() === "Bridge" || $partyDuel->getKit() === "BattleRush" || $partyDuel->getKit() === "MLGRush"){
				/** @var PracticeParty $party1 */
				$party1 = PartyManager::getPartyFromName($partyDuel->getParty1());
				/** @var PracticeParty $party2 */
				$party2 = PartyManager::getPartyFromName($partyDuel->getParty2());
				/** @var DuelArena $arena */
				$arena = ArenaManager::getArena($partyDuel->getArena());
				if($party1->isPlayer($player)){
					PracticeUtil::teleport($player, Position::fromObject($arena->getP1Spawn(), $player->getWorld()), $arena->getP2Spawn());
					$this->getKitHolder()?->setKit($partyDuel->getKit());
					$partyDuel->adaptKitItems($player);
				}elseif($party2->isPlayer($player)){
					PracticeUtil::teleport($player, Position::fromObject($arena->getP2Spawn(), $player->getWorld()), $arena->getP1Spawn());
					$this->getKitHolder()?->setKit($partyDuel->getKit());
					$partyDuel->adaptKitItems($player);
				}
				/** @var Player $killer */
				if(($ksession = PlayerManager::getSession($killer)) != null){
					$msg = TextFormat::DARK_GRAY . "» " . $ksession->getItemInfo()?->getKillPhraseMessage($killer, $player);
					$killer->sendMessage($msg);
					$player->sendMessage($msg);
					if($partyDuel->getKit() === "MLGRush"){
						$killer->broadcastSound(new XpCollectSound(), [$killer]);
					}
					$killer->setLastDamageCause(new EntityDamageEvent($killer, EntityDamageEvent::CAUSE_MAGIC, 0));
					$partyDuel->addKillTo($killer);
					if($partyDuel->getKit() === "Bridge" && ($inv = $killer->getInventory()) !== null && !$inv->contains($arrow = VanillaItems::ARROW())){
						$inv->addItem($arrow);
					}
				}
			}elseif($partyDuel->getKit() === "BedFight" || $partyDuel->getKit() === "StickFight"){
				if($partyDuel->deathCountdown($player)){
					/** @var Player $killer */
					if(($ksession = PlayerManager::getSession($killer)) != null){
						$msg = TextFormat::DARK_GRAY . "» " . $ksession->getItemInfo()?->getKillPhraseMessage($killer, $player);
						$killer->sendMessage($msg);
						$player->sendMessage($msg);
						$killer->setLastDamageCause(new EntityDamageEvent($killer, EntityDamageEvent::CAUSE_MAGIC, 0));
						$partyDuel->addKillTo($killer);
					}
				}
			}else{
				$partyDuel->removeFromTeam($player);
			}
		}elseif(($blockIn = $this->getBlockIn()) !== null){
			if($blockIn->isRunning()){
				if(($ev = $player->getLastDamageCause()) !== null && $ev instanceof EntityDamageByEntityEvent && ($damager = $ev->getDamager()) !== null && $damager instanceof Player){
					$killer = $damager;
				}
				$team = $blockIn->getTeam($player);
				if($team === $blockIn->getTeam1()){
					$blockIn->onAttackerDeath($player);
				}elseif($team === $blockIn->getTeam2()){
					if(!$blockIn->deathCountdown($player)){
						$killer = null;
					}
				}
				/** @var Player $killer */
				if(($ksession = PlayerManager::getSession($killer)) != null){
					$msg = TextFormat::DARK_GRAY . "» " . $ksession->getItemInfo()?->getKillPhraseMessage($killer, $player);
					$killer->sendMessage($msg);
					$player->sendMessage($msg);
					$killer->setLastDamageCause(new EntityDamageEvent($killer, EntityDamageEvent::CAUSE_MAGIC, 0));
				}
			}else{
				/** @var BlockInArena $arena */
				$arena = ArenaManager::getArena($blockIn->getArena());
				$team = $blockIn->getTeam($player);
				if($team === $blockIn->getTeam1()){
					PracticeUtil::teleport($player, Position::fromObject($arena->getP1Spawn(), $player->getWorld()), $arena->getCoreSpawn());
				}elseif($team === $blockIn->getTeam2()){
					PracticeUtil::teleport($player, Position::fromObject($arena->getP2Spawn(), $player->getWorld()), $arena->getCoreSpawn());
				}
			}
		}elseif(($clutch = $this->getClutch()) !== null){
			if($clutch->isRunning()){
				$clutch->resetClutch();
			}else{
				/** @var TrainingArena $arena */
				$arena = ArenaManager::getArena($clutch->getArena());
				PracticeUtil::teleport($player, Position::fromObject($arena->getP1Spawn(), $player->getWorld()), $arena->getP2Spawn());
			}
		}elseif(($reduce = $this->getReduce()) !== null){
			if($reduce->isRunning()){
				$reduce->resetReduce();
			}else{
				/** @var TrainingArena $arena */
				$arena = ArenaManager::getArena($reduce->getArena());
				PracticeUtil::teleport($player, Position::fromObject($arena->getP1Spawn(), $player->getWorld()), $arena->getP2Spawn());
			}
		}elseif($void){
			if($this->isInHub()){
				/** @var Position $pos */
				$pos = Server::getInstance()->getWorldManager()->getDefaultWorld()?->getSpawnLocation();
				PracticeUtil::onChunkGenerated($pos->getWorld(), $pos->getFloorX() >> 4, $pos->getFloorZ() >> 4, function() use ($player, $pos){
					PracticeUtil::teleport($player, $pos);
				});
			}elseif(($arena = $this->getSpectateArena()) !== null){
				if(($world = $arena->getWorld()) !== null){
					$pos = Position::fromObject($arena->getSpawns()[array_rand($arena->getSpawns())], $world);
					PracticeUtil::onChunkGenerated($world, $pos->getFloorX() >> 4, $pos->getFloorZ() >> 4, function() use ($pos, $player){
						PracticeUtil::teleport($player, $pos);
					});
				}
			}elseif((($event = $this->getEvent()) !== null && !$event->isInGame($player)) || ($event = EventHandler::getEventFromSpec($player)) !== null){
				/** @var EventArena $arena */
				$arena = ArenaManager::getArena($event->getArena());
				$pos = Position::fromObject($arena->getSpecSpawn(), $arena->getWorld());
				PracticeUtil::onChunkGenerated($pos->getWorld(), $pos->getFloorX() >> 4, $pos->getFloorZ() >> 4, function() use ($player, $pos){
					PracticeUtil::teleport($player, $pos);
				});
			}elseif(($duel = (DuelHandler::getDuelFromSpec($player) ?? BotHandler::getDuelFromSpec($player) ?? PartyDuelHandler::getDuelFromSpec($player) ?? ReplayHandler::getReplayFrom($player) ?? TrainingHandler::getClutchFromSpec($player) ?? TrainingHandler::getReduceFromSpec($player) ?? TrainingHandler::getBlockInFromSpec($player))) !== null){
				$pos = $duel->getCenterPosition();
				PracticeUtil::onChunkGenerated($pos->getWorld(), $pos->getFloorX() >> 4, $pos->getFloorZ() >> 4, function() use ($player, $pos){
					PracticeUtil::teleport($player, $pos);
				});
			}else{
				LogMonitor::debugLog("DEATH: $this->player is trying to die on {$player->getWorld()->getFolderName()} ({$player->getWorld()->getDisplayName()})");
			}
		}else{
			LogMonitor::debugLog("DEATH: $this->player is trying to die on {$player->getWorld()->getFolderName()} ({$player->getWorld()->getDisplayName()})");
		}

		/** @var Player $killer */
		if(($ksession = PlayerManager::getSession($killer)) !== null){
			if($playerLocation->getY() > 0){
				$killerLocation = $killer->getLocation();
				$deathEntity = new DeathEntity($playerLocation, $player->getSkin());
				$deathEntity->spawnToSpecifyPlayer($killer);
				$deathEntity->knockBack($playerLocation->getX() - $killerLocation->getX(), $playerLocation->getZ() - $killerLocation->getZ());
				$deathEntity->kill();
			}
			$settingInfo = $ksession->getSettingsInfo();
			$packets = [];
			if($settingInfo?->isBlood()){
				$packets[] = LevelEventPacket::create(LevelEvent::PARTICLE_DESTROY, RuntimeBlockMapping::getInstance()->toRuntimeId(VanillaBlocks::REDSTONE()->getFullId(), $killer->getNetworkSession()->getProtocolId()), $playerLocation); //@phpstan-ignore-line
			}
			if($settingInfo?->isLightning()){
				$packets[] = LevelSoundEventPacket::create(LevelSoundEvent::THUNDER, $playerLocation, -1, "minecraft:lightning_bolt", false, false);
				$packets[] = AddActorPacket::create($id = Entity::nextRuntimeId(), $id, "minecraft:lightning_bolt", $playerLocation, new Vector3(0, 0, 0), 0, 0, 0, array_map(function(Attribute $attr) : NetworkAttribute{
					return new NetworkAttribute($attr->getId(), $attr->getMinValue(), $attr->getMaxValue(), $attr->getValue(), $attr->getDefaultValue());
				}, $killer->getAttributeMap()->getAll()), [], []);
			}
			if(!empty($packets)){
				$killer->getServer()->broadcastPackets([$killer], $packets);
			}
		}

		$player->setLastDamageCause(new EntityDamageEvent($player, EntityDamageEvent::CAUSE_MAGIC, 0));
	}

	public function respawn() : void{
		/** @var Player $player */
		$player = $this->getPlayer();
		$this->getKitHolder()?->clearKit();
		if($this->getSettingsInfo()?->isArenaRespawn() && ($arena = $this->getArena()) !== null){
			$arena->addPlayer($player);
			return;
		}
		$this->getArena()?->removePlayer($this->player);
		PracticeUtil::teleport($player, Server::getInstance()->getWorldManager()->getDefaultWorld()?->getSpawnLocation()); //@phpstan-ignore-line
		ItemHandler::spawnHubItems($player);
		$this->getScoreboardInfo()?->setScoreboard(ScoreboardInfo::SCOREBOARD_SPAWN);
		$this->setInHub();
	}

	public function reset() : void{
		/** @var Player $player */
		$player = $this->getPlayer();
		$this->getArena()?->removePlayer($this->player);
		$player->extinguish();
		if(!$this->isFrozen()){
			$player->setImmobile(false);
		}
		VanishHandler::removeFromVanish($player);
		$this->getKitHolder()?->clearKit();
		$this->setInCombat();
		$this->setThrowPearl(true, false);
		$this->setGapple(true, false);
		$this->setShootArrow(true, false);
		PracticeUtil::teleport($player, Server::getInstance()->getWorldManager()->getDefaultWorld()?->getSpawnLocation()); //@phpstan-ignore-line
		if($this->isInParty()){
			ItemHandler::spawnPartyItems($player);
			$this->getScoreboardInfo()?->setScoreboard(ScoreboardInfo::SCOREBOARD_PARTY);
		}else{
			ItemHandler::spawnHubItems($player);
			if(PracticeCore::isLobby()){
				$this->getScoreboardInfo()?->setScoreboard(ScoreboardInfo::SCOREBOARD_LOBBY);
			}else{
				$this->getScoreboardInfo()?->setScoreboard(ScoreboardInfo::SCOREBOARD_SPAWN);
			}
		}
		$this->setInHub();
	}

	public function throwPotion(SplashPot $item) : bool{
		/** @var Player $player */
		$player = $this->getPlayer();
		if(!$player->isImmobile()){
			$location = $player->getLocation();
			$world = $location->getWorld();
			$pot = new SplashPotion(Location::fromObject($player->getEyePos(), $world, $location->getYaw(), $location->getPitch()), $player, PotionType::STRONG_HEALING(), null, $players = PracticeUtil::getViewersForPosition($player));
			($ev = new ProjectileLaunchEvent($pot))->call();
			if($ev->isCancelled()){
				$pot->flagForDespawn();
				return false;
			}
			$world->addSound($location, new ThrowSound(), $players);
			if(!$player->isCreative()){
				$player->getInventory()->setItemInHand(VanillaItems::AIR());
			}
			$this->getDuel()?->setThrowFor($player, $item);
			return true;
		}
		return false;
	}

	public function throwSnowball(PMSnowball $item) : bool{
		/** @var Player $player */
		$player = $this->getPlayer();
		if(!$player->isImmobile()){
			$location = $player->getLocation();
			$world = $location->getWorld();
			$snowball = new Snowball(Location::fromObject($player->getEyePos(), $world, $location->getYaw(), $location->getPitch()), $player, null, $players = PracticeUtil::getViewersForPosition($player));
			($ev = new ProjectileLaunchEvent($snowball))->call();
			if($ev->isCancelled()){
				$snowball->flagForDespawn();
				return false;
			}
			$world->addSound($location, new ThrowSound(), $players);
			if(!$player->isCreative()){
				$inv = $player->getInventory();
				$count = $item->getCount() - 1;
				if($count === 0){
					$inv->setItemInHand(VanillaItems::AIR());
				}else{
					$inv->setItemInHand($item->setCount($count));
				}
			}
			if(PracticeCore::REPLAY){ //@phpstan-ignore-line
				$this->getDuel()?->setThrowFor($player, $item);
			}
			return true;
		}
		return false;
	}

	public function throwPearl(EPearl $item) : bool{
		/** @var Player $player */
		$player = $this->getPlayer();
		if(!$player->isImmobile()){
			if(($kit = $this->getKitHolder()?->getKit()) !== null && $kit->getName() === "Build" || $this->canPearl()){
				if($kit !== null && $kit->getName() !== "Build"){
					$this->setThrowPearl(false);
				}
				$location = $player->getLocation();
				$world = $location->getWorld();
				$pearl = new EnderPearl(Location::fromObject($player->getEyePos(), $world, $location->getYaw(), $location->getPitch()), $player, null, $players = PracticeUtil::getViewersForPosition($player));
				($ev = new ProjectileLaunchEvent($pearl))->call();
				if($ev->isCancelled()){
					$pearl->flagForDespawn();
					return false;
				}
				$world->addSound($location, new ThrowSound(), $players);
				if(!$player->isCreative()){
					$inv = $player->getInventory();
					$count = $item->getCount() - 1;
					if($count === 0){
						$inv->setItemInHand(VanillaItems::AIR());
					}else{
						$inv->setItemInHand($item->setCount($count));
					}
				}
				if(PracticeCore::REPLAY){ //@phpstan-ignore-line
					$this->getDuel()?->setThrowFor($player, $item);
				}
				return true;
			}
		}
		return false;
	}

	public function setThrowPearl(bool $throw = true, bool $message = true) : void{
		if($throw){
			if($message){
				$this->getPlayer()?->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Enderpearl is out of cooldown");
			}
			$this->canPearl = true;
			$this->getExtensions()?->setXpAndProgress(0, 0.0);
		}else{
			if($message){
				$this->getPlayer()?->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Enderpearl is on cooldown");
			}
			$this->canPearl = false;
			new EnderPearlTask($this);
			$this->getExtensions()?->setXpAndProgress(10, 1.0);
		}
	}

	public function canPearl() : bool{
		return $this->canPearl;
	}

	public function setAgroPearl(bool $agro = true) : void{
		$this->isAgro = $agro;
		if($agro){
			new AgroTask($this);
		}
	}

	public function isAgroPearl() : bool{
		return $this->isAgro;
	}

	public function setGapple(bool $eat = true, bool $message = true) : void{
		if($eat){
			if($message){
				$this->getPlayer()?->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Gapple is out of cooldown");
			}
			$this->canGapple = true;
			$this->getExtensions()?->setXpAndProgress(0, 0.0);
		}else{
			if($message){
				$this->getPlayer()?->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Gapple is on cooldown");
			}
			$this->canGapple = false;
			new GappleTask($this, 7);
			$this->getExtensions()?->setXpAndProgress(7, 1.0);
		}
	}

	public function setGoldenHead(bool $eat = true, bool $message = true) : void{
		if($eat){
			if($message){
				$this->getPlayer()?->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Gapple is out of cooldown");
			}
			$this->canGapple = true;
			$this->getExtensions()?->setXpAndProgress(0, 0.0);
		}else{
			if($message){
				$this->getPlayer()?->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Gapple is on cooldown");
			}
			$this->canGapple = false;
			new GappleTask($this, 2);
			$this->getExtensions()?->setXpAndProgress(2, 1.0);
		}
	}

	public function canGapple() : bool{
		return $this->canGapple;
	}

	public function setShootArrow(bool $shoot = true, bool $message = true) : void{
		if($shoot){
			if($message){
				$this->getPlayer()?->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Arrow is out of cooldown");
			}
			$this->canArrow = true;
			$this->getExtensions()?->setXpAndProgress(0, 0.0);
			if(($kit = $this->getKitHolder()?->getKit()) !== null && ($kit->getName() === "OITC" || $kit->getName() === "Bridge")){
				if(($inv = $this->getPlayer()?->getInventory()) !== null && !$inv->contains($arrow = VanillaItems::ARROW())){
					$inv->addItem($arrow);
				}
			}
		}else{
			if($message){
				$this->getPlayer()?->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Arrow is on cooldown");
			}
			$cooldown = 1;
			if(($kit = $this->getKitHolder()?->getKit()) !== null){
				if($kit->getName() === "OITC"){
					$cooldown = 5;
				}elseif($kit->getName() === "Bridge"){
					$cooldown = 3;
				}
			}
			$this->canArrow = false;
			new ArrowTask($this, $cooldown);
			$this->getExtensions()?->setXpAndProgress($cooldown, 1.0);
		}
	}

	public function canArrow() : bool{
		return $this->canArrow;
	}

	public function useRod() : bool{
		/** @var Player $player */
		$player = $this->getPlayer();
		if(!$player->isImmobile()){
			/** @var FishingBehavior $fishingBehaviour */
			$fishingBehaviour = $this->getFishingBehavior();
			if($fishingBehaviour->isFishing()){
				$fishingBehaviour->stopFishing();
			}else{
				$fishingBehaviour->startFishing();
			}
			$this->getDuel()?->setFishingFor($player, !$fishingBehaviour->isFishing());
			return true;
		}
		return false;
	}

	public function canBuild() : bool{
		return $this->getSettingsInfo()?->getBuilderModeInfo()?->canBuild() ?? false;
	}

	public function setCanChat(bool $chat) : void{
		$this->canChat = $chat;
		if(!$chat){
			if(!$this->getPlayer()?->hasPermission("practice.permission.spambypass")){
				new ChatTask($this);
			}else{
				$this->canChat = true;
			}
		}
	}

	public function canChat() : bool{
		return $this->canChat;
	}

	public function tryChat() : bool{
		/** @var Player $player */
		$player = $this->getPlayer();
		if(PlayerManager::isGlobalMute() && !PlayerManager::isGlobalMuteBypassAble($this->player) && !$player->hasPermission("practice.permission.globalmute")){
			$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You do not have permission to chat");
			return false;
		}
		if(!$this->canChat() && !$player->hasPermission("practice.permission.spambypass")){
			$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Spamming is not allowed");
			return false;
		}
		if($this->getDurationInfo()?->isMuted()){
			$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You are muted");
			return false;
		}
		return true;
	}

	public function setLastReplied(string $replied = "") : void{
		$this->lastReplied = $replied;
	}

	public function getLastReplied() : string{
		return $this->lastReplied;
	}

	public function setChangeSkin(bool $skin) : void{
		$this->canSkin = $skin;
		if(!$skin){
			new SkinTask($this);
		}
	}

	public function canChangeSkin() : bool{
		return $this->canSkin;
	}

	public function trySkin() : bool{
		if(!$this->canChangeSkin()){
			$this->getPlayer()?->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Slow down! You are changing your skin too quickly");
			return false;
		}
		return true;
	}

	public function getPing() : int{
		return $this->pingMS;
	}

	public function updatePing(int $pingMS) : void{
		$this->getScoreboardInfo()?->updatePing($this->pingMS = $pingMS);
		if(!$this->isDefaultTag && ($player = $this->getPlayer()) !== null){
			$player->sendData(SettingsHandler::getPlayersFromType(SettingsHandler::CPS_PING), [EntityMetadataProperties::SCORE_TAG => new StringMetadataProperty(PracticeCore::COLOR . $this->getClicksInfo()->getCps() . TextFormat::WHITE . " CPS" . TextFormat::GRAY . " | " . PracticeCore::COLOR . $pingMS . TextFormat::WHITE . " MS")]);
			$player->sendData(SettingsHandler::getPlayersFromType(SettingsHandler::PING), [EntityMetadataProperties::SCORE_TAG => new StringMetadataProperty(PracticeCore::COLOR . $pingMS . TextFormat::WHITE . " MS")]);
		}
	}

	public function getStats() : string{
		/** @var Player $player */
		$player = $this->getPlayer();
		/** @var StatsInfo $statsInfo */
		$statsInfo = $this->getStatsInfo();
		$title = TextFormat::GRAY . "   » " . TextFormat::BOLD . PracticeCore::COLOR . "Stats of " . TextFormat::WHITE . $player->getDisplayName() . TextFormat::RESET . TextFormat::GRAY . " «";
		$kills = TextFormat::GRAY . "   » " . PracticeCore::COLOR . "Kills" . TextFormat::WHITE . ": " . $statsInfo->getKills() . TextFormat::GRAY . " «";
		$deaths = TextFormat::GRAY . "   » " . PracticeCore::COLOR . "Deaths" . TextFormat::WHITE . ": " . $statsInfo->getDeaths() . TextFormat::GRAY . " «";
		$coins = TextFormat::GRAY . "   » " . PracticeCore::COLOR . "Coins" . TextFormat::WHITE . ": " . $statsInfo->getCoin() . TextFormat::GRAY . " «";
		$shards = TextFormat::GRAY . "   » " . PracticeCore::COLOR . "Shards" . TextFormat::WHITE . ": " . $statsInfo->getShard() . TextFormat::GRAY . " «";
		$bp = TextFormat::GRAY . "   » " . PracticeCore::COLOR . "BattlePass" . TextFormat::WHITE . ": " . $statsInfo->getBp() . TextFormat::GRAY . " «";
		$eloFormat = TextFormat::GRAY . "   » " . PracticeCore::COLOR . "{kit}" . TextFormat::WHITE . ": {elo}" . TextFormat::GRAY . " «";
		$eloTitle = TextFormat::GRAY . "   » " . TextFormat::BOLD . PracticeCore::COLOR . "Elo of " . TextFormat::WHITE . $player->getDisplayName() . TextFormat::RESET . TextFormat::GRAY . " «";
		$kitArr = KitsManager::getDuelKits(true);
		$eloStr = "";
		$count = 0;
		$len = count($kitArr) - 1;
		$elo = $this->getEloInfo()?->getElo();
		foreach($kitArr as $kit){
			$name = $kit;
			$kit = strtolower($kit);
			if(!isset($elo[$kit])){
				$elo[$kit] = 1000;
			}
			$line = ($count === $len) ? "" : "\n";
			$str = str_replace("{kit}", $name, str_replace("{elo}", (string) $elo[$kit], $eloFormat)) . $line;
			$eloStr .= $str;
			$count++;
		}
		$lineSeparator = TextFormat::GRAY . "";
		$result = ["title" => $title, "firstSeparator" => $lineSeparator, "kills" => $kills, "deaths" => $deaths, "coins" => $coins, "shards" => $shards, "bp" => $bp, "secondSeparator" => $lineSeparator, "eloTitle" => $eloTitle, "thirdSeparator" => $lineSeparator, "elo" => $eloStr, "fourthSeparator" => $lineSeparator];
		return implode("\n", $result);
	}

	public function getInfo() : string{
		$ranksStr = implode(", ", $this->getRankInfo()?->getRanks(true));
		if(strlen($ranksStr) <= 0){
			$ranksStr = "None";
		}
		$info = ["Name" => $this->player . ($this->getDisguiseInfo()?->isDisguised() ? " (Disguise -> {$this->getDisguiseInfo()->getDisguiseData()->getDisplayName()})" : ""), "Alts" => implode(",", $this->getClientInfo()?->getAltAccounts() ?? [$this->player]), "Ranks" => $ranksStr, "Version" => $this->getClientInfo()?->getVersion(), "Device OS" => $this->getClientInfo()?->getDeviceOS(true), "Controls" => $this->getClientInfo()?->getInputAtLogin(true)];
		$title = TextFormat::GRAY . "   » " . TextFormat::BOLD . PracticeCore::COLOR . "Info of " . TextFormat::WHITE . $this->player . TextFormat::RESET . TextFormat::GRAY . " «";
		$lineSeparator = TextFormat::GRAY . "";
		$format = TextFormat::GRAY . "   » " . PracticeCore::COLOR . "%key%" . TextFormat::WHITE . ": %value%" . TextFormat::GRAY . " «";
		$result = ["title" => $title, "firstSeparator" => $lineSeparator];
		$keys = array_keys($info);
		foreach($keys as $key){
			$value = $info[$key];
			$message = str_replace(["%value%", "%key%"], [$value, $key], $format);
			$result[$key] = $message;
		}
		$result["lastSeparator"] = $lineSeparator;
		return implode("\n", $result);
	}

	public function hasLoadedData() : bool{
		return $this->loadedData;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public function loadData(array $data = []) : void{
		if(($player = $this->getPlayer()) !== null && !$this->alreadyGotDestroy()){
			$this->getStatsInfo()?->init($data);
			$this->getRankInfo()?->init($data);
			$this->getItemInfo()?->init($data);
			$this->getEloInfo()?->init($data);
			$this->getDurationInfo()?->init($data);
			$this->getSettingsInfo()?->init($data);
			$this->getDisguiseInfo()?->init($data);
			if(!PracticeCore::isLobby() && $this->getDurationInfo()?->isVoteExpired()){
				$this->getRankInfo()?->removeRank(VoteHandler::getRank()?->getName());
			}
			$flag = false;
			if(!PracticeCore::isLobby()){
				/** @var string[] $ranks */
				$ranks = $this->getRankInfo()?->getRanks(true);
				/** @var ItemInfo $itemInfo */
				$itemInfo = $this->getItemInfo();
				if($this->getDurationInfo()?->isDonateExpired()){
					/** @var StatsInfo $statsInfo */
					$statsInfo = $this->getStatsInfo();
					foreach($ranks as $rank){
						switch($rank){
							case "Vip":
							case "Booster":
							case "Media":
							case "Famous":
								$flag = true;
								$statsInfo->addCurrency(StatsInfo::COIN, 200);
								$statsInfo->addCurrency(StatsInfo::SHARD, 20);
								$itemInfo->alterCosmeticById($player, CosmeticManager::CAPE, "19", false, false, false, false);
								$itemInfo->alterCosmeticById($player, CosmeticManager::ARTIFACT, "12", false, false, false, false);
								$itemInfo->alterCosmeticById($player, CosmeticManager::PROJECTILE, "5", false, false, false, false);
								$itemInfo->alterCosmeticById($player, CosmeticManager::KILLPHRASE, "14", false, false, false, false);
								break;
							case "Mvp":
								$flag = true;
								$statsInfo->addCurrency(StatsInfo::COIN, 600);
								$statsInfo->addCurrency(StatsInfo::SHARD, 60);
								$itemInfo->alterCosmeticById($player, CosmeticManager::CAPE, "19", false, false, false, false);
								$itemInfo->alterCosmeticById($player, CosmeticManager::ARTIFACT, "12", false, false, false, false);
								$itemInfo->alterCosmeticById($player, CosmeticManager::PROJECTILE, "5", false, false, false, false);
								$itemInfo->alterCosmeticById($player, CosmeticManager::KILLPHRASE, "14", false, false, false, false);
								$itemInfo->alterCosmeticById($player, CosmeticManager::CAPE, "27", false, false, false, false);
								$itemInfo->alterCosmeticById($player, CosmeticManager::ARTIFACT, "17", false, false, false, false);
								$itemInfo->alterCosmeticById($player, CosmeticManager::PROJECTILE, "6", false, false, false, false);
								$itemInfo->alterCosmeticById($player, CosmeticManager::KILLPHRASE, "15", false, false, false, false);
								break;
							case "MvpPlus":
								$flag = true;
								$statsInfo->addCurrency(StatsInfo::COIN, 1200);
								$statsInfo->addCurrency(StatsInfo::SHARD, 120);
								$itemInfo->alterCosmeticById($player, CosmeticManager::CAPE, "19", false, false, false, false);
								$itemInfo->alterCosmeticById($player, CosmeticManager::ARTIFACT, "12", false, false, false, false);
								$itemInfo->alterCosmeticById($player, CosmeticManager::PROJECTILE, "5", false, false, false, false);
								$itemInfo->alterCosmeticById($player, CosmeticManager::KILLPHRASE, "14", false, false, false, false);
								$itemInfo->alterCosmeticById($player, CosmeticManager::CAPE, "27", false, false, false, false);
								$itemInfo->alterCosmeticById($player, CosmeticManager::ARTIFACT, "17", false, false, false, false);
								$itemInfo->alterCosmeticById($player, CosmeticManager::PROJECTILE, "6", false, false, false, false);
								$itemInfo->alterCosmeticById($player, CosmeticManager::KILLPHRASE, "15", false, false, false, false);
								break;
							case "Admin":
							case "Designer":
							case "Builder":
							case "Dev":
							case "Helper":
							case "Mod":
							case "Owner":
								$flag = true;
								$statsInfo->addCurrency(StatsInfo::COIN, 500);
								$statsInfo->addCurrency(StatsInfo::SHARD, 50);
								$itemInfo->alterCosmeticById($player, CosmeticManager::ARTIFACT, "29", false, false, false, false);
								$itemInfo->alterCosmeticById($player, CosmeticManager::CAPE, "22", false, false, false, false);
								$itemInfo->alterCosmeticById($player, CosmeticManager::CAPE, "59", false, false, false, false);
								$itemInfo->alterCosmeticById($player, CosmeticManager::PROJECTILE, "13", false, false, false, false);
								$itemInfo->alterCosmeticById($player, CosmeticManager::KILLPHRASE, "16", false, false, false, false);
								break;
						}
					}
					if($flag){
						$expiresTime = new DateTime("NOW");
						$expiresTime->modify("+1 days");
						$this->getDurationInfo()->setDonated(date_format($expiresTime, "Y-m-d-H-i"));
					}
				}
				if(empty(array_intersect($ranks, ["Admin", "Mod", "Helper", "Builder", "Designer", "Owner", "Dev"]))){
					$itemInfo->alterCosmeticById($player, CosmeticManager::ARTIFACT, "29", true, false, false, false);
					$itemInfo->alterCosmeticById($player, CosmeticManager::CAPE, "22", true, false, false, false);
					$itemInfo->alterCosmeticById($player, CosmeticManager::CAPE, "59", true, false, false, false);
					$itemInfo->alterCosmeticById($player, CosmeticManager::PROJECTILE, "13", true, false, false, false);
					$itemInfo->alterCosmeticById($player, CosmeticManager::KILLPHRASE, "16", true, false, false, false);
				}
				if(empty(array_intersect($ranks, ["MvpPlus", "Mvp"]))){
					$itemInfo->alterCosmeticById($player, CosmeticManager::CAPE, "27", true, false, false, false);
					$itemInfo->alterCosmeticById($player, CosmeticManager::ARTIFACT, "17", true, false, false, false);
					$itemInfo->alterCosmeticById($player, CosmeticManager::PROJECTILE, "6", true, false, false, false);
					$itemInfo->alterCosmeticById($player, CosmeticManager::KILLPHRASE, "15", true, false, false, false);
				}
				if(empty(array_intersect($ranks, ["MvpPlus", "Mvp", "Vip", "Booster", "Media", "Famous"]))){
					$itemInfo->alterCosmeticById($player, CosmeticManager::CAPE, "19", true, false, false, false);
					$itemInfo->alterCosmeticById($player, CosmeticManager::ARTIFACT, "12", true, false, false, false);
					$itemInfo->alterCosmeticById($player, CosmeticManager::PROJECTILE, "5", true, false, false, false);
					$itemInfo->alterCosmeticById($player, CosmeticManager::KILLPHRASE, "14", true, false, false, false);
				}
				if(empty(array_intersect($ranks, ["Voter"]))){
					//remove voter cosmetics
				}
			}
			PermissionHandler::updatePlayerPermissions($player);
			if($this->getRankInfo()?->hasHelperPermissions()){
				$this->staffStatsInfo = new StaffStatsInfo();
				$xuid = $this->getClientInfo()?->getXuid();
				DatabaseManager::getMainDatabase()->executeImplRaw([0 => "SELECT * FROM StaffStats WHERE xuid = '$xuid'"], [0 => []], [0 => SqlThread::MODE_SELECT], function(array $rows){
					if(($this->getPlayer()) !== null){
						if(isset($rows[0], $rows[0]->getRows()[0])){
							$this->getStaffStatsInfo()?->init($rows[0]->getRows()[0]);
						}
					}
				}, null);
			}
			CosmeticManager::setStrippedSkin($player, $player->getSkin(), true);
			ItemHandler::spawnHubItems($player);
			if($this->getSettingsInfo()?->isScoreboard()){
				if(PracticeCore::isLobby()){
					$this->getScoreboardInfo()?->setScoreboard(ScoreboardInfo::SCOREBOARD_LOBBY);
				}else{
					$this->getScoreboardInfo()?->setScoreboard(ScoreboardInfo::SCOREBOARD_SPAWN);
				}
			}
			ScoreboardUtil::updateSpawnScoreboard(ScoreboardUtil::ONLINE_PLAYERS);
			$result = [
				TextFormat::BOLD . PracticeCore::COLOR . "       Zeqa " . TextFormat::WHITE . "Network" . TextFormat::RESET
				, TextFormat::DARK_GRAY . ""
				, TextFormat::GRAY . "   Welcome to " . PracticeCore::COLOR . "Zeqa " . TextFormat::WHITE . "Network"
				, ""
				, TextFormat::GRAY . " - Vote: " . PracticeCore::COLOR . "vote.zeqa.net"
				, TextFormat::GRAY . " - Store: " . PracticeCore::COLOR . "store.zeqa.net"
				, TextFormat::GRAY . " - Discord: " . PracticeCore::COLOR . "discord.gg/zeqa"
				, ""
				, TextFormat::DARK_GRAY . ""
			];
			$player->sendMessage(implode("\n", $result));
			if($flag){
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Daily login claimed");
			}
			if($this->getClientInfo()?->getInputAtLogin() === IDeviceIds::KEYBOARD){
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::YELLOW . "If your mouse " . TextFormat::RED . "double clicks" . TextFormat::YELLOW . ", please download and use " . TextFormat::RED . "DC prevent" . TextFormat::YELLOW . " at discord.gg/zeqa or it will result as " . TextFormat::RED . "a ban");
			}
			$player->broadcastSound(new XpLevelUpSound(10), [$player]);
			NPCManager::spawnNPCs($player);
			$this->loadedData = true;
		}
	}

	public function saveData(string $server = null) : void{
		if($this->loadedData && !TransferHandler::isTransferring($this->player)){
			$name = $this->player;
			$lowername = strtolower($name);
			$closure = function(){
			};
			if($server !== null){
				TransferHandler::add($name, $server);
				$closure = function() use ($name){
					TransferHandler::update($name);
				};
			}
			/** @var string $xuid */
			$xuid = $this->getClientInfo()?->getXuid();
			if(!PracticeCore::isLobby()){
				$this->getDurationInfo()?->save($xuid, $lowername, $closure);
				$this->getEloInfo()?->save($xuid, $lowername, $closure);
				$this->getItemInfo()?->save($xuid, $lowername, $closure);
				$this->getRankInfo()?->save($xuid, $lowername, $closure);
				$disguiseInfo = $this->getDisguiseInfo();
				$this->getSettingsInfo()?->save($xuid, $lowername, $disguiseInfo?->isDisguised() ? $disguiseInfo->getDisguiseData()->getDisplayName() : "", $closure);
				$this->getStatsInfo()?->save($xuid, $lowername, $closure);
				$this->getStaffStatsInfo()?->save($xuid, $lowername);
			}else{
				CosmeticManager::saveSkinData($name);
			}
			$this->getClientInfo()?->save($name, $closure);
		}
	}

	public function alreadyGotDestroy() : bool{
		return $this->gotDestroy;
	}

	public function destroyCycles() : void{
		$this->gotDestroy = true;
		$this->duelHistory = [];
		$this->extensions = null;
		$this->statsInfo = null;
		$this->eloInfo = null;
		$this->settingsInfo = null;
		$this->itemInfo = null;
		$this->durationInfo = null;
		$this->rankInfo = null;
		$this->clicksInfo = null;
		$this->clientInfo = null;
		$this->disguiseInfo = null;
		$this->scoreboardInfo = null;
		$this->kitHolderInfo = null;
		$this->fishingBehavior = null;
		$this->staffStatsInfo = null;
	}
}