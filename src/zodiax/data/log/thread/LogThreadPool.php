<?php

declare(strict_types=1);

namespace zodiax\data\log\thread;

use UnderflowException;
use function assert;
use function count;
use function spl_object_id;

class LogThreadPool{

	private array $workers = [];

	public function addWorker(LogThread $thread) : void{
		$this->workers[spl_object_id($thread)] = $thread;
	}

	public function start() : void{
		if(count($this->workers) === 0){
			throw new UnderflowException("Cannot start an empty pool of workers");
		}
		foreach($this->workers as $thread){
			$thread->start(PTHREADS_INHERIT_INI | PTHREADS_INHERIT_CONSTANTS);
		}
	}

	public function getLeastBusyWorker() : LogThread{
		$best = null;
		$best_score = INF;
		foreach($this->workers as $thread){
			$score = $thread->busy_score;
			if($score < $best_score){
				$best_score = $score;
				$best = $thread;
				if($score === 0){
					break;
				}
			}
		}
		assert($best !== null);
		return $best;
	}

	public function triggerGarbageCollector() : void{
		foreach($this->workers as $thread){
			$thread->triggerGarbageCollector();
		}
	}

	public function shutdown() : void{
		foreach($this->workers as $thread){
			$thread->stop();
			$thread->join();
		}
		$this->workers = [];
	}
}
