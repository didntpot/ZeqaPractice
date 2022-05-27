<?php

declare(strict_types=1);

namespace zodiax\game\entity;

use JsonException;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\SkinAdapterSingleton;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\AnimateEntityPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use ReflectionClass;
use Webmozart\PathUtil\Path;
use zodiax\game\npc\NPCManager;
use zodiax\player\misc\cosmetic\CosmeticManager;
use zodiax\PracticeCore;
use function atan2;
use function file_get_contents;
use function in_array;
use function sqrt;

class NPCHuman extends CustomEntity{

	public const NETWORK_ID = 0;

	public UuidInterface $uuid;
	public string $format;
	public string $animation;
	public Skin $skin;

	/**
	 * @throws JsonException
	 */
	public function __construct(Location $location, float $headYaw, string $name, string $format, string $skin, float $scale, string $animation){
		parent::__construct($location, $headYaw, $name, new EntitySizeInfo(1.8, 0.6, 1.62));
		$this->uuid = Uuid::uuid4();
		$this->format = $format;
		$this->animation = $animation;
		/** @var string $geometryData */
		$geometryData = file_get_contents(Path::join(PracticeCore::getResourcesFolder(), "npc", "$skin.json"));
		$this->skin = new Skin($name, CosmeticManager::getSkinDataFromPNG(Path::join(PracticeCore::getResourcesFolder(), "npc", "$skin.png")), "", "geometry.humanoid.custom", $geometryData);
		$this->setScale($scale);
	}

	/**
	 * @throws JsonException
	 */
	public function editData(Location $location, float $headYaw, string $format, string $skin, float $scale, string $animation) : void{
		$hasSpawned = $this->hasSpawned;
		foreach($this->hasSpawned as $player){
			$this->despawnFrom($player);
		}
		$this->location = $location;
		$this->headYaw = $headYaw;
		$this->format = $format;
		$this->animation = $animation;
		/** @var string $geometryData */
		$geometryData = file_get_contents(Path::join(PracticeCore::getResourcesFolder(), "npc", "$skin.json"));
		$this->skin = new Skin($this->name, CosmeticManager::getSkinDataFromPNG(Path::join(PracticeCore::getResourcesFolder(), "npc", "$skin.png")), "", "geometry.humanoid.custom", $geometryData);
		$this->setScale($scale);
		foreach($hasSpawned as $player){
			$this->spawnTo($player);
		}
		NPCManager::editNPC($location, $headYaw, $this->getRealName(), $format, $skin, $scale, $animation);
	}

	public function getName() : string{
		return (new ReflectionClass($this))->getShortName();
	}

	public function getFormatName() : string{
		return $this->format;
	}

	public function spawnTo(Player $player) : void{
		if(in_array($player, $this->hasSpawned, true)){
			return;
		}
		$player->getNetworkSession()->sendDataPacket(PlayerListPacket::add([PlayerListEntry::createAdditionEntry($this->uuid, $this->id, $this->getName(), SkinAdapterSingleton::get()->toSkinData($this->skin))]));
		$player->getNetworkSession()->sendDataPacket(AddPlayerPacket::create($this->uuid, $this->format, $this->getId(), $this->getId(), "", $this->location->asVector3(), null, $this->location->pitch, $this->location->yaw, $this->headYaw, ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet(ProtocolInfo::CURRENT_PROTOCOL, VanillaItems::AIR())), GameMode::SURVIVAL, $this->getSyncedNetworkData(false), AdventureSettingsPacket::create(0, 0, 0, 0, 0, $this->getId()), [], "", DeviceOS::UNKNOWN));
		$this->sendData($player, [EntityMetadataProperties::NAMETAG => new StringMetadataProperty($this->format)]);
		$player->getNetworkSession()->sendDataPacket(PlayerListPacket::remove([PlayerListEntry::createRemovalEntry($this->uuid)]));
		if($this->animation !== ""){
			PracticeCore::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player){
				if($player->isOnline()){
					$player->getNetworkSession()->sendDataPacket(AnimateEntityPacket::create($this->animation, "", "", 0, "", 0, [$this->getId()]));
				}
			}), 40);
		}
		$this->hasSpawned[] = $player;
	}

	public function despawnFrom(Player $player) : void{
		parent::despawnFrom($player);
		$player->getNetworkSession()->sendDataPacket(PlayerListPacket::remove([PlayerListEntry::createAdditionEntry($this->uuid, $this->id, $this->getName(), SkinAdapterSingleton::get()->toSkinData($this->skin))]));
	}

	public function lookAt(Vector3 $target) : void{
		$horizontal = sqrt(($target->x - $this->location->x) ** 2 + ($target->z - $this->location->z) ** 2);
		$vertical = $target->y - $this->location->y;
		$this->location->pitch = -atan2($vertical, $horizontal) / M_PI * 180; //negative is up, positive is down
		$xDist = $target->x - $this->location->x;
		$zDist = $target->z - $this->location->z;
		$this->location->yaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
		if($this->location->yaw < 0){
			$this->location->yaw += 360.0;
		}
		$movePacket = MovePlayerPacket::create($this->getId(), $this->location->add(0, 1.62, 0), $this->location->pitch, $this->location->yaw, $this->headYaw = $this->location->yaw, MovePlayerPacket::MODE_NORMAL, false, 0, 0, 0, 0);
		foreach($this->getViewers() as $player){
			$player->getNetworkSession()->sendDataPacket($movePacket);
		}
	}
}