<?php

declare(strict_types=1);

namespace zodiax\player\info\duel\data;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;

class WorldReplayData{

	private array $tempBlockData;
	private array $blockTimes;

	public function __construct(){
		$this->blockTimes = [];
		$this->tempBlockData = [];
	}

	public function setBlockAt(int $tick, Block $block, bool $break = false) : void{
		$pos = $block->getPosition();
		if($break){
			($newBlockData = new BlockData(VanillaBlocks::AIR(), $tick))->setPosition($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ());
			if(isset($this->tempBlockData[$strPos = $newBlockData->getPositionAsString()])){
				$prevBlockData = $this->tempBlockData[$strPos];
			}else{
				$prevBlockData = new BlockData($block, $tick);
			}
		}else{
			if(isset($this->tempBlockData[$strPos = ($newBlockData = new BlockData($block, $tick))->getPositionAsString()])){
				$prevBlockData = $this->tempBlockData[$strPos];
			}else{
				($prevBlockData = new BlockData(VanillaBlocks::AIR(), $tick))->setPosition($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ());
			}
		}
		$strPos = $newBlockData->getPositionAsString();
		$prevBlockData->setNextBlockData($newBlockData);
		$newBlockData->setPrevBlockData($prevBlockData);
		$this->blockTimes[$prevBlockData->getTickUpdated()][$strPos] = $prevBlockData;
		$this->tempBlockData[$strPos] = $newBlockData;
		$this->blockTimes[$tick][$strPos] = $newBlockData;
	}

	public function getBlocksAt(int $tick, bool $approximate = false) : array{
		if(!$approximate){
			if(isset($this->blockTimes[$tick])){
				return $this->blockTimes[$tick];
			}
			return [];
		}
		$outputBlocks = [];
		foreach($this->tempBlockData as $tempBlockPos => $blockData){
			if($blockData instanceof BlockData){
				$currentBlockData = $blockData;
				while($currentBlockData !== null && $currentBlockData->getTickUpdated() > 0 && $currentBlockData->getTickUpdated() > $tick){
					if(($prevBlockData = $currentBlockData->getPrevBlockData()) === null){
						break;
					}
					$currentBlockData = $prevBlockData;
				}
				$outputBlocks[$tempBlockPos] = $currentBlockData;
			}
		}
		return $outputBlocks;
	}
}