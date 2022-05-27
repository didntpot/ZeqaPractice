<?php

declare(strict_types=1);

namespace zodiax\player\info;

use Closure;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\XpLevelUpSound;
use poggit\libasynql\SqlThread;
use zodiax\data\database\DatabaseManager;
use zodiax\data\log\LogMonitor;
use zodiax\player\misc\cosmetic\CosmeticManager;
use zodiax\player\misc\cosmetic\misc\CosmeticItem;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use function array_multisort;
use function array_search;
use function count;
use function explode;
use function implode;
use function in_array;
use function str_replace;

class ItemInfo{
	private array $potcolor = ["255", "0", "0"];
	private string $projectile = "0";
	private string $tag = "";
	private string $artifact = "0";
	private string $cape = "0";
	private string $killphrase = "0";
	private array $ownedProjectile = ["0"];
	private array $ownedTag = [""];
	private array $ownedArtifact = ["0"];
	private array $ownedCape = ["0"];
	private array $ownedKillphrase = ["0"];
	private bool $premium_bp = false;
	private int $free_bp_progress = 0;
	private int $premium_bp_progress = 0;

	public function init(array $data) : void{
		$this->potcolor = explode(",", $data["potcolor"] ?? "255,0,0");
		$this->tag = $data["tag"] ?? "";
		$this->ownedTag = explode(",", $data["ownedtag"] ?? "");
		$this->artifact = $data["artifact"] ?? "0";
		$this->ownedArtifact = explode(",", $data["ownedartifact"] ?? "0");
		$this->cape = $data["cape"] ?? "0";
		$this->ownedCape = explode(",", $data["ownedcape"] ?? "0");
		$this->projectile = $data["projectile"] ?? "0";
		$this->ownedProjectile = explode(",", $data["ownedprojectile"] ?? "0");
		$this->killphrase = $data["killphrase"] ?? "0";
		$this->ownedKillphrase = explode(",", $data["ownedkillphrase"] ?? "0");
		$this->premium_bp = (bool) ($data["premium_bp"] ?? false);
		$this->free_bp_progress = (int) ($data["free_bp_progress"] ?? 0);
		$this->premium_bp_progress = (int) ($data["premium_bp_progress"] ?? 0);
	}

	public function setPotColor(array $potcolor) : void{
		$this->potcolor = $potcolor;
	}

	public function setTag(string $tag = "") : void{
		$this->tag = $tag;
	}

	public function setArtifact(string $id = "0") : void{
		$this->artifact = $id;
	}

	public function setCape(string $id = "0") : void{
		$this->cape = $id;
	}

	public function setProjectile(string $id = "0") : void{
		$this->projectile = $id;
	}

	public function setKillPhrase(string $id = "0") : void{
		$this->killphrase = $id;
	}

	public function setPremiumBp(bool $premium_bp = false) : void{
		$this->premium_bp = $premium_bp;
	}

	public function setPremiumBpProgress(int $bp = 0) : void{
		$this->premium_bp_progress = $bp;
	}

	public function setFreeBpProgress(int $bp = 0) : void{
		$this->free_bp_progress = $bp;
	}

	public function setCosmetic(int $type, string $id) : void{
		switch($type){
			case CosmeticManager::ARTIFACT:
				$this->setArtifact($id);
				break;
			case CosmeticManager::CAPE:
				$this->setCape($id);
				break;
			case CosmeticManager::PROJECTILE:
				$this->setProjectile($id);
				break;
			case CosmeticManager::KILLPHRASE:
				$this->setKillPhrase($id);
				break;
		}
	}

	public function getPotColor() : array{
		return $this->potcolor;
	}

	public function getTag() : string{
		return $this->tag;
	}

	public function getArtifact(bool $asContent = false) : string{
		return $asContent ? CosmeticManager::getArtifactFromId($this->artifact)->getContent() : $this->artifact;
	}

	public function getCape(bool $asContent = false) : string{
		return $asContent ? CosmeticManager::getCapeFromId($this->cape)->getContent() : $this->cape;
	}

	public function getProjectile(bool $asContent = false) : string{
		return $asContent ? CosmeticManager::getProjectileFromId($this->projectile)->getContent() : $this->projectile;
	}

	public function getKillPhrase(bool $asContent = false) : string{
		return $asContent ? CosmeticManager::getKillPhraseFromId($this->killphrase)->getContent() : $this->killphrase;
	}

	public function getOwnedProjectile(bool $asInt = false) : int|array{
		return $asInt ? count($this->ownedProjectile) : $this->ownedProjectile;
	}

	public function getOwnedTag(bool $asInt = false) : int|array{
		return $asInt ? count($this->ownedTag) : $this->ownedTag;
	}

	public function getOwnedArtifact(bool $asInt = false) : int|array{
		return $asInt ? count($this->ownedArtifact) : $this->ownedArtifact;
	}

