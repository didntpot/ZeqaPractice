<?php

declare(strict_types=1);

namespace zodiax\game\items;

use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use function array_merge;
use function str_contains;

class ItemHandler{

	const HUB_LOBBY = "hub.lobby";
	const HUB_FFA = "hub.ffa";
	const HUB_DUELS = "hub.duels";
	const HUB_BOT = "hub.bot";
	const HUB_EVENT = "hub.event";
	const HUB_TRAINING = "hub.training";
	const HUB_PARTY = "hub.party";
	const HUB_SPEC = "hub.spec";
	const HUB_SHOP = "hub.shop";
	const HUB_SETTINGS = "hub.settings";
	const HUB_LEAVE = "hub.leave";

	const CLUTCH_PLAY = "clutch.play";
	const CLUTCH_SETTINGS = "clutch.settings";

	const REDUCE_PLAY = "reduce.play";
	const REDUCE_SETTINGS = "reduce.settings";

	const BLOCKIN_PLAY = "blockin.play";
	const BLOCKIN_SETTINGS = "blockin.settings";

	const REPLAY_PLAY = "replay.play";
	const REPLAY_PAUSE = "replay.pause";
	const REPLAY_REWIND = "replay.rewind";
	const REPLAY_FORWARD = "replay.forward";
	const REPLAY_SETTINGS = "replay.settings";

	const PARTY_DUEL = "party.duel";
	const PARTY_INBOX = "party.inbox";
	const PARTY_SETTINGS = "party.settings";

	const SPEC_TELEPORTER = "spec.teleporter";

	/** @var array<string, PracticeItem> $items */
	private static array $items = [];
	/** @var string[] $hubKeys */
	private static array $hubKeys = [];
	/** @var string[] $replayKeys */
	private static array $replayKeys = [];
	/** @var string[] $partyKeys */
	private static array $partyKeys = [];

