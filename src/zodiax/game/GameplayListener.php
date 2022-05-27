<?php

declare(strict_types=1);

namespace zodiax\game;

use pocketmine\block\Anvil;
use pocketmine\block\Barrel;
use pocketmine\block\Bed;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\BrewingStand;
use pocketmine\block\Cake;
use pocketmine\block\ChemistryTable;
use pocketmine\block\Chest;
use pocketmine\block\CraftingTable;
use pocketmine\block\DaylightSensor;
use pocketmine\block\Door;
use pocketmine\block\DragonEgg;
use pocketmine\block\EnchantingTable;
use pocketmine\block\EnderChest;
use pocketmine\block\FenceGate;
use pocketmine\block\Furnace;
use pocketmine\block\Hopper;
use pocketmine\block\ItemFrame;
use pocketmine\block\Jukebox;
use pocketmine\block\Lever;
use pocketmine\block\Liquid;
use pocketmine\block\Loom;
use pocketmine\block\ShulkerBox;
use pocketmine\block\SweetBerryBush;
use pocketmine\block\TNT;
use pocketmine\block\Trapdoor;
use pocketmine\entity\Attribute;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\BlockFormEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\block\StructureGrowEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\EntityTrampleFarmlandEvent;
use pocketmine\event\entity\ItemSpawnEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerBucketEvent;
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\inventory\PlayerCursorInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\transaction\action\DropItemAction;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\inventory\transaction\CraftingTransaction;
use pocketmine\item\EnderPearl;
use pocketmine\item\ItemIds;
use pocketmine\item\LiquidBucket;
use pocketmine\item\Snowball as PMSnowball;
use pocketmine\item\SplashPotion;
use pocketmine\item\VanillaItems;
use pocketmine\math\Facing;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket;
use pocketmine\network\mcpe\protocol\types\ActorEvent;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackInfoEntry;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use zodiax\arena\ArenaManager;
use zodiax\arena\types\FFAArena;
use zodiax\data\log\LogMonitor;
use zodiax\duel\BotHandler;
use zodiax\duel\DuelHandler;
use zodiax\duel\ReplayHandler;
use zodiax\duel\types\PlayerDuel;
use zodiax\event\EventHandler;
use zodiax\forms\display\basic\RanksInfoForm;
use zodiax\forms\display\basic\servers\RegionMenu;
use zodiax\forms\display\basic\SettingsMenuForm;
use zodiax\forms\display\cosmetic\shop\ShopMenu;
use zodiax\forms\display\game\duel\DuelMenu;
use zodiax\forms\display\game\duel\DuelRequestForm;
use zodiax\forms\display\game\duel\ReplayForm;
use zodiax\forms\display\game\event\EventForm;
use zodiax\forms\display\game\FFAForm;
use zodiax\forms\display\game\spectate\SpectateMenu;
use zodiax\forms\display\game\spectate\TeleportForm;
use zodiax\forms\display\game\training\blockin\settings\BlockInSettings;
use zodiax\forms\display\game\training\clutch\ClutchSetting;
use zodiax\forms\display\game\training\reduce\ReduceSetting;
use zodiax\forms\display\game\training\TrainingMenu;
use zodiax\forms\display\party\duel\PartyDuelForm;
use zodiax\forms\display\party\duel\PartyDuelMenu;
use zodiax\forms\display\party\LeavePartyForm;
use zodiax\forms\display\party\PartyMainMenu;
use zodiax\forms\display\party\PartySettingsMenu;
use zodiax\game\entity\CustomItemEntity;
use zodiax\game\entity\GenericHuman;
use zodiax\game\entity\projectile\Arrow;
use zodiax\game\entity\projectile\Snowball;
use zodiax\game\inventories\menus\inventory\DoubleChestInv;
use zodiax\game\inventories\menus\PostMatchInv;
use zodiax\game\items\ItemHandler;
use zodiax\game\npc\NPCManager;
use zodiax\game\world\BlockRemoverHandler;
use zodiax\kits\info\KnockbackInfo;
use zodiax\misc\AbstractListener;
use zodiax\party\duel\PartyDuel;
use zodiax\party\duel\PartyDuelHandler;
use zodiax\party\PracticeParty;
use zodiax\player\info\client\ClientInfo;
use zodiax\player\info\duel\DuelInfo;
use zodiax\player\misc\cosmetic\CosmeticManager;
use zodiax\player\misc\queue\QueueHandler;
use zodiax\player\misc\SettingsHandler;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\proxy\TransferHandler;
use zodiax\ranks\RankHandler;
use zodiax\training\TrainingHandler;
use function boolval;
use function in_array;
use function min;
use function mt_getrandmax;
use function mt_rand;
use function sqrt;
use function strtolower;
use function substr;

class GameplayListener extends AbstractListener{

	/** @var array<string, array<string, bool|int>> $cachedData */
	public static array $cachedData = [];

	public function onJoin(PlayerJoinEvent $event) : void{
		self::$cachedData[$event->getPlayer()->getName()] = ["initialKnockbackMotion" => false, "shouldCancelKBMotion" => false, "lastAttackedActorTime" => 0];
	}

	public function onLeave(PlayerQuitEvent $event) : void{
		unset(self::$cachedData[$event->getPlayer()->getName()]);
	}

