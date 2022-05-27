<?php

declare(strict_types=1);

namespace zodiax\proxy;

use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\DebugInfoPacket;
use zodiax\misc\AbstractListener;
use zodiax\player\PlayerManager;
use function assert;
use function count;
use function explode;

class ProxyListener extends AbstractListener{

	public function onDataPacketReceive(DataPacketReceiveEvent $event) : void{
		$player = $event->getOrigin()->getPlayer();
		if(($session = PlayerManager::getSession($player)) !== null){
			$packet = $event->getPacket();
			switch($packet->pid()){
				case DebugInfoPacket::NETWORK_ID:
					assert($packet instanceof DebugInfoPacket);
					$data = explode(":", $packet->getData());
					if(count($data) > 2 && $data[0] === "waterdog" && $data[1] === "ping"){
						$session->updatePing((int) $data[2]);
					}
					break;
			}
		}
	}
}
