<?php

declare(strict_types=1);

namespace zodiax\player\info\duel\data;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\math\Vector3;

class BlockData{

	private ?BlockData $prevBlockData;
	private ?BlockData $nextBlockData;
	private int $id;
	private int $meta;
	private Vector3 $position;
	private int $tickUpdated;

	public function __construct(Block $block, int $tickUpdated){
		$this->position = $block->getPosition()->asVector3();
		$this->id = $block->getId();
		$this->meta = $block->getMeta();
		$this->tickUpdated = $tickUpdated;
		$this->prevBlockData = null;
		$this->nextBlockData = null;
	}

	public function getPrevBlockData() : ?BlockData{
		return $this->prevBlockData;
	}

	public function setPrevBlockData(BlockData $blockData) : void{
		$this->prevBlockData = $blockData;
	}

	public function getNextBlockData() : ?BlockData{
		return $this->nextBlockData;
	}

	public function setNextBlockData(BlockData $blockData) : void{
		$this->nextBlockData = $blockData;
	}

	public function getTickUpdated() : int{
		return $this->tickUpdated;
	}

	public function getBlock() : Block{
		return BlockFactory::getInstance()->get($this->id, $this->meta);
	}

	public function getPosition() : Vector3{
		return $this->position;
	}

	public function setPosition(float $x, float $y, float $z){
		$this->position = new Vector3($x, $y, $z);
	}

	public function getPositionAsString() : string{
		$x = (int) $this->position->x;
		$y = (int) $this->position->y;
		$z = (int) $this->position->z;
		return "$x:$y:$z";
	}
}