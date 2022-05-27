<?php

declare(strict_types=1);

namespace zodiax\game\entity;

use JsonException;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\convert\SkinAdapterSingleton;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use ReflectionClass;
use Webmozart\PathUtil\Path;
use zodiax\player\misc\cosmetic\CosmeticManager;
use zodiax\PracticeCore;
use function file_get_contents;
use function in_array;

class BlockInEntity extends CustomEntity{

	public const NETWORK_ID = 0;

	public UuidInterface $uuid;
	public Skin $skin;

	/**
	 * @throws JsonException
	 */
	public function __construct(Location $location, float $headYaw, string $name){
		parent::__construct($location, $headYaw, $name, new EntitySizeInfo(0.92, 0.73, 0));
		$this->uuid = Uuid::uuid4();
		/** @var string $geometryData */
		$geometryData = file_get_contents(Path::join(PracticeCore::getResourcesFolder(), "npc", "block.json"));
		$this->skin = new Skin($name, CosmeticManager::getSkinDataFromPNG(Path::join(PracticeCore::getResourcesFolder(), "npc", "block.png")), "", "geometry.humanoid.custom", $geometryData);
		$this->setScale(1);
	}

	public function getName() : string{
		return (new ReflectionClass($this))->getShortName();
	}

	public function spawnTo(Player $player) : void{
		if(in_array($player, $this->hasSpawned, true)){
			return;
		}
		$player->getNetworkSession()->sendDataPacket(PlayerListPacket::add([PlayerListEntry::createAdditionEntry($this->uuid, $this->id, $this->getName(), SkinAdapterSingleton::get()->toSkinData($this->skin))]));
		$player->getNetworkSession()->sendDataPacket(AddPlayerPacket::create($this->uuid, TextFormat::YELLOW . "Zeqa.net", $this->getId(), $this->getId(), "", $this->location->asVector3(), null, $this->location->pitch, $this->location->yaw, $this->headYaw, ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet(ProtocolInfo::CURRENT_PROTOCOL, VanillaItems::AIR())), GameMode::SURVIVAL, $this->getSyncedNetworkData(false), AdventureSettingsPacket::create(0, 0, 0, 0, 0, $this->getId()), [], "", DeviceOS::UNKNOWN));
		$this->sendData($player, [EntityMetadataProperties::NAMETAG => new StringMetadataProperty(TextFormat::YELLOW . "Zeqa.net")]);
		$player->getNetworkSession()->sendDataPacket(PlayerListPacket::remove([PlayerListEntry::createRemovalEntry($this->uuid)]));
		$this->hasSpawned[] = $player;
	}

	public function despawnFrom(Player $player) : void{
		parent::despawnFrom($player);
		$player->getNetworkSession()->sendDataPacket(PlayerListPacket::remove([PlayerListEntry::createAdditionEntry($this->uuid, $this->id, $this->getName(), SkinAdapterSingleton::get()->toSkinData($this->skin))]));
	}

	public function despawnFromAll() : void{
		foreach($this->hasSpawned as $player){
			if($player->isOnline()){
				$this->despawnFrom($player);
			}
		}
	}
}