<?php

declare(strict_types=1);

namespace zodiax\game\world;

use pocketmine\math\Vector3;
use pocketmine\world\ChunkListener;
use pocketmine\world\ChunkLoader;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;

class PracticeChunkLoader implements ChunkListener, ChunkLoader{

	private int $chunkX;
	private int $chunkZ;
	private World $world;
	private $callable; // @phpstan-ignore-line

	public function __construct(World $world, int $chunkX, int $chunkZ, callable $callable){
		$this->world = $world;
		$this->chunkX = $chunkX;
		$this->chunkZ = $chunkZ;
		$this->callable = $callable;
	}

	public function onChunkLoaded(int $chunkX, int $chunkZ, Chunk $chunk) : void{
		if(!$chunk->isPopulated()){
			return;
		}
		$this->onComplete();
	}

	public function onChunkPopulated(int $chunkX, int $chunkZ, Chunk $chunk) : void{
		$this->onComplete();
	}

	public function onComplete() : void{
		$this->world->unregisterChunkLoader($this, $this->chunkX, $this->chunkZ);
		$this->world->unregisterChunkListener($this, $this->chunkX, $this->chunkZ);
		($this->callable)();
	}

	public function onChunkChanged(int $chunkX, int $chunkZ, Chunk $chunk) : void{
	}

	public function onBlockChanged(Vector3 $block) : void{
	}

	public function onChunkUnloaded(int $chunkX, int $chunkZ, Chunk $chunk) : void{
	}
}