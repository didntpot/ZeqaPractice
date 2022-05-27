<?php

declare(strict_types=1);

namespace zodiax\game\world\thread;

use pocketmine\world\format\Chunk;
use pocketmine\world\SimpleChunkManager;

class ThreadChunkManager extends SimpleChunkManager{

	/** @return Chunk[] */
	public function getChunks() : array{
		$chunks = [];
		foreach($this->chunks as $hash => $chunk){
			if($chunk->isTerrainDirty()){
				$chunks[$hash] = $chunk;
			}
		}
		return $chunks;
	}
}
