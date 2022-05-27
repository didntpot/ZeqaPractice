<?php

declare(strict_types=1);

namespace zodiax\player\info;

use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\discord\DiscordUtil;
use zodiax\player\misc\PlayerTrait;
use zodiax\player\misc\SettingsHandler;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use function array_filter;
use function array_pop;
use function array_unshift;
use function count;
use function max;
use function microtime;
use function str_replace;

class ClicksInfo{
	use PlayerTrait;

	private array $cps;
	private float $flag;
	private float $lastTime;

	public function __construct(Player $player){
		$this->player = $player->getName();
		$this->cps = [];
		$this->flag = 0;
		$this->lastTime = 0;
	}

	public function addClick() : void{
		if(($session = $this->getSession()) === null){
			return;
		}
		array_unshift($this->cps, microtime(true));
		if(count($this->cps) > 50){
			array_pop($this->cps);
		}
		$cps = $this->getCps();
		$player = $session->getPlayer();
		if($session->getSettingsInfo()->isCpsPopup()){
			$player->sendTip(PracticeCore::COLOR . "CPS " . TextFormat::WHITE . $cps);
		}
		if($cps >= 18){
			$player->sendTitle(TextFormat::YELLOW . "Double Clicking is", TextFormat::BOLD . TextFormat::RED . "PROHIBITED" . TextFormat::RESET . TextFormat::YELLOW . ", Use DC Prevent (" . TextFormat::WHITE . "discord.gg/zeqa" . TextFormat::YELLOW . ")", 5, 20, 5);
			if($cps >= 20){
				if(count($staffs = PlayerManager::getOnlineStaffs()) > 0){
					$currentTime = microtime(true);
					if($currentTime - $this->lastTime >= 1){
						$this->lastTime = $currentTime;
						$clientInfo = $session->getClientInfo();
						$msg = TextFormat::RED . TextFormat::BOLD . "ALERT" . TextFormat::RESET . TextFormat::RED . " " . $player->getName() . TextFormat::YELLOW . " (" . TextFormat::RED . $clientInfo->getDeviceOS(true) . TextFormat::YELLOW . "|" . TextFormat::RED . $clientInfo->getInputAtLogin(true) . TextFormat::YELLOW . ") is clicking " . TextFormat::RED . $cps . TextFormat::YELLOW . " cps (" . TextFormat::RED . $this->getSession()?->getPing() . TextFormat::YELLOW . " ms)";
						foreach($staffs as $p){
							$p->sendMessage($msg);
						}
					}
					return;
				}
				if(++$this->flag > 50){
					$theReason = TextFormat::BOLD . TextFormat::RED . "Network Kick" . "\n\n" . TextFormat::RESET;
					$theReason .= TextFormat::RED . "Reason " . TextFormat::DARK_GRAY . "» " . TextFormat::GRAY . "Unfair Advantage (High Cps)\n";
					$theReason .= TextFormat::RED . "Kicked by " . TextFormat::DARK_GRAY . "» " . TextFormat::GRAY . "Zeqa";
					$clientInfo = $session->getClientInfo();
					$msg = TextFormat::RED . TextFormat::BOLD . "ALERT" . TextFormat::RESET . TextFormat::RED . " " . ($name = $player->getName()) . TextFormat::YELLOW . " (" . TextFormat::RED . $clientInfo->getDeviceOS(true) . TextFormat::YELLOW . "|" . TextFormat::RED . $clientInfo->getInputAtLogin(true) . TextFormat::YELLOW . ") got kicked for clicking " . TextFormat::RED . $cps . TextFormat::YELLOW . " cps (" . TextFormat::RED . ($ping = $this->getSession()?->getPing()) . TextFormat::YELLOW . " ms)";
					DiscordUtil::sendBan("**Kicked (" . PracticeCore::getRegionInfo() . ")**\nPlayer: $name ({$clientInfo->getDeviceOS(true)}|{$clientInfo->getInputAtLogin(true)})\nReason: High Cps ($cps cps, $ping ms)\nStaff: Zeqa", true, 0xFF0000, "http://api.zeqa.net/api/players/avatars/" . str_replace(" ", "%20", $name));
					$player->kick($theReason);
					foreach(PlayerManager::getOnlineStaffs() as $p){
						$p->sendMessage($msg);
					}
				}
			}else{
				$this->flag = max($this->flag - 0.04, 0);
			}
		}else{
			$this->flag = max($this->flag - 0.04, 0);
		}
	}

	public function getCps() : int{
		if(empty($this->cps)){
			return 0;
		}
		$currentTime = microtime(true);
		return count(array_filter($this->cps, function(float $time) use ($currentTime) : bool{
			return ($currentTime - $time) <= 1;
		}));
	}

	public function update() : void{
		if(($session = $this->getSession()) !== null){
			$cps = $this->getCps();
			$session->setNoDefaultTag();
			$session->getPlayer()->sendData(SettingsHandler::getPlayersFromType(SettingsHandler::CPS_PING), [EntityMetadataProperties::SCORE_TAG => new StringMetadataProperty(PracticeCore::COLOR . $cps . TextFormat::WHITE . " CPS" . TextFormat::GRAY . " | " . PracticeCore::COLOR . $session->getPing() . TextFormat::WHITE . " MS")]);
			$session->getPlayer()->sendData(SettingsHandler::getPlayersFromType(SettingsHandler::CPS), [EntityMetadataProperties::SCORE_TAG => new StringMetadataProperty(PracticeCore::COLOR . $cps . TextFormat::WHITE . " CPS")]);
		}
	}
}
