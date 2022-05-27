<?php

declare(strict_types=1);

namespace zodiax\player\info\disguise;

use libasynCurl\Curl;
use pocketmine\network\mcpe\convert\SkinAdapterSingleton;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\player\Player;
use pocketmine\utils\InternetRequestResult;
use pocketmine\utils\TextFormat;
use zodiax\player\misc\PlayerTrait;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use function is_array;
use function json_decode;
use function strlen;

class DisguiseInfo{
	use PlayerTrait;

	private PlayerVisualData $disguisedData;
	private PlayerVisualData $originalData;
	private bool $processing = false;

	public function __construct(Player $player){
		$this->player = $player->getName();
		$this->originalData = new PlayerVisualData($player->getDisplayName());
		$this->disguisedData = new PlayerVisualData($player->getDisplayName());
	}

	private function applyVisualData(PlayerVisualData $data) : void{
		$this->getPlayer()->setDisplayName($data->getDisplayName());
		$this->getSession()->updateNameTag();
	}

	public function init(array $data) : void{
		$session = $this->getSession();
		$player = $this->getPlayer();
		$msg = "";
		if(strlen($disguised = $data["disguise"] ?? "") <= 0){
			$msg = TextFormat::DARK_GRAY . "[" . TextFormat::GREEN . "+" . TextFormat::DARK_GRAY . "]" . TextFormat::GREEN . " {$player->getDisplayName()}";
			$session->updateNameTag();
		}else{
			if(PlayerManager::getPlayerExact($disguised) !== null || PlayerManager::getPlayerExact($disguised, true) !== null || PlayerManager::getPlayerByPrefix($disguised) !== null){
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "$disguised is currently online, your disguise has now been reset");
				$msg = TextFormat::DARK_GRAY . "[" . TextFormat::GREEN . "+" . TextFormat::DARK_GRAY . "]" . TextFormat::GREEN . " {$player->getDisplayName()}";
				$session->updateNameTag();
			}else{
				$this->setDisguised($disguised, true);
			}
		}
		if($msg !== "" && !$session->getSettingsInfo()->isSilentStaff()){
			foreach(PlayerManager::getOnlinePlayers() as $online){
				$online->sendMessage($msg);
			}
		}
	}

	public function isDisguised() : bool{
		return $this->originalData->getDisplayName() !== $this->disguisedData->getDisplayName();
	}

	public function isProcessing() : bool{
		return $this->processing;
	}

	public function setDisguised(string $newname, bool $join = false) : void{
		if($newname !== $this->originalData->getDisplayName()){
			$player = $this->getPlayer();
			if($this->processing){
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "Please wait till the previous process done");
			}
			$player->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "Checking if " . TextFormat::YELLOW . $newname . TextFormat::GRAY . " is able to disguise as");
			$this->processing = true;
			Curl::getRequest("https://xbl.io/api/v2/friends/search?gt=$newname", 10, ["User-Agent: request", "X-Authorization: " . "00og4g0wgkkwcggokw84cokgc40w8o84000"], function(?InternetRequestResult $altsResult) use ($newname, $join) : void{
				$this->processing = false;
				if($altsResult !== null){
					if(is_array($altsResponse = json_decode($altsResult->getBody(), true)) && isset($altsResponse["profileUsers"])){
						if(($session = $this->getSession()) !== null){
							$player = $session->getPlayer();
							$player->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "Unable to disguise as " . TextFormat::RED . $newname . TextFormat::GRAY . " due to impersonation reason");
							if($join){
								$session->updateNameTag();
								if(!$session->getSettingsInfo()->isSilentStaff()){
									$msg = TextFormat::DARK_GRAY . "[" . TextFormat::GREEN . "+" . TextFormat::DARK_GRAY . "]" . TextFormat::GREEN . " {$player->getDisplayName()}";
									foreach(PlayerManager::getOnlinePlayers() as $online){
										$online->sendMessage($msg);
									}
								}
							}
						}
						return;
					}
				}
				if(($session = $this->getSession()) !== null){
					$player = $session->getPlayer();
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "You are now disguised as " . TextFormat::GREEN . $newname);
					$this->disguisedData->setDisplayName($newname);
					PlayerManager::changeDisplayName($this->originalData->getDisplayName(), $this->disguisedData->getDisplayName());
					$this->applyVisualData($this->disguisedData);
					$remove = PlayerListPacket::remove([PlayerListEntry::createRemovalEntry($player->getUniqueId())]);
					$add = PlayerListPacket::add([PlayerListEntry::createAdditionEntry($player->getUniqueId(), $player->getId(), $player->getDisplayName(), SkinAdapterSingleton::get()->toSkinData($player->getSkin()), $player->getXuid())]);
					foreach(PlayerManager::getOnlinePlayers() as $p){
						$p->getNetworkSession()->sendDataPacket($remove);
						$p->getNetworkSession()->sendDataPacket($add);
					}
					if($join){
						$session->updateNameTag();
						if(!$session->getSettingsInfo()->isSilentStaff()){
							$msg = TextFormat::DARK_GRAY . "[" . TextFormat::GREEN . "+" . TextFormat::DARK_GRAY . "]" . TextFormat::GREEN . " {$player->getDisplayName()}";
							foreach(PlayerManager::getOnlinePlayers() as $online){
								$online->sendMessage($msg);
							}
						}
					}
				}
			});
		}else{
			if(($player = $this->getPlayer()) !== null){
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "Your disguise has been removed");
				PlayerManager::changeDisplayName($this->disguisedData->getDisplayName(), $this->originalData->getDisplayName());
				$this->disguisedData->setDisplayName($newname);
				$this->applyVisualData($this->originalData);
				$remove = PlayerListPacket::remove([PlayerListEntry::createRemovalEntry($player->getUniqueId())]);
				$add = PlayerListPacket::add([PlayerListEntry::createAdditionEntry($player->getUniqueId(), $player->getId(), $player->getDisplayName(), SkinAdapterSingleton::get()->toSkinData($player->getSkin()), $player->getXuid())]);
				foreach(PlayerManager::getOnlinePlayers() as $p){
					$p->getNetworkSession()->sendDataPacket($remove);
					$p->getNetworkSession()->sendDataPacket($add);
				}
			}
		}
	}

	public function getDisguiseData() : PlayerVisualData{
		return $this->disguisedData;
	}
}