	public function getOwnedCape(bool $asInt = false) : int|array{
		return $asInt ? count($this->ownedCape) : $this->ownedCape;
	}

	public function getOwnedKillPhrase(bool $asInt = false) : int|array{
		return $asInt ? count($this->ownedKillphrase) : $this->ownedKillphrase;
	}

	public function getKillPhraseMessage(Player $killer, Player $victim, $optionalKiller = " ", $optionalVictim = " ") : string{
		return str_replace("{ov}", $optionalVictim, str_replace("{v}", TextFormat::RED . $victim->getDisplayName(), str_replace("{ok}", $optionalKiller, str_replace("{k}", TextFormat::GREEN . $killer->getDisplayName(), $this->getKillPhrase(true)))));
	}

	public function getPremiumBp() : bool{
		return $this->premium_bp;
	}

	public function getPremiumBpProgress() : int{
		return $this->premium_bp_progress;
	}

	public function getFreeBpProgress() : int{
		return $this->free_bp_progress;
	}

	public function isOwningCosmetics(CosmeticItem $item) : bool{
		return match ($item->getType()) {
			CosmeticManager::ARTIFACT => in_array($item->getId(), $this->ownedArtifact, true),
			CosmeticManager::CAPE => in_array($item->getId(), $this->ownedCape, true),
			CosmeticManager::PROJECTILE => in_array($item->getId(), $this->ownedProjectile, true),
			CosmeticManager::KILLPHRASE => in_array($item->getId(), $this->ownedKillphrase, true),
			default => false,
		};
	}

	public function alterCosmeticItem(Player $player, CosmeticItem $item, bool $remove = false, bool $sendmsg = true, bool $fragmentize = false, bool $canDuplicate = true) : void{
		$success = match ($item->getType()) {
			CosmeticManager::ARTIFACT => $this->alterOwnedArtifact($item->getId(), $player, $remove, $canDuplicate),
			CosmeticManager::CAPE => $this->alterOwnedCape($item->getId(), $player, $remove, $canDuplicate),
			CosmeticManager::PROJECTILE => $this->alterOwnedProjectile($item->getId(), $remove, $canDuplicate),
			CosmeticManager::KILLPHRASE => $this->alterOwnedKillPhrase($item->getId(), $remove, $canDuplicate),
			default => false,
		};

		if($sendmsg){
			LogMonitor::cosmeticLog("Cosmetic : {$player->getName()} " . ($remove ? "lost" : "get") . " {$item->getDisplayName(true)}");
			$msg = PracticeCore::PREFIX;
			if($success && !$remove){
				$msg .= TextFormat::GRAY . "You have obtained ";
			}elseif($success && $remove){
				$msg .= TextFormat::GRAY . "You have lost ";
			}elseif(!$success && !$remove){
				$msg .= TextFormat::GRAY . "You already have an item ";
			}else{
				$msg .= TextFormat::GRAY . "You do not have the item to remove ";
			}
			$msg .= TextFormat::RESET . $item->getDisplayName(true);
			$player->sendMessage($msg);
			$player->broadcastSound(new XpLevelUpSound(10), [$player]);
		}

		if(($fragmentize && $success == $remove)){
			LogMonitor::cosmeticLog("Cosmetic : {$player->getName()} recycle {$item->getDisplayName(true)}");
			$this->FragmentizeItem($player, $item);
		}
	}

	public function alterCosmeticById(Player $player, int $type, string $id, bool $remove = false, bool $sendmsg = true, bool $fragmentize = false, bool $canDuplicate = true) : void{
		$item = CosmeticManager::getCosmeticFromId($type, $id);
		$this->alterCosmeticItem($player, $item, $remove, $sendmsg, $fragmentize, $canDuplicate);
	}

	public static function getFramentizeAmount(int $rarity) : int{
		return match ($rarity) {
			CosmeticManager::C => 30,
			CosmeticManager::R => 80,
			CosmeticManager::SR => 240,
			CosmeticManager::UR => 750,
			CosmeticManager::LIMITED => 80,
			default => 0
		};
	}

	public function FragmentizeItem(Player $player, CosmeticItem $item, bool $sendmsg = true) : void{
		$msg = PracticeCore::PREFIX . "You have gained ";
		$session = PlayerManager::getSession($player);

		$session->getStatsInfo()->addCurrency(StatsInfo::SHARD, $this::getFramentizeAmount($item->getRarity()));
		$msg .= TextFormat::AQUA . $this::getFramentizeAmount($item->getRarity()) . TextFormat::RESET;

		if($sendmsg){
			$msg .= TextFormat::RESET . " Shards";
			$player->sendMessage($msg);
		}
	}

	public function alterOwnedTag(Player $player, string $tag, bool $remove = false, bool $canDuplicate = true) : bool{
		$key = array_search($tag, $this->ownedTag, true);
		if($remove && $key){
			if($this->tag === $this->ownedTag[$key]){
				$this->tag = "";
				PlayerManager::getSession($player)?->updateNameTag();
			}
			unset($this->ownedTag[$key]);
			return true;
		}elseif(!$remove && ($canDuplicate || !$key)){
			$this->ownedTag[] = $tag;
			return true;
		}
		return false;
	}