	public function onDataPacketSend(DataPacketSendEvent $event) : void{
		if(PracticeCore::isPackEnable()){
			$packets = $event->getPackets();
			foreach($packets as $packet){
				switch($packet->pid()){
					case AddPlayerPacket::NETWORK_ID:
						/** @var AddPlayerPacket $packet */
						if(($session = PlayerManager::getSession($player = PlayerManager::getPlayerExact($packet->username))) !== null && $session->isDefaultTag()){
							PracticeCore::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($session, $player){
								if($session !== null && $session->isDefaultTag() && $player->isOnline()){
									/** @var ClientInfo $clientInfo */
									$clientInfo = $session->getClientInfo();
									$player->sendData(SettingsHandler::getPlayersFromType(SettingsHandler::DEVICE), [EntityMetadataProperties::SCORE_TAG => new StringMetadataProperty(PracticeCore::COLOR . $clientInfo->getDeviceOS(true, PracticeCore::isPackEnable()) . TextFormat::GRAY . " | " . TextFormat::WHITE . $clientInfo->getInputAtLogin(true))]);
								}
							}), 5);
						}
						break;
					case ResourcePacksInfoPacket::NETWORK_ID:
						/** @var ResourcePacksInfoPacket $packet */
						$packInfo = PracticeCore::getPacksInfo();
						foreach($packet->resourcePackEntries as $index => $entry){
							if(isset($packInfo[$id = $entry->getPackId()]) && $packInfo[$id] !== $entry->getEncryptionKey()){
								$packet->resourcePackEntries[$index] = new ResourcePackInfoEntry($id, $entry->getVersion(), $entry->getSizeBytes(), $packInfo[$id], "", $id, false);
							}
						}
						break;
				}
			}
		}
	}

	public function onDataPacketReceive(DataPacketReceiveEvent $event) : void{
		/** @var Player $player */
		$player = $event->getOrigin()->getPlayer();
		if(($session = PlayerManager::getSession($player)) !== null){
			$packet = $event->getPacket();
			switch($packet->pid()){
				case PlayerAuthInputPacket::NETWORK_ID:
					/** @var PlayerAuthInputPacket $packet */
					$clientInfo = $session->getClientInfo();
					if($clientInfo?->checkInput($packet->getInputMode()) && $session->isDefaultTag()){
						$player->sendData(SettingsHandler::getPlayersFromType(SettingsHandler::DEVICE), [EntityMetadataProperties::SCORE_TAG => new StringMetadataProperty(PracticeCore::COLOR . $clientInfo->getDeviceOS(true, PracticeCore::isPackEnable()) . TextFormat::GRAY . " | " . TextFormat::WHITE . $clientInfo->getInputAtLogin(true))]);
					}
					if($session->getSettingsInfo()?->isAutoSprint()){
						if($player->isSprinting() && $packet->hasFlag(PlayerAuthInputFlags::DOWN)){
							$player->setSprinting(false);
						}elseif(!$player->isSprinting() && $packet->hasFlag(PlayerAuthInputFlags::UP)){
							$player->setSprinting();
						}
					}
					break;
				case AnimatePacket::NETWORK_ID:
					/** @var AnimatePacket $packet */
					if($packet->action === AnimatePacket::ACTION_SWING_ARM){
						$event->cancel();
						$player->getServer()->broadcastPackets($player->getViewers(), [$packet]);
						if(PracticeCore::REPLAY){ // @phpstan-ignore-line
							$session->getDuel()?->setAnimationFor($player, ActorEvent::ARM_SWING);
						}
					}
					break;
				case LevelSoundEventPacket::NETWORK_ID:
					/** @var LevelSoundEventPacket $packet */
					if($packet->sound === LevelSoundEvent::ATTACK_NODAMAGE){
						$session->getClicksInfo()?->addClick();
						if($session->getClientInfo()?->isPE() && !$player->useHeldItem()){
							$player->getNetworkSession()->getInvManager()?->syncSlot($player->getInventory(), $player->getInventory()->getHeldItemIndex());
						}
					}
					break;
				case InventoryTransactionPacket::NETWORK_ID:
					/** @var InventoryTransactionPacket $packet */
					switch($packet->trData->getTypeId()){
						case InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY:
							/** @var UseItemOnEntityTransactionData $trData */
							$trData = $packet->trData;
							if($trData->getActionType() === UseItemOnEntityTransactionData::ACTION_ATTACK){
								$entityId = $trData->getActorRuntimeId();
								$session->getClicksInfo()?->addClick();
								if($session->getSettingsInfo()?->isMoreCritical()){
									$player->getServer()->broadcastPackets([$player], [AnimatePacket::create($entityId, AnimatePacket::ACTION_CRITICAL_HIT)]);
								}
								if(($npc = NPCManager::getNPCfromEntityId($entityId)) !== null){
									switch($npc->getRealName()){
										case "Rules":
											break;
										case "Ranks":
											RanksInfoForm::onDisplay($player);
											break;
									}
								}elseif(($blockInEntity = TrainingHandler::getBlockInEntityfromEntityId($entityId)) !== null){
									if(($blockIn = PlayerManager::getSession($player)?->getBlockIn()) !== null && $blockInEntity->getId() === $entityId){
										$blockIn->onCoreBreak($player);
										return;
									}
								}
							}
							break;
						case InventoryTransactionPacket::TYPE_USE_ITEM:
							/** @var UseItemTransactionData $trData */
							$trData = $packet->trData;
							if($trData->getActionType() === UseItemTransactionData::ACTION_CLICK_BLOCK){
								if($session->getClientInfo()?->isPE() && !$player->useHeldItem()){
									$player->getNetworkSession()->getInvManager()?->syncSlot($player->getInventory(), $player->getInventory()->getHeldItemIndex());
								}
							}
							break;
					}
					break;
				case ActorEventPacket::NETWORK_ID:
					/** @var ActorEventPacket $packet */
					if(PracticeCore::REPLAY && $packet->eventId === ActorEvent::EATING_ITEM){ // @phpstan-ignore-line
						$session->getDuel()?->setAnimationFor($player, ActorEvent::EATING_ITEM, $packet->eventData);
					}
					break;
			}
		}
	}

	public function onItemUse(PlayerItemUseEvent $event) : void{
		if(($session = PlayerManager::getSession($player = $event->getPlayer())) !== null && !$session->isFrozen()){
			if(($session->isInHub() && $session->getKitHolder()?->isEditingKit()) || $session->getDisguiseInfo()?->isProcessing() || TransferHandler::isTransferring($player->getName())){
				$event->cancel();
				return;
			}
			$item = $event->getItem();
			switch($item->getId()){
				case ItemIds::BOW:
					if($session->canArrow()){
						return;
					}
					break;
				case ItemIds::SNOWBALL:
					/** @var PMSnowball $item */
					$session->throwSnowball($item);
					break;
				case ItemIds::ENDER_PEARL:
					/** @var EnderPearl $item */
					$session->throwPearl($item);
					break;
				case ItemIds::SPLASH_POTION:
					if($item->getMeta() !== 22){
						return;
					}
					/** @var SplashPotion $item */
					$session->throwPotion($item);
					break;
				case ItemIds::FISHING_ROD:
					$session->useRod();
					break;
				case ItemIds::MUSHROOM_STEW:
					$player->setHealth($player->getHealth() + 8);
					$player->getInventory()->setItemInHand(VanillaItems::AIR());
					break;
				case ItemIds::GOLDEN_APPLE:
					if($session->canGapple()){
						return;
					}
					break;
				case ItemIds::MOB_HEAD:
					if($item->getMeta() === 4){
						if($session->canGapple()){
							$session->setGoldenHead(false, false);
							if(!$player->isCreative()){
								if(($count = $item->getCount() - 1) === 0){
									$player->getInventory()->setItemInHand(VanillaItems::AIR());
								}else{
									$player->getInventory()->setItemInHand($item->setCount($count));
								}
							}
							$effects = $player->getEffects();
							$effects->add(new EffectInstance(VanillaEffects::SPEED(), PracticeUtil::secondsToTicks(8), 0));
							$effects->add(new EffectInstance(VanillaEffects::REGENERATION(), PracticeUtil::secondsToTicks(5), 2));
						}
					}else{
						return;
					}
					break;
				default:
					if(($tag = $item->getNamedTag()->getTag("PracticeItem")) !== null){
						switch($tag->getValue()){
							case ItemHandler::HUB_LOBBY:
								if($session->isInHub()){
									RegionMenu::onDisplay($player);
								}
								break;
							case ItemHandler::HUB_FFA:
								if($session->isInHub()){
									FFAForm::onDisplay($player);
								}
								break;
							case ItemHandler::HUB_DUELS:
								if($session->isInHub()){
									DuelMenu::onDisplay($player);
								}
								break;
							case ItemHandler::HUB_BOT:
								$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Coming Soon...");
								/*if($session->isInHub()){
									 BotForm::onDisplay($player);
								}*/
								break;
							case ItemHandler::HUB_EVENT:
								if($session->isInHub()){
									EventForm::onDisplay($player);
								}
								break;
							case ItemHandler::HUB_TRAINING:
								if($session->isInHub()){
									TrainingMenu::onDisplay($player);
								}
								break;
							case ItemHandler::HUB_PARTY:
								if($session->isInHub()){
									PartyMainMenu::onDisplay($player);
								}
								break;
							case ItemHandler::HUB_SPEC:
								if($session->isInHub()){
									SpectateMenu::onDisplay($player);
								}
								break;
							case ItemHandler::HUB_SHOP:
								if($session->isInHub()){
									ShopMenu::onDisplay($player);
								}
								break;
							case ItemHandler::HUB_SETTINGS:
								if($session->isInHub()){
									SettingsMenuForm::onDisplay($player);
								}
								break;
							case ItemHandler::HUB_LEAVE:
								if($session->isInHub()){
									if($session->isInParty()){
										/** @var PracticeParty $party */
										$party = $session->getParty();
										if($party->isOwner($player) && $party->isInQueue()){
											PartyDuelHandler::removeFromQueue($party);
											ItemHandler::spawnPartyItems($player);
										}else{
											LeavePartyForm::onDisplay($player);
										}
									}elseif($session->isInQueue()){
										DuelHandler::removeFromQueue($player);
										ItemHandler::spawnHubItems($player);
									}elseif($session->isInBotQueue()){
										BotHandler::removeFromQueue($player);
										ItemHandler::spawnHubItems($player);
									}elseif(QueueHandler::isInQueue($player)){
										QueueHandler::removePlayer($player);
									}
								}elseif(($duel = $session->getBotDuel() ?? ReplayHandler::getReplayFrom($player) ?? $session->getClutch() ?? $session->getReduce()) !== null){
									$duel->setEnded();
								}elseif(($event = $session->getEvent()) !== null){
									$event->removePlayer($player->getDisplayName());
								}elseif(($ffa = $session->getSpectateArena()) !== null){
									$ffa->removeSpectator($player->getName());
								}elseif(($duel = (DuelHandler::getDuelFromSpec($player) ?? BotHandler::getDuelFromSpec($player) ?? PartyDuelHandler::getDuelFromSpec($player) ?? EventHandler::getEventFromSpec($player) ?? TrainingHandler::getClutchFromSpec($player) ?? TrainingHandler::getReduceFromSpec($player) ?? TrainingHandler::getBlockInFromSpec($player))) !== null){
									$duel->removeSpectator($player->getDisplayName());
								}elseif(($blockIn = $session->getBlockIn()) !== null){
									if($blockIn->isOwner($player)){
										$blockIn->setEnded();
									}else{
										$blockIn->removePlayer($player);
									}
								}
								break;
							case ItemHandler::CLUTCH_PLAY:
								if(($clutch = $session->getClutch()) !== null){
									$clutch->setAsStarting();
								}
								break;
							case ItemHandler::CLUTCH_SETTINGS:
								if(($clutch = $session->getClutch()) !== null){
									ClutchSetting::onDisplay($player, $clutch);
								}
								break;
							case ItemHandler::REDUCE_PLAY:
								if(($reduce = $session->getReduce()) !== null){
									$reduce->setAsStarting();
								}
								break;
							case ItemHandler::REDUCE_SETTINGS:
								if(($reduce = $session->getReduce()) !== null){
									ReduceSetting::onDisplay($player, $reduce);
								}
								break;
							case ItemHandler::BLOCKIN_PLAY:
								if(($blockIn = $session->getBlockIn()) !== null){
									$blockIn->setAsStarting();
								}
								break;
							case ItemHandler::BLOCKIN_SETTINGS:
								if(($blockIn = $session->getBlockIn()) !== null){
									BlockInSettings::onDisplay($player, $blockIn);
								}
								break;
							case ItemHandler::PARTY_SETTINGS:
								if($session->isInHub() && $session->isInParty()){
									PartySettingsMenu::onDisplay($player);
								}
								break;
							case ItemHandler::PARTY_DUEL:
								if($session->isInHub() && $session->isInParty()){
									PartyDuelForm::onDisplay($player);
								}
								break;
							case ItemHandler::PARTY_INBOX:
								if($session->isInHub() && $session->isInParty()){
									PartyDuelMenu::onDisplay($player);
								}
								break;
							case ItemHandler::REPLAY_FORWARD:
								if(($replay = ReplayHandler::getReplayFrom($player)) !== null){
									$replay->fastForward();
								}
								break;
							case ItemHandler::REPLAY_REWIND:
								if(($replay = ReplayHandler::getReplayFrom($player)) !== null){
									$replay->rewind();
								}
								break;
							case ItemHandler::REPLAY_PLAY:
								if(($replay = ReplayHandler::getReplayFrom($player)) !== null){
									$replay->setPaused(false);
								}
								break;
							case ItemHandler::REPLAY_PAUSE:
								if(($replay = ReplayHandler::getReplayFrom($player)) !== null){
									$replay->setPaused(true);
								}
								break;
							case ItemHandler::REPLAY_SETTINGS:
								if(($replay = ReplayHandler::getReplayFrom($player)) !== null){
									ReplayForm::onDisplay($player, ["replay" => $replay]);
								}
								break;
							case ItemHandler::SPEC_TELEPORTER:
								if(($game = ($session->getSpectateArena() ?? DuelHandler::getDuelFromSpec($player) ?? BotHandler::getDuelFromSpec($player) ?? PartyDuelHandler::getDuelFromSpec($player) ?? TrainingHandler::getClutchFromSpec($player) ?? TrainingHandler::getReduceFromSpec($player) ?? TrainingHandler::getBlockInFromSpec($player))) !== null){
									TeleportForm::onDisplay($player, $game);
								}
								break;
						}
					}
					return;
			}
		}
		$event->cancel();
	}

	public function onItemInteract(PlayerInteractEvent $event) : void{
		if(($session = PlayerManager::getSession($event->getPlayer())) !== null && !$session->isVanish() && !$session->isFrozen()){
			$block = $event->getBlock();
			if(($session->isInHub() && $session->getKitHolder()?->isEditingKit()) || ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK && !$session->canBuild() && ($block instanceof Anvil || $block instanceof Barrel || $block instanceof Bed || $block instanceof BrewingStand || $block instanceof Cake || $block instanceof ChemistryTable || $block instanceof Chest || $block instanceof CraftingTable || $block instanceof DaylightSensor || $block instanceof Door || $block instanceof DragonEgg || $block instanceof EnchantingTable || $block instanceof EnderChest || $block instanceof FenceGate || $block instanceof Furnace || $block instanceof Hopper || $block instanceof ItemFrame || $block instanceof Jukebox || $block instanceof Lever || $block instanceof Loom || $block instanceof ShulkerBox || $block instanceof SweetBerryBush || $block instanceof TNT || $block instanceof Trapdoor))){
				$event->cancel();
			}
			return;
		}
		$event->cancel();
	}

	public function onItemDrop(PlayerDropItemEvent $event) : void{
		if(($session = PlayerManager::getSession($player = $event->getPlayer())) !== null){
			if($session->getKitHolder()?->hasKit() && ($item = $event->getItem())->getId() === ItemIds::GOLDEN_APPLE){
				$itemEntity = new CustomItemEntity($player->getLocation(), $item, $player);
				$itemEntity->setPickupDelay(40);
				$itemEntity->spawnToAll();
				return;
			}elseif($session->canBuild()){
				return;
			}
		}
		$event->cancel();
	}

	public function onItemPickup(EntityItemPickupEvent $event) : void{
		$player = $event->getEntity();
		if($player instanceof Player && ($session = PlayerManager::getSession($player)) !== null && !$session->isVanish()){
			return;
		}
		$event->cancel();
	}

	public function onConsume(PlayerItemConsumeEvent $event) : void{
		if(($session = PlayerManager::getSession($player = $event->getPlayer())) !== null && !$session->isVanish() && !$session->isFrozen()){
			$kitHolder = $session->getKitHolder();
			if($kitHolder?->isEditingKit()){
				$event->cancel();
				return;
			}
			switch($event->getItem()->getId()){
				case ItemIds::GOLDEN_APPLE:
					if(($kit = $kitHolder?->getKit()) !== null){
						switch($kit->getName()){
							case "Bridge":
								$player->setHealth($player->getMaxHealth());
								return;
							case "BuildUHC":
								return;
							default:
								if($session->canGapple()){
									$session->setGapple(false);
									return;
								}
								break;
						}
					}
					break;
				case ItemIds::ENCHANTED_GOLDEN_APPLE:
					if($session->canGapple()){
						$session->setGapple(false);
						return;
					}
					break;
			}
		}
		$event->cancel();
	}

	public function onItemMove(InventoryTransactionEvent $event) : void{
		$transaction = $event->getTransaction();
		if($transaction instanceof CraftingTransaction){
			$event->cancel();
			return;
		}
		if(($session = PlayerManager::getSession($player = $transaction->getSource())) !== null && !$session->isVanish() && !$session->isFrozen()){
			$actions = $transaction->getActions();
			if($session->isInHub() && $session->getKitHolder()?->isEditingKit()){
				foreach($actions as $action){
					if($action instanceof SlotChangeAction){
						$inventory = $action->getInventory();
						if(!$inventory instanceof PlayerInventory && !$inventory instanceof PlayerCursorInventory){
							$event->cancel();
							return;
						}
						if($action->getSourceItem()->getId() === $action->getTargetItem()->getId()){
							$event->cancel();
							return;
						}
					}else{
						$event->cancel();
						return;
					}
				}
			}elseif($session->isInArena() || $session->isInDuel() || $session->isInBotDuel() || $session->isInGame() || $session->isInPartyDuel() || $session->isInClutch() || $session->isInReduce() || $session->isInBlockIn()){
				foreach($actions as $action){
					if($action instanceof SlotChangeAction){
						$inventory = $action->getInventory();
						if(!$inventory instanceof PlayerInventory && !$inventory instanceof PlayerCursorInventory){
							$event->cancel();
						}
					}elseif($action instanceof DropItemAction && $action->getTargetItem()->getId() !== ItemIds::GOLDEN_APPLE){
						$event->cancel();
					}
				}
			}elseif(!$session->canBuild()){
				$event->cancel();
				foreach($actions as $action){
					if($action instanceof SlotChangeAction){
						$inventory = $action->getInventory();
						if($inventory instanceof DoubleChestInv && ($tag = $action->getSourceItem()->getNamedTag()->getTag("SwitchKey")) !== null){
							/** @var PostMatchInv $baseMenu */
							$baseMenu = $inventory->getBaseMenu();
							/** @var DuelInfo $duelInfo */
							$duelInfo = $baseMenu->getDuelInfo();
							$winner = boolval($tag->getValue());
							$player->removeCurrentWindow();
							PracticeCore::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player, $duelInfo, $winner){
								if($player->isOnline()){
									(new PostMatchInv($duelInfo, $player->getPosition(), $winner))->send($player);
								}
							}), 10);
							break;
						}
					}
				}
			}
		}else{
			$event->cancel();
		}
	}

	public function onExhaust(PlayerExhaustEvent $event) : void{
		$event->cancel();
	}

	public function onCraft(CraftItemEvent $event) : void{
		$event->cancel();
	}

	public function onBlockPlace(BlockPlaceEvent $event) : void{
		if(($session = PlayerManager::getSession($player = $event->getPlayer())) !== null && !$session->isVanish() && !$session->isFrozen()){
			if(($kit = $session->getKitHolder()?->getKit()) !== null && $kit->getMiscKitInfo()->canBuild()){
				$block = $event->getBlock();
				if($session->isInArena()){
					$kitName = $kit->getName();
					if($kitName === "Build"){
						BlockRemoverHandler::setBlockToRemove($block->getPosition());
						return;
					}elseif($kitName === "BuildUHC"){
						if($block->getId() !== BlockLegacyIds::MOB_HEAD_BLOCK){
							BlockRemoverHandler::setBlockToRemove($block->getPosition());
							return;
						}
					}
				}elseif(($duel = $session->getDuel() ?? $session->getBotDuel() ?? $session->getPartyDuel() ?? $session->getClutch() ?? $session->getReduce() ?? $session->getBlockIn()) !== null){
					if($duel->tryBreakOrPlaceBlock($player, $block, false)){
						return;
					}
				}
			}elseif($session->canBuild()){
				return;
			}
		}
		$event->cancel();
	}

	public function onBlockBreak(BlockBreakEvent $event) : void{
		$player = $event->getPlayer();
		if(($session = PlayerManager::getSession($player)) !== null && !$session->isVanish() && !$player->isImmobile()){
			if(($kit = $session->getKitHolder()?->getKit()) !== null && $kit->getMiscKitInfo()->canBuild()){
				$event->setDrops([]);
				$block = $event->getBlock();
				if($session->isInArena()){
					$kitName = $kit->getName();
					if($kitName === "Build"){
						if(in_array($block->getId(), [BlockLegacyIds::SANDSTONE, BlockLegacyIds::COBWEB], true)){
							return;
						}
					}elseif($kitName === "BuildUHC"){
						if(($block->getId() === BlockLegacyIds::WOODEN_PLANKS && $block->getMeta() === 0) || in_array($block->getId(), [BlockLegacyIds::COBBLESTONE, BlockLegacyIds::OBSIDIAN, BlockLegacyIds::FLOWING_WATER, BlockLegacyIds::WATER, BlockLegacyIds::FLOWING_LAVA, BlockLegacyIds::LAVA], true)){
							return;
						}
					}
				}elseif(($duel = $session->getDuel() ?? $session->getBotDuel() ?? $session->getPartyDuel() ?? $session->getClutch() ?? $session->getReduce() ?? $session->getBlockIn()) !== null){
					if($duel->tryBreakOrPlaceBlock($player, $block)){
						return;
					}
				}
			}elseif($session->canBuild()){
				return;
			}
		}
		$event->cancel();
	}

	public function onBlockSpread(BlockSpreadEvent $event) : void{
		$newState = $event->getNewState();
		if($newState instanceof Liquid){
			$block = $event->getBlock();
			$pos = $block->getPosition();
			$target = null;
			if(ArenaManager::MAPS_MODE === ArenaManager::ADVANCE){
				/** @var FFAArena $ffa */
				if(($ffa = ArenaManager::getArena("BuildUHC")) !== null && $pos->distance($ffa->getSpawns(1)[0]) < 100){
					BlockRemoverHandler::setBlockToRemove($pos);
					return;
				}
				/** @var PlayerDuel[] $duels */
				$duels = DuelHandler::getDuels();
				foreach($duels as $duel){
					if($duel->getKit() === "BuildUHC" && $pos->distance($duel->getCenterPosition()) < 100){
						$target = $duel;
						break;
					}
				}
				if($target === null){
					/** @var PartyDuel[] $duels */
					$duels = PartyDuelHandler::getDuels();
					foreach($duels as $duel){
						if($duel->getKit() === "BuildUHC" && $pos->distance($duel->getCenterPosition()) < 100){
							$target = $duel;
							break;
						}
					}
				}
			}else{ // @phpstan-ignore-line
				if(($ffa = ArenaManager::getArena("BuildUHC")) !== null && $pos->getWorld()->getId() === $ffa->getWorld()?->getId()){
					BlockRemoverHandler::setBlockToRemove($pos);
					return;
				}
				$target = DuelHandler::getDuelFromWorld($pos->getWorld()) ?? PartyDuelHandler::getDuelFromWorld($pos->getWorld());
			}
			if($target !== null){
				$newState->position($pos->getWorld(), $pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ()); //@phpstan-ignore-line
				if($target->tryUpdateBlock($newState, false)){
					return;
				}
			}
		}
		$event->cancel();
	}

	public function onBlockForm(BlockFormEvent $event) : void{
		$newState = $event->getNewState();
		if(in_array($newState->getId(), [BlockLegacyIds::STONE, BlockLegacyIds::COBBLESTONE, BlockLegacyIds::OBSIDIAN], true)){
			$block = $event->getBlock();
			$pos = $block->getPosition();
			$target = null;
			if(ArenaManager::MAPS_MODE === ArenaManager::ADVANCE){
				/** @var FFAArena $ffa */
				if(($ffa = ArenaManager::getArena("BuildUHC")) !== null && $pos->distance($ffa->getSpawns(1)[0]) < 100){
					BlockRemoverHandler::setBlockToRemove($pos);
					return;
				}
				/** @var PlayerDuel[] $duels */
				$duels = DuelHandler::getDuels();
				foreach($duels as $duel){
					if($duel->getKit() === "BuildUHC" && $pos->distance($duel->getCenterPosition()) < 100){
						$target = $duel;
						break;
					}
				}
				if($target === null){
					/** @var PartyDuel[] $duels */
					$duels = PartyDuelHandler::getDuels();
					foreach($duels as $duel){
						if($duel->getKit() === "BuildUHC" && $pos->distance($duel->getCenterPosition()) < 100){
							$target = $duel;
							break;
						}
					}
				}
			}else{ // @phpstan-ignore-line
				if(($ffa = ArenaManager::getArena("BuildUHC")) !== null && $pos->getWorld()->getId() === $ffa->getWorld()?->getId()){
					BlockRemoverHandler::setBlockToRemove($pos);
					return;
				}
				$target = DuelHandler::getDuelFromWorld($pos->getWorld()) ?? PartyDuelHandler::getDuelFromWorld($pos->getWorld());
			}
			if($target !== null){
				$newState->position($pos->getWorld(), $pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ()); //@phpstan-ignore-line
				if($target->tryUpdateBlock($newState, false)){
					return;
				}
			}
		}
		$event->cancel();
	}

	public function onBlockGrow(StructureGrowEvent $event) : void{
		if(in_array($event->getBlock()->getId(), [BlockLegacyIds::BAMBOO, BlockLegacyIds::BAMBOO_SAPLING], true) && $event->getPlayer() === null){
			$event->cancel();
		}
	}

	public function onBucketFillBucket(PlayerBucketEvent $event) : void{
		$player = $event->getPlayer();
		if(($session = PlayerManager::getSession($player)) !== null && !$session->isVanish() && !$player->isImmobile()){
			if(($kit = $session->getKitHolder()?->getKit()) !== null && $kit->getMiscKitInfo()->canBuild()){
				if($session->isInArena() && $kit->getName() === "BuildUHC"){
					if($event instanceof PlayerBucketEmptyEvent && $event->getBucket() instanceof LiquidBucket){
						BlockRemoverHandler::setBlockToRemove($event->getBlockClicked()->getPosition());
					}
					return;
				}elseif(($duel = $session->getDuel() ?? $session->getPartyDuel()) !== null){
					$block = $event->getBlockClicked();
					if($event instanceof PlayerBucketFillEvent){
						$block = $block->getSide(Facing::opposite($event->getBlockFace()));
					}
					if($duel->tryBreakOrPlaceBlock($player, $block, ($event instanceof PlayerBucketFillEvent))){
						return;
					}
				}
			}elseif($session->canBuild()){
				return;
			}
		}
		$event->cancel();
	}

	public function onLeavesDecay(LeavesDecayEvent $event) : void{
		$event->cancel();
	}

	public function onFarmland(EntityTrampleFarmlandEvent $event) : void{
		$event->cancel();
	}

	public function onFireSpread(BlockBurnEvent $event) : void{
		$event->cancel();
	}

	public function onItemSpawnEvent(ItemSpawnEvent $event) : void{
		$entity = $event->getEntity();
		$player = $entity->getOwningEntity();
		if($player instanceof Player && ($session = PlayerManager::getSession($player)) !== null){
			if($session->getKitHolder()?->hasKit() && ($item = $entity->getItem())->getId() === ItemIds::GOLDEN_APPLE){
				PracticeCore::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player, $item){
					if($player->isOnline()){
						$player->getInventory()->addItem($item);
					}
				}), 5);
			}elseif($session->canBuild()){
				return;
			}
		}
		$entity->flagForDespawn();
	}

	public function onEntityDamaged(EntityDamageEvent $event) : void{
		$player = $event->getEntity();
		if($player instanceof Player && ($session = PlayerManager::getSession($player)) !== null && !$player->isImmobile()){
			$cause = $event->getCause();
			if($cause === EntityDamageEvent::CAUSE_VOID){
				$event->cancel();
				$session->onDeath(true);
				return;
			}
			if($cause === EntityDamageEvent::CAUSE_FALL || $cause === EntityDamageEvent::CAUSE_CONTACT || $session->isInHub() || $session->isVanish()){
				$event->cancel();
				return;
			}
			if(PracticeCore::REPLAY){ // @phpstan-ignore-line
				$session->getDuel()?->setAnimationFor($player, ActorEvent::HURT_ANIMATION);
			}
			if($player->getHealth() - $event->getFinalDamage() <= 0){
				if($event instanceof EntityDamageByEntityEvent){
					$damager = $event->getDamager();
					if($damager instanceof Player){
						Server::getInstance()->broadcastPackets([$damager], [LevelSoundEventPacket::create(LevelSoundEvent::HURT, $player->getPosition(), -1, "minecraft:player", false, false)]);
					}
				}
				$event->cancel();
				$session->onDeath();
			}
			return;
		}elseif($player instanceof GenericHuman){
			return;
		}
		$event->cancel();
	}

	/**
	 * @priority LOWEST
	 */
	public function onEntityDamagedByEntity(EntityDamageByEntityEvent $event) : void{
		$player = $event->getEntity();
		$damager = $event->getDamager();
		if($player->getId() !== $damager?->getId() && $event->getModifier(EntityDamageEvent::MODIFIER_PREVIOUS_DAMAGE_COOLDOWN) >= 0.0){
			if($player instanceof Player && $damager instanceof Player && ($pSession = PlayerManager::getSession($player)) !== null && ($dSession = PlayerManager::getSession($damager)) !== null){
				if(($attackedKit = $pSession->getKitHolder()?->getKit()) !== null && $dSession->getKitHolder()?->hasKit()){
					if($attackedKit->getMiscKitInfo()->canDamagePlayers() && !$player->isImmobile() && !$damager->isImmobile()){
						if(($arena = $pSession->getArena()) !== null){
							if(!$arena->canInterrupt()){
								if($pSession->isInCombat() && !$dSession->isInCombat()){
									$event->cancel();
									$damager->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Interrupting is not allowed!");
									return;
								}elseif($dSession->isInCombat() && ($target = $dSession->getTarget()) !== null && $target->getName() !== $player->getName()){
									$event->cancel();
									$damager->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Interrupting is not allowed!");
									return;
								}
							}
							$pSession->setInCombat($damager);
							$dSession->setInCombat($player);
						}elseif(($game = $pSession->getPartyDuel() ?? $pSession->getBlockIn()) !== null){
							if(!$game->isRunning() || $game->getTeam($player)?->isInTeam($damager)){
								$event->cancel();
								return;
							}
						}
						$kbInfo = $attackedKit->getKnockbackInfo();
						$event->setKnockBack(0.0);
						$event->setAttackCooldown($kbInfo->getSpeed());
						self::$cachedData[$damager->getName()]["lastAttackedActorTime"] = Server::getInstance()->getTick();
						$this->knockBack($player, $damager, $kbInfo);
						if(($duel = $pSession->getDuel() ?? $pSession->getPartyDuel()) !== null){
							$duel->addHitTo($damager, $event->isApplicable(EntityDamageEvent::MODIFIER_CRITICAL));
						}
						return;
					}
				}elseif($damager->getInventory()->getItemInHand()->getNamedTag()->getTag("PracticeItem")?->getValue() === ItemHandler::HUB_DUELS && $pSession->isInHub() && $dSession->isInHub()){
					DuelRequestForm::onDisplay($damager, $player);
				}
				$event->cancel();
				return;
			}
			$attackedKit = null;
			$attackerKit = null;
			if($player instanceof Player && ($session = PlayerManager::getSession($player)) !== null && $damager instanceof GenericHuman){
				$attackedKit = $session->getKitHolder()?->getKit();
				$attackerKit = $damager->getKitHolder()->getKit();
			}elseif($player instanceof GenericHuman && $damager instanceof Player && ($session = PlayerManager::getSession($damager)) !== null){
				$attackedKit = $player->getKitHolder()->getKit();
				$attackerKit = $session->getKitHolder()?->getKit();
			}
			if($attackedKit !== null && $attackerKit !== null && $attackedKit->getMiscKitInfo()->canDamagePlayers()){
				$kbInfo = $attackedKit->getKnockbackInfo();
				if($attackedKit->getName() === "Clutch"){
					if($player instanceof Player && ($session = PlayerManager::getSession($player)) !== null && ($clutch = $session->getClutch()) !== null){
						$kbInfo = clone $clutch->getKnockbackInfo();
						$kbInfo->setSpeed(0);
					}
				}
				$event->setKnockBack(0.0);
				if($event->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK){
					$event->setAttackCooldown($kbInfo->getSpeed());
				}
				if($player instanceof Player){
					/** @var GenericHuman $damager */
					$this->knockBack($player, $damager, $kbInfo);
				}elseif($player instanceof GenericHuman){
					/** @var Player $damager */
					self::$cachedData[$damager->getName()]["lastAttackedActorTime"] = Server::getInstance()->getTick();
					$player->actuallyDoknockBack($damager, $kbInfo);
				}
				return;
			}
		}
		$event->cancel();
	}

	/**
	 * @handleCancelled
	 *
	 * @priority LOW
	 */
	public function onEntityDamageByChildEntityEvent(EntityDamageByChildEntityEvent $event) : void{
		$player = $event->getEntity();
		if($player instanceof Player && ($session = PlayerManager::getSession($player)) !== null && ($kit = $session->getKitHolder()?->getKit()) !== null){
			$child = $event->getChild();
			$event->setAttackCooldown((int) ($kit->getKnockbackInfo()->getSpeed() / 2));
			if($child instanceof Arrow){
				$event->setModifier(0, EntityDamageEvent::MODIFIER_ARMOR);
				$event->setModifier(0, EntityDamageEvent::MODIFIER_ARMOR_ENCHANTMENTS);
				if($player->getId() === $event->getDamager()?->getId()){
					if(in_array($kit->getName(), ["Bridge", "Attacker", "Defender"], true)){
						$event->cancel();
						return;
					}
					$event->uncancel();
					$player->setMotion($player->getDirectionVector());
				}elseif($kit->getName() === "OITC"){
					$event->cancel();
					$session->onDeath();
				}
			}elseif($child instanceof Snowball){
				if($kit->getName() === "Spleef"){
					$event->uncancel();
				}
			}
		}
	}

	public function onShootBow(EntityShootBowEvent $event) : void{
		$player = $event->getEntity();
		if($player instanceof Player && ($session = PlayerManager::getSession($player)) !== null && !$player->isImmobile() && $session->canArrow()){
			if(($kit = $session->getKitHolder()?->getKit()) !== null && in_array($kit->getName(), ["Bridge", "OITC"], true)){
				$session->setShootArrow(false);
			}
			$location = $player->getLocation();
			$world = $location->getWorld();
			$diff = $player->getItemUseDuration();
			$p = $diff / 20;
			$baseForce = min((($p ** 2) + $p * 2) / 3, 1);
			$arrow = new Arrow(Location::fromObject($player->getEyePos(), $world, ($location->yaw > 180 ? 360 : 0) - $location->yaw, -$location->pitch), $player, $baseForce >= 1, null, PracticeUtil::getViewersForPosition($player));
			/** @var Arrow $projectile */
			$projectile = $event->getProjectile();
			$arrow->setPunchKnockback($projectile->getPunchKnockback());
			$event->setProjectile($arrow);
			$session->getDuel()?->setReleaseBow($player, $event->getForce());
			return;
		}
		$event->cancel();
	}

	public function onToggleSneak(PlayerToggleSneakEvent $event) : void{
		if(PracticeCore::REPLAY){ // @phpstan-ignore-line
			PlayerManager::getSession($player = $event->getPlayer())?->getDuel()?->setSneaking($player, $event->isSneaking());
		}
	}

	public function onChat(PlayerChatEvent $event) : void{
		$event->cancel();
		$player = $event->getPlayer();
		$msg = TextFormat::clean($event->getMessage());
		LogMonitor::chatLog("{$player->getName()}: $msg");
		if(($session = PlayerManager::getSession($player)) !== null && $session->tryChat()){
			$session->setCanChat(false);
			$message = RankHandler::formatRanksForChat($player) . TextFormat::GRAY . " » " . TextFormat::WHITE . $msg;
			foreach(PlayerManager::getOnlinePlayers() as $p){
				$p->sendMessage($message);
			}
		}
	}

	public function onCommandPreprocess(PlayerCommandPreprocessEvent $event) : void{
		if(($session = PlayerManager::getSession($player = $event->getPlayer())) !== null && !$session->getDisguiseInfo()->isProcessing()){
			$message = $event->getMessage();
			$kitHolder = $session->getKitHolder();
			if($kitHolder?->isEditingKit() && $session->isInHub()){
				switch(strtolower($message)){
					case "confirm":
						$kitHolder->setFinishedEditingKit(false);
						ItemHandler::spawnHubItems($player);
						$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Your kit is saved");
						break;
					case "reset":
						$kitHolder->resetEditingKit();
						break;
					case "cancel":
						$kitHolder->setFinishedEditingKit(true);
						ItemHandler::spawnHubItems($player);
						$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Cancel editing kit");
						break;
					default:
						$player->sendMessage("\n\n");
						$player->sendMessage(TextFormat::YELLOW . "   You are now editing your inventory.\n");
						$player->sendMessage(TextFormat::WHITE . "   Type " . TextFormat::GREEN . "Confirm" . TextFormat::WHITE . " in chat to " . TextFormat::GREEN . "save" . TextFormat::WHITE . " the current edited");
						$player->sendMessage(TextFormat::WHITE . "   Type " . TextFormat::YELLOW . "Reset" . TextFormat::WHITE . " in chat to " . TextFormat::YELLOW . "reset" . TextFormat::WHITE . " the current edited");
						$player->sendMessage(TextFormat::WHITE . "   Type " . TextFormat::RED . "Cancel" . TextFormat::WHITE . " in chat to " . TextFormat::RED . "cancel" . TextFormat::WHITE . " the current edited");
						$player->sendMessage("\n\n");
						break;
				}
			}elseif($message[0] === "!" && $session->getRankInfo()?->hasHelperPermissions()){
				$message = RankHandler::formatStaffForChat($player) . TextFormat::WHITE . " » " . TextFormat::clean(substr($message, 1));
				foreach(PlayerManager::getOnlineStaffs() as $p){
					$p->sendMessage($message);
				}
			}else{
				return;
			}
		}
		$event->cancel();
	}

	public function onPlayerChangeSkin(PlayerChangeSkinEvent $event) : void{
		if(($session = PlayerManager::getSession($player = $event->getPlayer())) !== null && $session->trySkin()){
			$session->setChangeSkin(false);
			CosmeticManager::setStrippedSkin($player, $event->getNewSkin(), true);
			$player->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "Applying skin");
		}
		$event->cancel();
	}

	public function onEntityMotion(EntityMotionEvent $event) : void{
		$player = $event->getEntity();
		if($player instanceof Player){
			if(self::$cachedData[$name = $player->getName()]["initialKnockbackMotion"] ?? null){
				self::$cachedData[$name]["initialKnockbackMotion"] = false;
				self::$cachedData[$name]["shouldCancelKBMotion"] = true;
			}elseif(self::$cachedData[$name]["shouldCancelKBMotion"] ?? null){
				self::$cachedData[$name]["shouldCancelKBMotion"] = false;
				$event->cancel();
			}
		}
	}

	private function knockBack(Player $player, Entity $entity, KnockbackInfo $info) : void{
		$xzKb = $info->getHorizontalKb();
		$yKb = $info->getVerticalKb();
		if(!$player->isOnGround() && ($maxHeight = $info->getMaxHeight()) > 0){
			[$max, $min] = PracticeUtil::maxMin($player->getPosition()->getY(), $entity->getPosition()->getY());
			if($max - $min >= $maxHeight){
				$yKb *= 0.75;
				if($info->canRevert()){
					$yKb *= -1;
				}
			}
		}
		if(($session = PlayerManager::getSession($player)) !== null && $session->isAgroPearl()){
			$xzKb *= 0.85;
			$yKb *= 0.85;
			$session->setAgroPearl(false);
		}
		$x = $player->getPosition()->getX() - $entity->getPosition()->getX();
		$z = $player->getPosition()->getZ() - $entity->getPosition()->getZ();
		$f = sqrt($x * $x + $z * $z);
		if($f <= 0){
			return;
		}
		if(mt_rand() / mt_getrandmax() > $player->getAttributeMap()->get(Attribute::KNOCKBACK_RESISTANCE)?->getValue()){
			$f = 1 / $f;
			$motion = clone $player->getMotion();
			$motion->x /= 2;
			$motion->y /= 2;
			$motion->z /= 2;
			$motion->x += $x * $f * $xzKb;
			$motion->y += $yKb;
			$motion->z += $z * $f * $xzKb;
			if($motion->y > $yKb){
				$motion->y = $yKb;
			}
			self::$cachedData[$player->getName()]["initialKnockbackMotion"] = true;
			$player->setMotion($motion);
		}
	}
}
