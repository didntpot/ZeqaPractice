<?php

declare(strict_types=1);

namespace zodiax\game\entity;

use InvalidArgumentException;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\MetadataProperty;
use pocketmine\player\Player;
use pocketmine\Server;
use function array_search;
use function arsort;
use function atan2;
use function floor;
use function in_array;
use function is_array;
use function sqrt;

class CustomEntity{

	public const NETWORK_ID = -1;

	public string $name;
	public int $id;
	public Location $location;
	public float $headYaw;
	public float $scale = 1.0;
	public EntitySizeInfo $size;
	public Server $server;
	public EntityMetadataCollection $networkProperties;
	/** @var Player[] */
	public array $hasSpawned = [];

	public function __construct(Location $location, float $headYaw, string $name, EntitySizeInfo $size){
		$this->name = $name;
		$this->id = Entity::nextRuntimeId();
		$this->location = $location;
		$this->headYaw = $headYaw;
		$this->size = $size;
		$this->server = Server::getInstance();
		$this->networkProperties = new EntityMetadataCollection();
	}

	public function getRealName() : string{
		return $this->name;
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
		$pk = MoveActorAbsolutePacket::create($this->getId(), $this->location, $this->location->pitch, $this->location->yaw, $this->headYaw = $this->location->yaw, 0);
		foreach($this->getViewers() as $player){
			$player->getNetworkSession()->sendDataPacket($pk);
		}
	}

	public function getClosestPlayer() : ?Player{
		$arr = [];
		foreach($this->location->getWorld()->getPlayers() as $player){
			$arr[(int) floor($this->location->distance($player->getPosition()))] = $player;
		}
		arsort($arr);
		for($i = 0; $i <= 7; $i++){
			if(isset($arr[$i])){
				return $arr[$i];
			}
		}
		return null;
	}

	public function getLocation() : Location{
		return $this->location;
	}

	public function getId() : int{
		return $this->id;
	}

	public function getServer() : Server{
		return $this->server;
	}

	/**
	 * @param Player[]|Player         $player
	 * @param MetadataProperty[]|null $data
	 */
	public function sendData(array|Player $player, ?array $data = null) : void{
		if(!is_array($player)){
			$player = [$player];
		}
		$pk = SetActorDataPacket::create($this->getId(), $data ?? $this->getSyncedNetworkData(false), 0);
		foreach($player as $p){
			$p->getNetworkSession()->sendDataPacket(clone $pk);
		}
	}

	public function syncNetworkData() : void{
		$this->networkProperties->setByte(EntityMetadataProperties::ALWAYS_SHOW_NAMETAG, 1);
		$this->networkProperties->setFloat(EntityMetadataProperties::BOUNDING_BOX_HEIGHT, $this->size->getHeight() / $this->scale);
		$this->networkProperties->setFloat(EntityMetadataProperties::BOUNDING_BOX_WIDTH, $this->size->getWidth() / $this->scale);
		$this->networkProperties->setFloat(EntityMetadataProperties::SCALE, $this->scale);
		$this->networkProperties->setLong(EntityMetadataProperties::LEAD_HOLDER_EID, -1);
		$this->networkProperties->setLong(EntityMetadataProperties::OWNER_EID, $this->ownerId ?? -1);
		$this->networkProperties->setLong(EntityMetadataProperties::TARGET_EID, $this->targetId ?? 0);
		$this->networkProperties->setString(EntityMetadataProperties::NAMETAG, $this->name);

		$this->networkProperties->setGenericFlag(EntityMetadataFlags::AFFECTED_BY_GRAVITY, true);
		$this->networkProperties->setGenericFlag(EntityMetadataFlags::CAN_SHOW_NAMETAG, true);
		$this->networkProperties->setGenericFlag(EntityMetadataFlags::HAS_COLLISION, true);
		$this->networkProperties->setGenericFlag(EntityMetadataFlags::IMMOBILE, false);
		$this->networkProperties->setGenericFlag(EntityMetadataFlags::INVISIBLE, false);
		$this->networkProperties->setGenericFlag(EntityMetadataFlags::ONFIRE, false);
		$this->networkProperties->setGenericFlag(EntityMetadataFlags::SNEAKING, false);
		$this->networkProperties->setGenericFlag(EntityMetadataFlags::WALLCLIMBING, false);
	}

	/**
	 * @return MetadataProperty[]
	 */
	final public function getSyncedNetworkData(bool $dirtyOnly) : array{
		$this->syncNetworkData();
		return $dirtyOnly ? $this->networkProperties->getDirty() : $this->networkProperties->getAll();
	}

	public function spawnTo(Player $player) : void{
		if(in_array($player, $this->hasSpawned, true)){
			return;
		}
		$player->getNetworkSession()->sendDataPacket(AddActorPacket::create($this->getId(), $this->getId(), 'custom:entity', $this->location->asVector3(), null, $this->location->pitch, $this->location->yaw, $this->headYaw, [], $this->getSyncedNetworkData(false), []));
		$this->hasSpawned[] = $player;
	}

	public function despawnFrom(Player $player) : void{
		$key = array_search($player, $this->hasSpawned, true);
		if($key !== false){
			$player->getNetworkSession()->sendDataPacket(RemoveActorPacket::create($this->id));
			unset($this->hasSpawned[$key]);
		}
	}

	/**
	 * @return Player[]
	 */
	public function getViewers() : array{
		return $this->hasSpawned;
	}

	public function getScale() : float{
		return $this->scale;
	}

	public function setScale(float $value) : void{
		if($value <= 0){
			throw new InvalidArgumentException("Scale must be greater than 0");
		}
		if($this->scale !== $value){
			$this->scale = $value;
			$this->setSize($this->size->scale($value));
		}
	}

	public function getSize() : EntitySizeInfo{
		return $this->size;
	}

	public function setSize(EntitySizeInfo $size) : void{
		$this->size = $size;
	}
}