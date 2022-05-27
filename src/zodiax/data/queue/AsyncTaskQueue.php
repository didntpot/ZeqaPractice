<?php

declare(strict_types=1);

namespace zodiax\data\queue;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use SplQueue;
use zodiax\data\log\LogMonitor;
use function get_class;

class AsyncTaskQueue{

	private static SplQueue $prepareTasks;
	private static ?AsyncTask $activeTask;

	public static function initialize() : void{
		self::$prepareTasks = new SplQueue();
		self::$activeTask = null;
	}

	public static function addTaskToQueue(AsyncTask $task) : void{
		self::$prepareTasks->push($task);
		self::update();
	}

	public static function update() : void{
		if(self::$activeTask === null && ($task = self::$prepareTasks->shift()) !== null){
			$name = get_class(self::$activeTask = $task);
			LogMonitor::debugLog("ASYNCTASK: Submit task ($name)");
			Server::getInstance()->getAsyncPool()->submitTask(self::$activeTask);
		}elseif(self::$activeTask?->isFinished()){
			self::$activeTask = null;
			self::update();
		}
	}
}