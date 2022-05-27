<?php

declare(strict_types=1);

namespace zodiax\player\misc\cosmetic\gacha;

use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\math\Facing;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\ChestOpenSound;
use zodiax\misc\AbstractRepeatingTask;
use zodiax\player\misc\cosmetic\CosmeticManager;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\utils\CustomParticle;

class GachaTask extends AbstractRepeatingTask{

	private Player $player;
	private Gacha $gacha;

	public function __construct(Player $player, Gacha $gacha){
		parent::__construct(PracticeUtil::secondsToTicks(1));
		$this->player = $player;
		$this->gacha = $gacha;
	}

	protected function onUpdate(int $tickDifference) : void{
		if($this->player->isOnline() && ($session = PlayerManager::getSession($this->player)) !== null){
			switch($this->getCurrentTick()){
				case 0:
					$this->player->getArmorInventory()->setHelmet(VanillaBlocks::CARVED_PUMPKIN()->asItem());
					$pos = $this->player->getPosition();
					Server::getInstance()->broadcastPackets([$this->player], [PlaySoundPacket::create("mob.zombie.wood", $pos->getX(), $pos->getY(), $pos->getZ(), 1, 1)]);
					$this->player->sendTitle("");
					break;
				case 20:
					$this->player->broadcastSound(new ChestOpenSound(), [$this->player]);
					$this->player->sendTitle("");
					break;
				case 40:
					$this->player->getArmorInventory()->setHelmet(VanillaItems::AIR());
					if(($item = GachaHandler::randomItemFromGacha($this->gacha->getId())) !== null){
						$session->getItemInfo()->alterCosmeticItem($this->player, $item, false, true, true, !$session->getSettingsInfo()->isAutoRecycle());
						$totemParticle = new CustomParticle(-1, "minecraft:totem_particle");
						$pos = $this->player->getPosition();
						foreach(Facing::ALL as $side){
							$this->player->getWorld()->addParticle($pos->getSide($side), $totemParticle, [$this->player]);
						}
						$this->player->sendTitle("", $item->getDisplayName(true));
						if($item->getRarity() === CosmeticManager::SR || $item->getRarity() === CosmeticManager::UR){
							$type = match ($item->getType()) {
								CosmeticManager::CAPE => "cape",
								CosmeticManager::ARTIFACT => "artifact",
								CosmeticManager::PROJECTILE => "projectile",
								CosmeticManager::KILLPHRASE => "killphrase"
							};
							$announce = PracticeCore::PREFIX . TextFormat::GREEN . $this->player->getDisplayName() . TextFormat::GRAY . " has obtained an extremely rare $type " . $item->getDisplayName(true);
							foreach(PlayerManager::getOnlinePlayers() as $p){
								$p->sendMessage($announce);
							}
						}
					}
					$this->getHandler()?->cancel();
					break;
			}
		}
	}
}