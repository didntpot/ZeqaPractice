<?php

declare(strict_types=1);

namespace zodiax\player\misc\cosmetic\misc;

use pocketmine\utils\TextFormat;
use zodiax\player\misc\cosmetic\CosmeticManager;

class CosmeticItem{

	private string $id;
	private string $uid;
	private string $displayName;
	private string $content;
	private int $type;
	private int $rarity;
	private bool $isTradable;
	private string $displayRarity;
	private string $displayType;
	private string $fullName;

	public function __construct(string $id, string $displayName, int $type, int $rarity, ?string $content, bool $isTradable){
		$this->id = $id;
		$this->displayName = $displayName;
		$this->type = $type;
		$this->rarity = $rarity;
		$this->content = $content ?? "";
		$this->isTradable = $isTradable;
		$this->displayRarity = match ($this->rarity) {
			CosmeticManager::DEFAULT => TextFormat::GRAY,
			CosmeticManager::C => TextFormat::GREEN,
			CosmeticManager::R => TextFormat::BLUE,
			CosmeticManager::SR => TextFormat::LIGHT_PURPLE,
			CosmeticManager::UR => TextFormat::GOLD,
			CosmeticManager::LIMITED => TextFormat::RED,
		};
		$this->displayType = match ($this->type) {
			CosmeticManager::ARTIFACT => TextFormat::WHITE . "[A]" . TextFormat::RESET,
			CosmeticManager::CAPE => TextFormat::WHITE . "[C]" . TextFormat::RESET,
			CosmeticManager::PROJECTILE => TextFormat::WHITE . "[P]" . TextFormat::RESET,
			CosmeticManager::KILLPHRASE => TextFormat::WHITE . "[K]" . TextFormat::RESET,
		};

		$this->fullName = $this->displayRarity . $this->displayName . " " . $this->displayType;
		$this->uid = $this->displayType . $this->id;
	}

	public function getId() : string{
		return $this->id;
	}

	public function getUid() : string{
		return $this->uid;
	}

	public function getRarity(bool $asText = false) : int|string{
		return $asText ? $this->displayRarity : $this->rarity;
	}

	public function getDisplayName(bool $fullName = false) : string{
		return $fullName ? $this->fullName : $this->displayName;
	}

	public function getType(bool $asText = false) : int|string{
		return $asText ? $this->displayType : $this->type;
	}

	public function getContent() : string{
		return $this->content;
	}

	public function isTradable() : bool{
		return $this->isTradable;
	}

	public function setContent(string $content) : void{
		$this->content = $content;
	}
}
