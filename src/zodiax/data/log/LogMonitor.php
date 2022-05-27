<?php

declare(strict_types=1);

namespace zodiax\data\log;

use pocketmine\Server;
use pocketmine\utils\Config;
use RuntimeException;
use Webmozart\PathUtil\Path;
use zodiax\data\log\thread\LogThread;
use zodiax\data\log\thread\LogThreadPool;
use zodiax\PracticeCore;
use function count;
use function method_exists;
use function mkdir;

class LogMonitor{

	private static LogThreadPool $log;

	public static function initialize() : void{
		@mkdir($path = Path::join(PracticeCore::getDataFolderPath(), "logs"));
		$workers = (new Config(Path::join(PracticeCore::getDataFolderPath(), "settings.yml")))->get("database")["worker-limit"];
		$class_loaders = [];
		$devirion = Server::getInstance()->getPluginManager()->getPlugin("DEVirion");
		if($devirion !== null){
			if(!method_exists($devirion, "getVirionClassLoader")){
				throw new RuntimeException();
			}
			$class_loaders[] = Server::getInstance()->getLoader();
			$class_loaders[] = $devirion->getVirionClassLoader();
		}

		self::$log = new LogThreadPool();
		$workers = 1;
		for($i = 0; $i < $workers; $i++){
			$thread = new LogThread([LogThread::DEBUG => Path::join($path, "debug.log"), LogThread::CHAT => Path::join($path, "chat.log"), LogThread::COSMETIC => Path::join($path, "cosmetic.log")]);
			if(count($class_loaders) > 0){
				$thread->setClassLoaders($class_loaders);
			}
			self::$log->addWorker($thread);
		}
		self::$log->start();

		self::dailyChatLog();
	}

	public static function debugLog(string $log) : void{
		self::$log->getLeastBusyWorker()->write(LogThread::DEBUG, $log);
	}

	public static function chatLog(string $log) : void{
		self::$log->getLeastBusyWorker()->write(LogThread::CHAT, $log);
	}

	public static function cosmeticLog(string $log) : void{
		self::$log->getLeastBusyWorker()->write(LogThread::COSMETIC, $log);
	}

	public static function triggerGarbageCollector() : void{
		self::$log->triggerGarbageCollector();
	}

	public static function dailyChatLog() : void{
		self::$log->getLeastBusyWorker()->dailyChatLog();
	}

	public static function shutdown() : void{
		self::$log->shutdown();
	}
}
