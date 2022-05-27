<?php

declare(strict_types=1);

namespace zodiax\game\inventories\menus\inventory;

use pocketmine\block\tile\Chest;
use pocketmine\block\tile\Nameable;
use pocketmine\block\tile\Tile;
use pocketmine\block\VanillaBlocks;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;
use pocketmine\world\Position;
use zodiax\game\inventories\InventoryTask;
use zodiax\game\inventories\menus\BaseMenu;

class DoubleChestInv extends PracticeBaseInv{

	const CHEST_SIZE = 54;

	public function __construct(BaseMenu $menu, Position $position){
		parent::__construct($menu, self::CHEST_SIZE, $position);
	}

	public function sendPrivateInv(Player $player) : void{
		$pos = $this->holder->floor()->add(0, self::HEIGHT_ABOVE, 0);
		$player->getNetworkSession()->sendDataPacket(UpdateBlockPacket::create(BlockPosition::fromVector3($pos), RuntimeBlockMapping::getInstance()->toRuntimeId(VanillaBlocks::CHEST()->getFullId(), $player->getNetworkSession()->getProtocolId()), UpdateBlockPacket::FLAG_NETWORK, UpdateBlockPacket::DATA_LAYER_NORMAL)); // @phpstan-ignore-line
		$player->getNetworkSession()->sendDataPacket(UpdateBlockPacket::create(BlockPosition::fromVector3($pos->add(1, 0, 0)), RuntimeBlockMapping::getInstance()->toRuntimeId(VanillaBlocks::CHEST()->getFullId(), $player->getNetworkSession()->getProtocolId()), UpdateBlockPacket::FLAG_NETWORK, UpdateBlockPacket::DATA_LAYER_NORMAL)); // @phpstan-ignore-line
		$player->getNetworkSession()->sendDataPacket(BlockActorDataPacket::create(BlockPosition::fromVector3($pos), new CacheableNbt($pair = CompoundTag::create()->setString(Nameable::TAG_CUSTOM_NAME, $this->menu->getName())->setString(Tile::TAG_ID, "Chest")->setInt(Chest::TAG_PAIRX, $pos->x)->setInt(Chest::TAG_PAIRZ, $pos->z)))); // @phpstan-ignore-line
		$player->getNetworkSession()->sendDataPacket(BlockActorDataPacket::create(BlockPosition::fromVector3($pos->add(1, 0, 0)), new CacheableNbt($pair->setInt(Chest::TAG_PAIRX, $pos->x + 1)))); // @phpstan-ignore-line
		new InventoryTask($player, $this);
	}

	public function sendPublicInv(Player $player) : void{
		$pos = $this->holder->floor()->add(0, self::HEIGHT_ABOVE, 0);
		$player->getNetworkSession()->sendDataPacket(UpdateBlockPacket::create(BlockPosition::fromVector3($pos), RuntimeBlockMapping::getInstance()->toRuntimeId($this->holder->getWorld()->getBlock($pos)->getFullId(), $player->getNetworkSession()->getProtocolId()), UpdateBlockPacket::FLAG_NETWORK, UpdateBlockPacket::DATA_LAYER_NORMAL)); // @phpstan-ignore-line
		$player->getNetworkSession()->sendDataPacket(UpdateBlockPacket::create(BlockPosition::fromVector3($pos->add(1, 0, 0)), RuntimeBlockMapping::getInstance()->toRuntimeId($this->holder->getWorld()->getBlock($pos->add(1, 0, 0))->getFullId(), $player->getNetworkSession()->getProtocolId()), UpdateBlockPacket::FLAG_NETWORK, UpdateBlockPacket::DATA_LAYER_NORMAL)); // @phpstan-ignore-line
	}

	public function getHolder() : Position{
		return $this->holder;
	}
}