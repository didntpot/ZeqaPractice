<?php

declare(strict_types=1);

namespace zodiax\player\info\duel;

use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\utils\TextFormat;
use zodiax\kits\KitsManager;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use function round;

class DuelInfo{

	private string $winner;
	private string $winnerDisplayName;
	private array $winnerPostInv;
	private string $loser;
	private string $loserDisplayName;
	private array $loserPostInv;
	private string $kit;
	private bool $ranked;
	private bool $draw;

	public function __construct(string $player1Name, string $player1DisplayName, string $player2Name, string $player2DisplayName, string $kit, bool $ranked, array $matchData, string $winner = ""){
		$this->winner = ($winner === "" ? $player1Name : ($winner === $player1Name ? $player1Name : $player2Name));
		$this->winnerDisplayName = ($this->winner === $player1Name ? $player1DisplayName : $player2DisplayName);
		$this->loser = ($winner === "" ? $player2Name : ($winner === $player1Name ? $player2Name : $player1Name));
		$this->loserDisplayName = ($this->winner === $player1Name ? $player2DisplayName : $player1DisplayName);
		$this->kit = $kit;
		$this->ranked = $ranked;
		$this->draw = $winner === "";
		$kit = KitsManager::getKit($kit);
		$itemFactory = ItemFactory::getInstance();

		$health = 0;
		$hunger = 0;
		$items = [];
		$armors = [];
		if(($winner = PlayerManager::getPlayerExact($this->winner)) !== null){
			$health = (int) $winner->getHealth();
			$hunger = (int) $winner->getHungerManager()->getFood();
			$items = $winner->getInventory()->getContents(true);
			$armors = $winner->getArmorInventory()->getContents(true);
		}
		$count = 0;
		$row = 0;
		$maxRows = 3;
		foreach($items as $item){
			$currentRow = $maxRows - $row;
			$v = ($currentRow + 1) * 9;
			if($row === 0){
				$v = $v - 9;
				$val = ($count % 9) + $v;
			}else{
				$val = $count - 9;
			}
			if($val != -1){
				$this->winnerPostInv[$val] = $item;
			}
			$count++;
			if($count % 9 === 0 && $count !== 0){
				$row++;
			}
		}
		$row = $maxRows + 1;
		$lastRowIndex = ($row + 1) * 9;
		$secLastRowIndex = $row * 9;
		foreach($armors as $armor){
			$this->winnerPostInv[$secLastRowIndex++] = $armor;
		}
		$this->winnerPostInv[$lastRowIndex++] = $itemFactory->get(ItemIds::MOB_HEAD, 3, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "$this->winnerDisplayName's Stats")->setLore([TextFormat::RESET . TextFormat::WHITE . " Health: " . PracticeCore::COLOR . $health, TextFormat::RESET . TextFormat::WHITE . " Hunger: " . PracticeCore::COLOR . $hunger]);
		$this->winnerPostInv[$lastRowIndex++] = $itemFactory->get(ItemIds::BLAZE_ROD, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Hits Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Total Hits: " . PracticeCore::COLOR . $matchData[$this->winner]["numHits"], TextFormat::RESET . TextFormat::WHITE . " Critical Hits: " . PracticeCore::COLOR . $matchData[$this->winner]["criticalHits"], TextFormat::RESET . TextFormat::WHITE . " Longest Combo: " . PracticeCore::COLOR . $matchData[$this->winner]["longestCombo"]]);
		switch($this->kit){
			case "BattleRush":
			case "MLGRush":
				$this->winnerPostInv[$lastRowIndex++] = $itemFactory->get(ItemIds::GRASS, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Build Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Blocks Placed: " . PracticeCore::COLOR . $matchData[$this->winner]["placedBlocks"], TextFormat::RESET . TextFormat::WHITE . " Blocks Destroyed: " . PracticeCore::COLOR . $matchData[$this->winner]["brokeBlocks"]]);
				$this->winnerPostInv[$lastRowIndex] = $itemFactory->get(ItemIds::PAPER, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Match Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Score: " . PracticeCore::COLOR . $matchData[$this->winner]["extraScores"], TextFormat::RESET . TextFormat::WHITE . " Kills: " . PracticeCore::COLOR . $matchData[$this->winner]["kills"], TextFormat::RESET . TextFormat::WHITE . " Deaths: " . PracticeCore::COLOR . $matchData[$this->winner]["deaths"]]);
				break;
			case "BedFight":
				$this->winnerPostInv[$lastRowIndex++] = $itemFactory->get(ItemIds::GRASS, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Build Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Blocks Placed: " . PracticeCore::COLOR . $matchData[$this->winner]["placedBlocks"], TextFormat::RESET . TextFormat::WHITE . " Blocks Destroyed: " . PracticeCore::COLOR . $matchData[$this->winner]["brokeBlocks"]]);
				$this->winnerPostInv[$lastRowIndex] = $itemFactory->get(ItemIds::PAPER, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Match Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Bed: " . PracticeCore::COLOR . ($matchData[$this->winner]["extraFlag"] ? "Exist" : "Not Exist"), TextFormat::RESET . TextFormat::WHITE . " Kills: " . PracticeCore::COLOR . $matchData[$this->winner]["kills"], TextFormat::RESET . TextFormat::WHITE . " Deaths: " . PracticeCore::COLOR . $matchData[$this->winner]["deaths"]]);
				break;
			case "Bridge":
				$this->winnerPostInv[$lastRowIndex++] = $itemFactory->get(ItemIds::ARROW, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Arrow Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Used: " . PracticeCore::COLOR . $matchData[$this->winner]["arrowsUsed"], TextFormat::RESET . TextFormat::WHITE . " Missed: " . PracticeCore::COLOR . $matchData[$this->winner]["arrowsUsed"] - $matchData[$this->winner]["arrowsHit"]]);
				$this->winnerPostInv[$lastRowIndex++] = $itemFactory->get(ItemIds::GRASS, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Build Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Blocks Placed: " . PracticeCore::COLOR . $matchData[$this->winner]["placedBlocks"], TextFormat::RESET . TextFormat::WHITE . " Blocks Destroyed: " . PracticeCore::COLOR . $matchData[$this->winner]["brokeBlocks"]]);
				$this->winnerPostInv[$lastRowIndex] = $itemFactory->get(ItemIds::PAPER, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Match Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Score: " . PracticeCore::COLOR . $matchData[$this->winner]["extraScores"], TextFormat::RESET . TextFormat::WHITE . " Kills: " . PracticeCore::COLOR . $matchData[$this->winner]["kills"], TextFormat::RESET . TextFormat::WHITE . " Deaths: " . PracticeCore::COLOR . $matchData[$this->winner]["deaths"]]);
				break;
			case "BuildUHC":
			case "Classic":
				$this->winnerPostInv[$lastRowIndex++] = $itemFactory->get(ItemIds::FISHING_ROD, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Rod Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Used: " . PracticeCore::COLOR . $matchData[$this->winner]["rodUsed"], TextFormat::RESET . TextFormat::WHITE . " Missed: " . PracticeCore::COLOR . $matchData[$this->winner]["rodUsed"] - $matchData[$this->winner]["rodHit"], TextFormat::RESET . TextFormat::WHITE . " Accuracy: " . PracticeCore::COLOR . round($matchData[$this->winner]["rodHit"] / ($matchData[$this->winner]["rodUsed"] === 0 ? 1 : $matchData[$this->winner]["rodUsed"]) * 100) . "%"]);
				$this->winnerPostInv[$lastRowIndex++] = $itemFactory->get(ItemIds::ARROW, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Arrow Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Used: " . PracticeCore::COLOR . $matchData[$this->winner]["arrowsUsed"], TextFormat::RESET . TextFormat::WHITE . " Missed: " . PracticeCore::COLOR . $matchData[$this->winner]["arrowsUsed"] - $matchData[$this->winner]["arrowsHit"]]);
				$this->winnerPostInv[$lastRowIndex] = $itemFactory->get(ItemIds::GRASS, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Build Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Blocks Placed: " . PracticeCore::COLOR . $matchData[$this->winner]["placedBlocks"], TextFormat::RESET . TextFormat::WHITE . " Blocks Destroyed: " . PracticeCore::COLOR . $matchData[$this->winner]["brokeBlocks"]]);
				break;
			case "Nodebuff":
				$itemCount = 0;
				foreach($items as $item){
					if($item->getId() === ItemIds::SPLASH_POTION){
						$itemCount++;
					}
				}
				$this->winnerPostInv[$lastRowIndex] = $itemFactory->get(ItemIds::SPLASH_POTION, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Potion Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Remaining: " . PracticeCore::COLOR . $itemCount, TextFormat::RESET . TextFormat::WHITE . " Missed: " . PracticeCore::COLOR . $matchData[$this->winner]["potsUsed"] - $matchData[$this->winner]["potsHit"], TextFormat::RESET . TextFormat::WHITE . " Thrown: " . PracticeCore::COLOR . $matchData[$this->winner]["potsUsed"], TextFormat::RESET . TextFormat::WHITE . " Accuracy: " . PracticeCore::COLOR . round($matchData[$this->winner]["potsHit"] / ($matchData[$this->winner]["potsUsed"] === 0 ? 1 : $matchData[$this->winner]["potsUsed"]) * 100) . "%"]);
				break;
			case "Soup":
				$itemCount = 0;
				foreach($items as $item){
					if($item->getId() === ItemIds::MUSHROOM_STEW){
						$itemCount++;
					}
				}
				$soup = 0;
				$kitItems = $kit->getItems();
				foreach($kitItems as $item){
					if($item->getId() === ItemIds::MUSHROOM_STEW){
						$soup++;
					}
				}
				$this->winnerPostInv[$lastRowIndex] = $itemFactory->get(ItemIds::MUSHROOM_STEW, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Soup Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Remaining: " . PracticeCore::COLOR . $itemCount, TextFormat::RESET . TextFormat::WHITE . " Used: " . PracticeCore::COLOR . $soup - $itemCount]);
				break;
			case "StickFight":
				$this->winnerPostInv[$lastRowIndex] = $itemFactory->get(ItemIds::PAPER, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Match Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Kills: " . PracticeCore::COLOR . $matchData[$this->winner]["kills"], TextFormat::RESET . TextFormat::WHITE . " Deaths: " . PracticeCore::COLOR . $matchData[$this->winner]["deaths"]]);
				break;
		}
		$switch = $itemFactory->get(ItemIds::PAPER, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::RED . $this->loserDisplayName . "'s" . TextFormat::GRAY . " Inventory")->setLore([TextFormat::RESET . TextFormat::GRAY . "Click to view"]);
		$this->winnerPostInv[53] = $switch->setNamedTag($switch->getNamedTag()->setInt("SwitchKey", 0));

		$health = 0;
		$hunger = 0;
		$items = [];
		$armors = [];
		if(($loser = PlayerManager::getPlayerExact($this->loser)) !== null){
			$health = $this->draw ? (int) $loser->getHealth() : 0;
			$hunger = (int) $loser->getHungerManager()->getFood();
			$items = $loser->getInventory()->getContents(true);
			$armors = $loser->getArmorInventory()->getContents(true);
		}
		$count = 0;
		$row = 0;
		$maxRows = 3;
		foreach($items as $item){
			$currentRow = $maxRows - $row;
			$v = ($currentRow + 1) * 9;
			if($row === 0){
				$v = $v - 9;
				$val = ($count % 9) + $v;
			}else{
				$val = $count - 9;
			}
			if($val != -1){
				$this->loserPostInv[$val] = $item;
			}
			$count++;
			if($count % 9 === 0 && $count !== 0){
				$row++;
			}
		}
		$row = $maxRows + 1;
		$lastRowIndex = ($row + 1) * 9;
		$secLastRowIndex = $row * 9;
		foreach($armors as $armor){
			$this->loserPostInv[$secLastRowIndex++] = $armor;
		}
		$this->loserPostInv[$lastRowIndex++] = $itemFactory->get(ItemIds::MOB_HEAD, 3, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "$this->loserDisplayName's Stats")->setLore([TextFormat::RESET . TextFormat::WHITE . " Health: " . PracticeCore::COLOR . $health, TextFormat::RESET . TextFormat::WHITE . " Hunger: " . PracticeCore::COLOR . $hunger]);
		$this->loserPostInv[$lastRowIndex++] = $itemFactory->get(ItemIds::BLAZE_ROD, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Hits Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Total Hits: " . PracticeCore::COLOR . $matchData[$this->loser]["numHits"], TextFormat::RESET . TextFormat::WHITE . " Critical Hits: " . PracticeCore::COLOR . $matchData[$this->loser]["criticalHits"], TextFormat::RESET . TextFormat::WHITE . " Longest Combo: " . PracticeCore::COLOR . $matchData[$this->loser]["longestCombo"]]);
		switch($this->kit){
			case "BattleRush":
				$this->loserPostInv[$lastRowIndex++] = $itemFactory->get(ItemIds::GRASS, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Build Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Blocks Placed: " . PracticeCore::COLOR . $matchData[$this->loser]["placedBlocks"], TextFormat::RESET . TextFormat::WHITE . " Blocks Destroyed: " . PracticeCore::COLOR . $matchData[$this->loser]["brokeBlocks"]]);
				$this->loserPostInv[$lastRowIndex] = $itemFactory->get(ItemIds::PAPER, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Match Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Score: " . PracticeCore::COLOR . $matchData[$this->loser]["extraScores"], TextFormat::RESET . TextFormat::WHITE . " Kills: " . PracticeCore::COLOR . $matchData[$this->loser]["kills"], TextFormat::RESET . TextFormat::WHITE . " Deaths: " . PracticeCore::COLOR . $matchData[$this->loser]["deaths"]]);
				break;
			case "BedFight":
				$this->loserPostInv[$lastRowIndex++] = $itemFactory->get(ItemIds::GRASS, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Build Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Blocks Placed: " . PracticeCore::COLOR . $matchData[$this->loser]["placedBlocks"], TextFormat::RESET . TextFormat::WHITE . " Blocks Destroyed: " . PracticeCore::COLOR . $matchData[$this->loser]["brokeBlocks"]]);
				$this->loserPostInv[$lastRowIndex] = $itemFactory->get(ItemIds::PAPER, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Match Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Bed: " . PracticeCore::COLOR . ($matchData[$this->loser]["extraFlag"] ? "Exist" : "Not Exist"), TextFormat::RESET . TextFormat::WHITE . " Kills: " . PracticeCore::COLOR . $matchData[$this->loser]["kills"], TextFormat::RESET . TextFormat::WHITE . " Deaths: " . PracticeCore::COLOR . $matchData[$this->loser]["deaths"]]);
				break;
			case "Bridge":
				$this->loserPostInv[$lastRowIndex++] = $itemFactory->get(ItemIds::ARROW, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Arrow Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Used: " . PracticeCore::COLOR . $matchData[$this->loser]["arrowsUsed"], TextFormat::RESET . TextFormat::WHITE . " Missed: " . PracticeCore::COLOR . $matchData[$this->loser]["arrowsUsed"] - $matchData[$this->loser]["arrowsHit"]]);
				$this->loserPostInv[$lastRowIndex++] = $itemFactory->get(ItemIds::GRASS, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Build Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Blocks Placed: " . PracticeCore::COLOR . $matchData[$this->loser]["placedBlocks"], TextFormat::RESET . TextFormat::WHITE . " Blocks Destroyed: " . PracticeCore::COLOR . $matchData[$this->loser]["brokeBlocks"]]);
				$this->loserPostInv[$lastRowIndex] = $itemFactory->get(ItemIds::PAPER, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Match Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Score: " . PracticeCore::COLOR . $matchData[$this->loser]["extraScores"], TextFormat::RESET . TextFormat::WHITE . " Kills: " . PracticeCore::COLOR . $matchData[$this->loser]["kills"], TextFormat::RESET . TextFormat::WHITE . " Deaths: " . PracticeCore::COLOR . $matchData[$this->loser]["deaths"]]);
				break;
			case "BuildUHC":
			case "Classic":
				$this->loserPostInv[$lastRowIndex++] = $itemFactory->get(ItemIds::FISHING_ROD, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Rod Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Used: " . PracticeCore::COLOR . $matchData[$this->loser]["rodUsed"], TextFormat::RESET . TextFormat::WHITE . " Missed: " . PracticeCore::COLOR . $matchData[$this->loser]["rodUsed"] - $matchData[$this->loser]["rodHit"], TextFormat::RESET . TextFormat::WHITE . " Accuracy: " . PracticeCore::COLOR . round($matchData[$this->loser]["rodHit"] / ($matchData[$this->loser]["rodUsed"] === 0 ? 1 : $matchData[$this->loser]["rodUsed"]) * 100) . "%"]);
				$this->loserPostInv[$lastRowIndex++] = $itemFactory->get(ItemIds::ARROW, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Arrow Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Used: " . PracticeCore::COLOR . $matchData[$this->loser]["arrowsUsed"], TextFormat::RESET . TextFormat::WHITE . " Missed: " . PracticeCore::COLOR . $matchData[$this->loser]["arrowsUsed"] - $matchData[$this->loser]["arrowsHit"]]);
				$this->loserPostInv[$lastRowIndex] = $itemFactory->get(ItemIds::GRASS, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Build Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Blocks Placed: " . PracticeCore::COLOR . $matchData[$this->loser]["placedBlocks"], TextFormat::RESET . TextFormat::WHITE . " Blocks Destroyed: " . PracticeCore::COLOR . $matchData[$this->loser]["brokeBlocks"]]);
				break;
			case "Nodebuff":
				$itemCount = 0;
				foreach($items as $item){
					if($item->getId() === ItemIds::SPLASH_POTION){
						$itemCount++;
					}
				}
				$this->loserPostInv[$lastRowIndex] = $itemFactory->get(ItemIds::SPLASH_POTION, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Potion Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Remaining: " . PracticeCore::COLOR . $itemCount, TextFormat::RESET . TextFormat::WHITE . " Missed: " . PracticeCore::COLOR . $matchData[$this->loser]["potsUsed"] - $matchData[$this->loser]["potsHit"], TextFormat::RESET . TextFormat::WHITE . " Thrown: " . PracticeCore::COLOR . $matchData[$this->loser]["potsUsed"], TextFormat::RESET . TextFormat::WHITE . " Accuracy: " . PracticeCore::COLOR . round($matchData[$this->loser]["potsHit"] / ($matchData[$this->loser]["potsUsed"] === 0 ? 1 : $matchData[$this->loser]["potsUsed"]) * 100) . "%"]);
				break;
			case "Soup":
				$itemCount = 0;
				foreach($items as $item){
					if($item->getId() === ItemIds::MUSHROOM_STEW){
						$itemCount++;
					}
				}
				$soup = 0;
				$kitItems = $kit->getItems();
				foreach($kitItems as $item){
					if($item->getId() === ItemIds::MUSHROOM_STEW){
						$soup++;
					}
				}
				$this->loserPostInv[$lastRowIndex] = $itemFactory->get(ItemIds::MUSHROOM_STEW, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Soup Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Remaining: " . PracticeCore::COLOR . $itemCount, TextFormat::RESET . TextFormat::WHITE . " Used: " . PracticeCore::COLOR . $soup - $itemCount]);
				break;
			case "StickFight":
				$this->loserPostInv[$lastRowIndex] = $itemFactory->get(ItemIds::PAPER, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::BOLD . PracticeCore::COLOR . "Match Statistics")->setLore([TextFormat::RESET . TextFormat::WHITE . " Kills: " . PracticeCore::COLOR . $matchData[$this->loser]["kills"], TextFormat::RESET . TextFormat::WHITE . " Deaths: " . PracticeCore::COLOR . $matchData[$this->loser]["deaths"]]);
				break;
		}
		$switch = $itemFactory->get(ItemIds::PAPER, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::GREEN . $this->winnerDisplayName . "'s" . TextFormat::GRAY . " Inventory")->setLore([TextFormat::RESET . TextFormat::GRAY . "Click to view"]);
		$this->loserPostInv[53] = $switch->setNamedTag($switch->getNamedTag()->setInt("SwitchKey", 1));
	}

	public function getWinnerName() : string{
		return $this->winner;
	}

	public function getWinnerDisplayName() : string{
		return $this->winnerDisplayName;
	}

	public function getWinnerPostInv() : array{
		return $this->winnerPostInv;
	}

	public function getLoserName() : string{
		return $this->loser;
	}

	public function getLoserDisplayName() : string{
		return $this->loserDisplayName;
	}

	public function getLoserPostInv() : array{
		return $this->loserPostInv;
	}

	public function getKit() : string{
		return $this->kit;
	}

	public function isRanked() : bool{
		return $this->ranked;
	}

	public function isDraw() : bool{
		return $this->draw;
	}

	public function getTexture() : string{
		return KitsManager::getKit($this->kit)?->getMiscKitInfo()->getTexture() ?? "";
	}
}
