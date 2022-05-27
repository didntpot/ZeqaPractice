<?php

declare(strict_types=1);

namespace zodiax\player\misc\cosmetic\gacha;

use pocketmine\utils\TextFormat;
use zodiax\player\misc\cosmetic\CosmeticManager;
use zodiax\player\misc\cosmetic\misc\CosmeticItem;

class Gacha{

	private string $id;
	private string $name;
	private array $currency;
	private array $items;
	private array $droprate;
	private int $dropraterange;
	private string $texture;
	private string $gachaDetailText;

	public function __construct(string $id, string $name, array $currency, string $texture = null){
		$this->id = $id;
		$this->name = $name;
		$this->currency = $currency;
		$this->items = [];
		$this->droprate = [];
		$this->dropraterange = 0;
		$this->gachaDetailText = "";
		$this->texture = $texture ?? "";
	}

	public function getId() : string{
		return $this->id;
	}

	public function getName() : string{
		return $this->name;
	}

	public function getTexture() : string{
		return $this->texture;
	}

	public function getGachaDetailText() : string{
		return $this->gachaDetailText;
	}

	public function getItems() : array{
		return $this->items;
	}

	public function getDroprateRange() : int{
		return $this->dropraterange;
	}

	public function getDroprate() : array{
		return $this->droprate;
	}

	public function getCurrency($type = null) : int|array|null{
		if($type === null){
			return $this->currency;
		}
		return $this->currency[$type] ?? null;
	}

	public function addItem(?CosmeticItem $item) : void{
		if($item !== null){
			$this->items[] = $item;
		}
	}

	public function calculateDroprate() : void{
		$standardDropRate = [CosmeticManager::LIMITED => 10, CosmeticManager::UR => 5, CosmeticManager::SR => 15, CosmeticManager::R => 30, CosmeticManager::C => 50];
		$countItemRarity = [CosmeticManager::LIMITED => 0, CosmeticManager::UR => 0, CosmeticManager::SR => 0, CosmeticManager::R => 0, CosmeticManager::C => 0];
		$contentItemDropate = [CosmeticManager::LIMITED => "", CosmeticManager::UR => "", CosmeticManager::SR => "", CosmeticManager::R => "", CosmeticManager::C => ""];
		$this->droprate = [];
		foreach($this->items as $item){
			$countItemRarity[$item->getRarity()]++;
			$this->droprate[$item->getUid()] = 0;
		}
		$sumDropRate = 0;
		foreach($standardDropRate as $rarity => $dr){
			if($countItemRarity[$rarity] > 0){
				$sumDropRate += $dr;
			}
		}
		$this->dropraterange = 0;
		foreach($this->items as $item){
			$calculatedDroprate = (($standardDropRate[$item->getRarity()] / $sumDropRate) / $countItemRarity[$item->getRarity()]);
			$contentItemDropate[$item->getRarity()] .= TextFormat::GRAY . "- " . $item->getDisplayName(true) . "\n" . TextFormat::RESET;
			$this->droprate[$item->getUid()] = (int) ($calculatedDroprate * 1000000);
			$this->dropraterange += $this->droprate[$item->getUid()];
		}
		$this->gachaDetailText = "";
		foreach($standardDropRate as $rarity => $dr){
			if($countItemRarity[$rarity] > 0){
				$rarityText = match ($rarity) {
					CosmeticManager::C => TextFormat::GREEN . "COMMON",
					CosmeticManager::R => TextFormat::BLUE . "RARE",
					CosmeticManager::SR => TextFormat::LIGHT_PURPLE . "EPIC",
					CosmeticManager::UR => TextFormat::GOLD . "LEGENDARY",
					CosmeticManager::LIMITED => TextFormat::RED . "LIMITED",
				};
				$drText = TextFormat::WHITE . "[" . ($dr / $sumDropRate * 100) . "%%]";
				$this->gachaDetailText .= TextFormat::BOLD . $rarityText . TextFormat::GRAY . " - " . $drText . "\n\n" . TextFormat::RESET;
				$this->gachaDetailText .= $contentItemDropate[$rarity] . "\n";
			}
		}
	}
}