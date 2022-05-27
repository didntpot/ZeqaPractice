<?php

declare(strict_types=1);

namespace zodiax\player\misc\cosmetic\misc\thread;

use pocketmine\snooze\SleeperNotifier;
use pocketmine\thread\Thread;
use Threaded;
use Webmozart\PathUtil\Path;
use zodiax\player\misc\cosmetic\CosmeticManager;
use zodiax\player\misc\cosmetic\misc\CosmeticQueue;
use zodiax\PracticeCore;
use function copy;
use function file_exists;
use function gc_collect_cycles;
use function gc_enable;
use function gc_mem_caches;
use function igbinary_serialize;
use function igbinary_unserialize;
use function imagealphablending;
use function imagecolorallocatealpha;
use function imagecolortransparent;
use function imagecopymerge;
use function imagecreatefrompng;
use function imagecreatetruecolor;
use function imagedestroy;
use function imagefill;
use function imagepng;
use function imagesavealpha;
use function imagesetpixel;
use function intdiv;
use function intval;
use function ord;
use function strlen;

class CosmeticThread extends Thread{

	public int $busy_score = 0;
	private SleeperNotifier $notifier;
	private Threaded $actionQueue;
	private Threaded $actionResults;
	private bool $running;
	private bool $isLobby;
	private string $dataFolder;
	private string $resourceFolder;

	public function __construct(SleeperNotifier $notifier){
		$this->notifier = $notifier;
		$this->actionQueue = new Threaded();
		$this->actionResults = new Threaded();
		$this->isLobby = PracticeCore::isLobby();
		$this->dataFolder = PracticeCore::getDataFolderPath();
		$this->resourceFolder = PracticeCore::getResourcesFolder();
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

	public function queue(CosmeticQueue $queue) : void{
		$this->synchronized(function() use ($queue) : void{
			$this->actionQueue[] = igbinary_serialize($queue);
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
				if($queue instanceof CosmeticQueue){
					$name = $queue->getPlayer();
					switch($queue->getMethod()){
						case CosmeticQueue::LOAD:
							$imagePath = $queue->getExtraData();
							if($queue->getBoolean()){
								$path = Path::join($this->dataFolder, "players", "skin", "$name.png");
								if(!file_exists($path)){
									copy(Path::join($this->resourceFolder, "cosmetic", "default_skin.png"), $path);
								}
								$down = imagecreatefrompng($path);
								$upper = imagecreatefrompng($queue->getExtraData());
								imagecolortransparent($upper, imagecolorallocatealpha($upper, 0, 0, 0, 127));
								imagealphablending($down, false);
								imagesavealpha($down, true);
								imagecopymerge($down, $upper, 0, 0, 0, 0, 128, 128, 100);
								imagepng($down, $imagePath = $this->dataFolder . "temp.png");
								imagedestroy($down);
								imagedestroy($upper);
							}
							$skinData = CosmeticManager::getSkinDataFromPNG($imagePath);
							$this->actionResults[] = igbinary_serialize(["name" => $name, "skinData" => $skinData]);
							$this->notifier->wakeupSleeper();
							break;
						case CosmeticQueue::SAVE:
							$path = Path::join($this->dataFolder, "players", "skin", "$name.png");
							if($queue->getBoolean()){
								copy(Path::join($this->resourceFolder, "cosmetic", "default_skin.png"), $path);
							}else{
								$skinData = $queue->getExtraData();
								$size = strlen($skinData);
								$width = [64 * 32 * 4 => 64, 64 * 64 * 4 => 64, 128 * 128 * 4 => 128][$size];
								$height = [64 * 32 * 4 => 32, 64 * 64 * 4 => 64, 128 * 128 * 4 => 128][$size];
								$skinPos = 0;
								$image = imagecreatetruecolor(128, 128);
								$head = false;
								if($this->isLobby){
									$head = imagecreatetruecolor(64, 64);
								}
								if($image !== false){
									imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
									if($head !== false){
										imagefill($head, 0, 0, imagecolorallocatealpha($head, 0, 0, 0, 127));
									}
									for($y = 0; $y < $height; $y++){
										for($x = 0; $x < $width; $x++){
											$r = ord($skinData[$skinPos]);
											$skinPos++;
											$g = ord($skinData[$skinPos]);
											$skinPos++;
											$b = ord($skinData[$skinPos]);
											$skinPos++;
											$a = 127 - intdiv(ord($skinData[$skinPos]), 2);
											$skinPos++;
											$col = imagecolorallocatealpha($image, $r, $g, $b, $a);
											if($width === 128 && $height === 128){
												imagesetpixel($image, $x, $y, $col);
											}else{
												imagesetpixel($image, $x * 2, $y * 2, $col);
												imagesetpixel($image, $x * 2 + 1, $y * 2, $col);
												imagesetpixel($image, $x * 2, $y * 2 + 1, $col);
												imagesetpixel($image, $x * 2 + 1, $y * 2 + 1, $col);
											}
											if($head !== false){
												if($x >= $width / 8 && $x < ($width / 8) * 2 && $y >= $height / 8 && $y < ($height / 8) * 2){
													$nheight = 64 / ($height / 8);
													$nwidth = 64 / ($width / 8);
													for($i = 0; $i < $nheight; $i++){
														for($j = 0; $j < $nwidth; $j++){
															$hx = ($x % intval($width / 8)) * $nwidth + $x % $nwidth + $j - $x % $nwidth;
															if($x % $nwidth === 0){
																$hx = ($x - intval($width / 8)) * $nwidth + $j;
															}
															$hy = ($y % intval($height / 8)) * $nheight + $y % $nheight + $i - $y % $nheight;
															if($y % $nheight === 0){
																$hy = ($y - intval($height / 8)) * $nheight + $i;
															}
															imagesetpixel($head, $hx, $hy, imagecolorallocatealpha($head, $r, $g, $b, $a));
														}
													}
												}
												if($x >= ($width / 8) * 5 && $x < ($width / 8) * 6 && $y >= $height / 8 && $y < ($height / 8) * 2){
													$nheight = 64 / ($height / 8);
													$nwidth = 64 / ($width / 8);
													for($i = 0; $i < $nheight; $i++){
														for($j = 0; $j < $nwidth; $j++){
															$hx = (($x - ($width / 8) * 5) % intval($width / 8)) * $nwidth + ($x - ($width / 8) * 5) % $nwidth + $j - $x % $nwidth;
															if($x % $nwidth === 0){
																$hx = ($x - ($width / 8) * 5) * $nwidth + $j;
															}
															$hy = ($y % intval($height / 8)) * $nheight + $y % $nheight + $i - $y % $nheight;
															if($y % $nheight === 0){
																$hy = ($y - intval($height / 8)) * $nheight + $i;
															}
															imagesetpixel($head, $hx, $hy, imagecolorallocatealpha($head, $r, $g, $b, $a));
														}
													}
												}
											}
										}
									}
									imagesavealpha($image, true);
									imagepng($image, $path);
									imagedestroy($image);
									if($head !== false){
										imagesavealpha($head, true);
										imagepng($head, Path::join($this->dataFolder, "players", "head", "$name.png"));
										imagedestroy($head);
									}
								}
							}
							--$this->busy_score;
							break;
					}
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
			$result = igbinary_unserialize($result);
			CosmeticManager::updateHolder($result["name"], $result["skinData"]);
			--$this->busy_score;
		}
	}
}