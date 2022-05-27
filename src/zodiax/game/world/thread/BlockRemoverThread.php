<?php

declare(strict_types=1);

namespace zodiax\game\world\thread;

use pocketmine\block\BlockFactory;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\thread\Thread;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\World;
use Threaded;
use zodiax\game\world\BlockRemoverHandler;
use function gc_collect_cycles;
use function gc_enable;
use function gc_mem_caches;
use function igbinary_serialize;
use function igbinary_unserialize;
use function is_array;

class BlockRemoverThread extends Thread{

	public int $busy_score = 0;
	private SleeperNotifier $notifier;
	private Threaded $actionQueue;
	private Threaded $actionResults;
	private bool $running;

	public function __construct(SleeperNotifier $notifier){
		$this->notifier = $notifier;
		$this->actionQueue = new Threaded();
		$this->actionResults = new Threaded();
	}

	public function start(int $options = PTHREADS_INHERIT_ALL) : bool{
		$this->running = true;
		return parent::start($options);
	}

	public function sleep() : void{
		$this->synchronized(function() : void{
			if($this->running){
				$this->wait();
			}
		});
	}

	public function stop() : void{
		$this->running = false;
		$this->synchronized(function() : void{
			$this->notify();
		});
	}

	public function queue(int $worldId, array $chunks) : void{
		$this->synchronized(function() use ($worldId, $chunks) : void{
			$this->actionQueue[] = igbinary_serialize([$worldId, $chunks]);
			++$this->busy_score;
			$this->notifyOne();
		});
	}

	public function triggerGarbageCollector() : void{
		$this->synchronized(function() : void{
			$this->actionQueue[] = igbinary_serialize("garbage_collector");
			$this->notifyOne();
		});
	}

	public function onRun() : void{
		while($this->running){
			while(($queue = $this->actionQueue->shift()) !== null){
				$queue = igbinary_unserialize($queue);
				if(is_array($queue)){
					$chunks = $queue[1];
					$blockFactory = BlockFactory::getInstance();
					$manager = new ThreadChunkManager(0, 255);
					foreach($chunks as $chunkHash => $chunkCache){
						World::getXZ($chunkHash, $chunkX, $chunkZ);
						$manager->setChunk($chunkX, $chunkZ, FastChunkSerializer::deserializeTerrain($chunkCache->getSerializeTerrain()));
						if($manager->getChunk($chunkX, $chunkZ) !== null){
							$blocks = $chunkCache->getBlocks();
							foreach($blocks as $hash => $block){
								World::getBlockXYZ($hash, $x, $y, $z);
								$manager->setBlockAt($x, $y, $z, $blockFactory->fromFullBlock($block));
							}
						}
					}
					$this->actionResults[] = igbinary_serialize([$queue[0], $manager->getChunks()]);
					$this->notifier->wakeupSleeper();
				}elseif($queue === "garbage_collector"){
					gc_enable();
					gc_collect_cycles();
					gc_mem_caches();
				}
			}
			$this->sleep();
		}
	}

	public function collectActionResults() : void{
		while(($result = $this->actionResults->shift()) !== null){
			BlockRemoverHandler::updateHolder(igbinary_unserialize($result));
			--$this->busy_score;
		}
	}
}