	public static function initialize() : void{
		self::registerItem(0, self::HUB_FFA, VanillaItems::DIAMOND_SWORD()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "» " . PracticeCore::COLOR . "Play " . TextFormat::WHITE . "FFA" . TextFormat::GRAY . " «"));
		self::registerItem(1, self::HUB_DUELS, VanillaItems::IRON_SWORD()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "» " . PracticeCore::COLOR . "Play " . TextFormat::WHITE . "Duels" . TextFormat::GRAY . " «"));
		self::registerItem(2, self::HUB_TRAINING, VanillaItems::GOLDEN_SWORD()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "» " . PracticeCore::COLOR . "Play " . TextFormat::WHITE . "Training" . TextFormat::GRAY . " «"));
		self::registerItem(3, self::HUB_EVENT, VanillaItems::NETHER_STAR()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "» " . PracticeCore::COLOR . "Play " . TextFormat::WHITE . "Event" . TextFormat::GRAY . " «"));
		self::registerItem(4, self::HUB_LOBBY, VanillaItems::NETHER_STAR()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "» " . PracticeCore::COLOR . "Region " . TextFormat::WHITE . "Selector" . TextFormat::GRAY . " «"));
		self::registerItem(5, self::HUB_PARTY, VanillaItems::TOTEM()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "» " . PracticeCore::COLOR . "Party " . TextFormat::WHITE . "Menu" . TextFormat::GRAY . " «"));
		self::registerItem(6, self::HUB_SPEC, VanillaItems::CLOCK()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "» " . PracticeCore::COLOR . "Spectate " . TextFormat::WHITE . "Games" . TextFormat::GRAY . " «"));
		self::registerItem(7, self::HUB_SHOP, VanillaItems::MINECART()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "» " . PracticeCore::COLOR . "Shop " . TextFormat::WHITE . "Menu" . TextFormat::GRAY . " «"));
		self::registerItem(8, self::HUB_SETTINGS, VanillaItems::COMPASS()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "» " . PracticeCore::COLOR . "Player " . TextFormat::WHITE . "Settings" . TextFormat::GRAY . " «"));
		self::registerItem(8, self::HUB_LEAVE, VanillaItems::RED_DYE()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "» " . TextFormat::RED . "Leave" . TextFormat::GRAY . " «"));

		self::registerItem(0, self::CLUTCH_PLAY, VanillaItems::LIME_DYE()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "» " . TextFormat::GREEN . "Start" . TextFormat::GRAY . " «"));
		self::registerItem(7, self::CLUTCH_SETTINGS, VanillaItems::COMPASS()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "» " . PracticeCore::COLOR . "Clutch " . TextFormat::WHITE . "Settings" . TextFormat::GRAY . " «"));

		self::registerItem(0, self::REDUCE_PLAY, VanillaItems::LIME_DYE()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "» " . TextFormat::GREEN . "Start" . TextFormat::GRAY . " «"));
		self::registerItem(7, self::REDUCE_SETTINGS, VanillaItems::COMPASS()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "» " . PracticeCore::COLOR . "Reduce " . TextFormat::WHITE . "Settings" . TextFormat::GRAY . " «"));

		self::registerItem(0, self::BLOCKIN_PLAY, VanillaItems::LIME_DYE()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "» " . TextFormat::GREEN . "Start" . TextFormat::GRAY . " «"));
		self::registerItem(7, self::BLOCKIN_SETTINGS, VanillaItems::COMPASS()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "» " . PracticeCore::COLOR . "Block-In " . TextFormat::WHITE . "Settings" . TextFormat::GRAY . " «"));

		self::registerItem(0, self::REPLAY_PLAY, VanillaItems::LIME_DYE()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "» " . TextFormat::GREEN . "Play" . TextFormat::GRAY . " «"));
		self::registerItem(0, self::REPLAY_PAUSE, VanillaItems::LIGHT_GRAY_DYE()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "» " . TextFormat::GRAY . "Pause" . TextFormat::GRAY . " «"));
		self::registerItem(3, self::REPLAY_REWIND, VanillaItems::CLOCK()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "» " . TextFormat::RED . "Rewind" . TextFormat::GRAY . " «"));
		self::registerItem(5, self::REPLAY_FORWARD, VanillaItems::CLOCK()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "» " . TextFormat::AQUA . "Forward" . TextFormat::GRAY . " «"));
		self::registerItem(4, self::REPLAY_SETTINGS, VanillaItems::COMPASS()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "» " . TextFormat::WHITE . "Settings" . TextFormat::GRAY . " «"));

		self::registerItem(0, self::PARTY_DUEL, VanillaItems::DIAMOND_SWORD()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "» " . PracticeCore::COLOR . "Party " . TextFormat::WHITE . "Duel" . TextFormat::GRAY . " «"));
		self::registerItem(1, self::PARTY_INBOX, VanillaItems::IRON_SWORD()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "» " . PracticeCore::COLOR . "Party " . TextFormat::WHITE . "Inbox" . TextFormat::GRAY . " «"));
		self::registerItem(7, self::PARTY_SETTINGS, VanillaItems::COMPASS()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "» " . PracticeCore::COLOR . "Party " . TextFormat::WHITE . "Settings" . TextFormat::GRAY . " «"));

		self::registerItem(0, self::SPEC_TELEPORTER, VanillaItems::COMPASS()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "» " . PracticeCore::COLOR . "Player " . TextFormat::WHITE . "Teleporter" . TextFormat::GRAY . " «"));
	}

	private static function registerItem(int $slot, string $localName, Item $item) : void{
		$item = new PracticeItem($item->setNamedTag($item->getNamedTag()->setString("PracticeItem", $localName)), $slot);
		self::$items[$localName] = $item;
		if(str_contains($localName, "hub.")){
			self::$hubKeys[] = $localName;
		}elseif(str_contains($localName, "replay.")){
			self::$replayKeys[] = $localName;
		}elseif(str_contains($localName, "party.")){
			self::$partyKeys[] = $localName;
		}
	}

	public static function spawnHubItems(Player $player) : void{
		$inv = $player->getInventory();
		if($inv !== null){
			PlayerManager::getSession($player)?->getExtensions()?->clearAll();
			if(PracticeCore::isLobby()){
				$item = self::$items[self::HUB_LOBBY];
				$inv->setItem($item->getSlot(), $item->getItem());
			}else{
				foreach(self::$hubKeys as $localName){
					if($localName === self::HUB_LEAVE || $localName === self::HUB_LOBBY){
						continue;
					}
					$item = self::$items[$localName];
					$inv->setItem($item->getSlot(), $item->getItem());
				}
			}
		}
	}

	public static function spawnPartyItems(Player $player) : void{
		$inv = $player->getInventory();
		if($inv !== null){
			$session = PlayerManager::getSession($player);
			if(($party = $session?->getParty()) !== null){
				$isOwner = $party->isOwner($player);
				$session->getExtensions()?->clearAll();
				$partyKeys = array_merge(self::$partyKeys, [self::HUB_LEAVE]);
				foreach($partyKeys as $localName){
					if(!$isOwner && $localName !== self::HUB_LEAVE){
						continue;
					}
					$item = self::$items[$localName];
					$inv->setItem($item->getSlot(), $item->getItem());
				}
			}
		}
	}

	public static function spawnClutchItems(Player $player) : void{
		$inv = $player->getInventory();
		if($inv !== null){
			$items = [self::CLUTCH_PLAY, self::CLUTCH_SETTINGS, self::HUB_LEAVE];
			foreach($items as $localName){
				$item = self::$items[$localName];
				$inv->setItem($item->getSlot(), $item->getItem());
			}
		}
	}

	public static function spawnReduceItems(Player $player) : void{
		$inv = $player->getInventory();
		if($inv !== null){
			$items = [self::REDUCE_PLAY, self::REDUCE_SETTINGS, self::HUB_LEAVE];
			foreach($items as $localName){
				$item = self::$items[$localName];
				$inv->setItem($item->getSlot(), $item->getItem());
			}
		}
	}

	public static function spawnBlockInItems(Player $player) : void{
		$inv = $player->getInventory();
		if($inv !== null){
			$items = PlayerManager::getSession($player)?->getBlockIn()?->isOwner($player) ? [self::BLOCKIN_PLAY, self::BLOCKIN_SETTINGS, self::HUB_LEAVE] : [self::HUB_LEAVE];
			foreach($items as $localName){
				$item = self::$items[$localName];
				$inv->setItem($item->getSlot(), $item->getItem());
			}
		}
	}

	public static function spawnReplayItems(Player $player) : void{
		$inv = $player->getInventory();
		if($inv !== null){
			PlayerManager::getSession($player)?->getExtensions()?->clearAll();
			$replayKeys = array_merge(self::$replayKeys, [self::HUB_LEAVE]);
			foreach($replayKeys as $localName){
				if($localName === self::REPLAY_PLAY){
					continue;
				}
				$item = self::$items[$localName];
				$inv->setItem($item->getSlot(), $item->getItem());
			}
		}
	}

	public static function giveSpectatorItem(Player $player) : void{
		$inv = $player->getInventory();
		if($inv !== null){
			PlayerManager::getSession($player)?->getExtensions()?->clearAll();
			$items = [self::SPEC_TELEPORTER, self::HUB_LEAVE];
			foreach($items as $localName){
				$item = self::$items[$localName];
				$inv->setItem($item->getSlot(), $item->getItem());
			}
		}
	}

	public static function givePlayItem(Player $player) : void{
		$inv = $player->getInventory();
		if($inv !== null){
			$item = self::$items[self::REPLAY_PLAY];
			$inv->setItem($item->getSlot(), $item->getItem());
		}
	}

	public static function givePauseItem(Player $player) : void{
		$inv = $player->getInventory();
		if($inv !== null){
			$item = self::$items[self::REPLAY_PAUSE];
			$inv->setItem($item->getSlot(), $item->getItem());
		}
	}

	public static function giveLeaveItem(Player $player) : void{
		$inv = $player->getInventory();
		if($inv !== null){
			PlayerManager::getSession($player)?->getExtensions()?->clearAll();
			$item = self::$items[self::HUB_LEAVE];
			$inv->setItem($item->getSlot(), $item->getItem());
		}
	}
}