<?php

declare(strict_types=1);

namespace zodiax\game\world;

use Closure;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\Position;
use pocketmine\world\World;
use RuntimeException;
use Webmozart\PathUtil\Path;
use zodiax\game\world\thread\BlockRemoverThread;
use zodiax\game\world\thread\BlockRemoverThreadPool;
use zodiax\misc\AbstractRepeatingTask;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function count;
use function method_exists;
use function mkdir;

class BlockRemoverHandler extends AbstractRepeatingTask{

	private static int $currentTick = 0;
	/** @var array<int, Position[]> $blocks */
	private static array $blocks = [];
	private static BlockRemoverThreadPool $remover;
	/** @var array<int, Closure> $removerHandler */
	private static array $removerHandler;

	public function __construct(){
		parent::__construct(PracticeUtil::secondsToTicks(1));
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

		self::$remover = new BlockRemoverThreadPool();
		$workers = 1;
		for($i = 0; $i < $workers; $i++){
			$thread = new BlockRemoverThread(self::$remover->getNotifier());
			if(count($class_loaders) > 0){
				$thread->setClassLoaders($class_loaders);
			}
			self::$remover->addWorker($thread);
		}
		self::$remover->start();
		self::$removerHandler = [];
	}

	public function onUpdate(int $tickDifference) : void{
		self::$currentTick++;
		if(isset(self::$blocks[self::$currentTick])){
			foreach(self::$blocks[self::$currentTick] as $pos){
				if($pos->isValid()){
					$world = $pos->getWorld();
					if(($block = $world->getBlock($pos))->getId() !== BlockLegacyIds::AIR){
						PracticeUtil::onChunkGenerated($world, $pos->getFloorX() >> 4, $pos->getFloorZ() >> 4, function() use ($world, $pos, $block){
							$world->addParticle($pos->add(0.5, 0.5, 0.5), new BlockBreakParticle($block));
							$world->setBlock($pos, VanillaBlocks::AIR(), false);
						});
					}
				}else{
					self::setBlockToRemove($pos);
				}
			}
			unset(self::$blocks[self::$currentTick]);
		}
	}

	public static function setBlockToRemove(Position $pos) : void{
		$tick = self::$currentTick + 10;
		if(isset(self::$blocks[$tick])){
			self::$blocks[$tick][] = $pos;
		}else{
			self::$blocks[$tick] = [$pos];
		}
	}

	public static function removeBlocks(World $world, array $chunks, array $backup = []) : void{
		self::$removerHandler[$worldId = $world->getId()] = Closure::fromCallable(function() use ($worldId, $backup){
			$world = Server::getInstance()->getWorldManager()->getWorld($worldId);
			if($world instanceof World){
				foreach($backup as $hash => $block){
					World::getBlockXYZ($hash, $x, $y, $z);
					PracticeUtil::onChunkGenerated($world, $x >> 4, $z >> 4, function() use ($world, $x, $y, $z, $block){
						$world->setBlockAt($x, $y, $z, $block, false);
					});
				}
			}
		});
		foreach($chunks as $hash => $chunkCache){
			World::getXZ($hash, $x, $z);
			if(($chunk = $world->loadChunk($x, $z)) !== null){
				$chunkCache->setSerializeTerrain(FastChunkSerializer::serializeTerrain($chunk));
			}else{
				unset($chunks[$hash]);
			}
		}
		self::$remover->getLeastBusyWorker()->queue($worldId, $chunks);
	}

	public static function updateHolder(array $result) : void{
		if(isset(self::$removerHandler[$worldId = $result[0]])){
			$world = Server::getInstance()->getWorldManager()->getWorld($worldId);
			if($world instanceof World){
				$chunks = $result[1];
				foreach($chunks as $hash => $chunk){
					World::getXZ($hash, $x, $z);
					$world->setChunk($x, $z, $chunk);
				}
				self::$removerHandler[$worldId]();
			}
			unset(self::$removerHandler[$worldId]);
		}
	}

	public static function triggerGarbageCollector() : void{
		self::$remover->triggerGarbageCollector();
	}

	public static function shutdown() : void{
		self::$remover->shutdown();
	}
}