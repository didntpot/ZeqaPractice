<?php

declare(strict_types=1);

namespace zodiax\game\world\thread;

use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class ChunkCache{

	private string $serializeTerrain;
	/** @var array<int, int> $blocks */
	private array $blocks;

	public function setSerializeTerrain(string $serializeTerrain) : void{
		$this->serializeTerrain = $serializeTerrain;
	}

	public function getSerializeTerrain() : string{
		return $this->serializeTerrain;
	}

	public function addBlock(Block $block, Vector3 $pos) : void{
		if(!isset($this->blocks[$hash = World::blockHash($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ())])){
			$this->blocks[$hash] = $block->getFullId();
		}
	}

	public function removeBlock(Block $block, Vector3 $pos) : void{
		if(isset($this->blocks[$hash = World::blockHash($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ())])){
			unset($this->blocks[$hash]);
		}else{
			$this->blocks[$hash] = $block->getFullId();
		}
	}

	/**
	 * @return array<int, int>
	 */
	public function getBlocks() : array{
		return $this->blocks;
	}
}