	public function alterOwnedProjectile(string $id, bool $remove = false, bool $canDuplicate = true) : bool{
		$key = array_search($id, $this->ownedProjectile, true);
		if($remove && $key){
			if($this->projectile === $this->ownedProjectile[$key]){
				$this->projectile = "0";
			}
			unset($this->ownedProjectile[$key]);
			return true;
		}elseif(!$remove && ($canDuplicate || !$key)){
			$this->ownedProjectile[] = $id;
			return true;
		}
		return false;
	}

	public function alterOwnedArtifact(string $id, Player $player, bool $remove = false, bool $canDuplicate = true) : bool{
		$key = array_search($id, $this->ownedArtifact, true);
		if($remove && $key){
			if($this->artifact === $this->ownedArtifact[$key]){
				$this->artifact = "0";
				CosmeticManager::setStrippedSkin($player, $player->getSkin());
			}
			unset($this->ownedArtifact[$key]);
			return true;
		}elseif(!$remove && ($canDuplicate || !$key)){
			$this->ownedArtifact[] = $id;
			return true;
		}
		return false;
	}

	public function alterOwnedCape(string $id, Player $player, bool $remove = false, bool $canDuplicate = true) : bool{
		$key = array_search($id, $this->ownedCape, true);
		if($remove && $key){
			if($this->cape === $this->ownedCape[$key]){
				$this->cape = "0";
				CosmeticManager::setStrippedSkin($player, $player->getSkin());
			}
			unset($this->ownedCape[$key]);
			return true;
		}elseif(!$remove && ($canDuplicate || !$key)){
			$this->ownedCape[] = $id;
			return true;
		}
		return false;
	}

	public function alterOwnedKillPhrase(string $id, bool $remove = false, bool $canDuplicate = true) : bool{
		$key = array_search($id, $this->ownedKillphrase, true);
		if($remove && $key){
			if($this->killphrase === $this->ownedKillphrase[$key]){
				$this->killphrase = "0";
			}
			unset($this->ownedKillphrase[$key]);
			return true;
		}elseif(!$remove && ($canDuplicate || !$key)){
			$this->ownedKillphrase[] = $id;
			return true;
		}
		return false;
	}

	public function save(string $xuid, string $name, Closure $closure) : void{
		array_multisort(CosmeticManager::getCosmeticItemFromList($this->ownedProjectile, CosmeticManager::PROJECTILE, 2), $this->ownedProjectile);
		array_multisort(CosmeticManager::getCosmeticItemFromList($this->ownedArtifact, CosmeticManager::ARTIFACT, 2), $this->ownedArtifact);
		array_multisort(CosmeticManager::getCosmeticItemFromList($this->ownedCape, CosmeticManager::CAPE, 2), $this->ownedCape);
		array_multisort(CosmeticManager::getCosmeticItemFromList($this->ownedKillphrase, CosmeticManager::KILLPHRASE, 2), $this->ownedKillphrase);
		$potcolor = implode(",", $this->potcolor);
		$ownedProjectile = implode(",", $this->ownedProjectile);
		$ownedTag = implode(",", $this->ownedTag);
		$ownedArtifact = implode(",", $this->ownedArtifact);
		$ownedCape = implode(",", $this->ownedCape);
		$ownedKillphrase = implode(",", $this->ownedKillphrase);
		DatabaseManager::getMainDatabase()->executeImplRaw([0 => "INSERT INTO PlayerItems (xuid, name, potcolor, projectile, tag, artifact, cape, killphrase, ownedprojectile, ownedtag, ownedartifact, ownedcape, ownedkillphrase, premium_bp, free_bp_progress, premium_bp_progress) VALUES ('$xuid', '$name', '$potcolor', '$this->projectile', '$this->tag', '$this->artifact', '$this->cape', '$this->killphrase', '$ownedProjectile', '$ownedTag', '$ownedArtifact', '$ownedCape', '$ownedKillphrase', '$this->premium_bp', '$this->free_bp_progress', '$this->premium_bp_progress') ON DUPLICATE KEY UPDATE name = '$name', potcolor = '$potcolor', projectile = '$this->projectile', tag = '$this->tag', artifact = '$this->artifact', cape = '$this->cape', killphrase = '$this->killphrase', ownedprojectile = '$ownedProjectile', ownedtag = '$ownedTag', ownedartifact = '$ownedArtifact', ownedcape = '$ownedCape', ownedkillphrase = '$ownedKillphrase', premium_bp = '$this->premium_bp', free_bp_progress = '$this->free_bp_progress', premium_bp_progress = '$this->premium_bp_progress'"], [0 => []], [0 => SqlThread::MODE_GENERIC], $closure, $closure);
	}
}
