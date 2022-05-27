<?php

declare(strict_types=1);

namespace zodiax\data\log\thread;

use pocketmine\thread\Thread;
use RuntimeException;
use Threaded;
use zodiax\PracticeCore;
use function date;
use function fclose;
use function fopen;
use function fwrite;
use function gc_collect_cycles;
use function gc_enable;
use function gc_mem_caches;
use function igbinary_serialize;
use function igbinary_unserialize;
use function is_resource;
use function sprintf;
use function touch;

class LogThread extends Thread{

	const DEBUG = 0;
	const CHAT = 1;
	const COSMETIC = 2;

	public int $busy_score = 0;
	private array $logFiles;
	private bool $timestamp;
	private Threaded $buffer;
	private bool $running;

	public function __construct(array $logFiles, bool $timestamp = true){
		$this->logFiles = igbinary_serialize($logFiles);
		$this->timestamp = $timestamp;
		$this->buffer = new Threaded();
		foreach($logFiles as $logFile){
			touch($logFile);
		}
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

	public function write(int $type, string $buffer) : void{
		$this->synchronized(function() use ($type, $buffer) : void{
			$this->buffer[] = igbinary_serialize(["type" => $type, "buffer" => $buffer]);
			++$this->busy_score;
			$this->notifyOne();
		});
	}

	public function dailyChatLog() : void{
		$this->synchronized(function() : void{
			$this->buffer[] = igbinary_serialize(["daily_chat_log" => ["username" => PracticeCore::NAME, "avatar_url" => PracticeCore::getLogoInfo()], "url" => PracticeCore::getWebhookInfo()["chat"], "region" => PracticeCore::getRegionInfo()]);
			$this->notifyOne();
		});
	}

	public function triggerGarbageCollector() : void{
		$this->synchronized(function() : void{
			$this->buffer[] = igbinary_serialize("garbage_collector");
			$this->notifyOne();
		});
	}

	public function onRun() : void{
		while($this->running){
			while(($data = $this->buffer->shift()) !== null){
				$data = igbinary_unserialize($data);
				if($data === "garbage_collector"){
					gc_collect_cycles();
					gc_mem_caches();
					gc_enable();
				}else{
					if(isset($data["daily_chat_log"], $data["url"], $data["region"])){
						touch($chat = igbinary_unserialize($this->logFiles)[LogThread::CHAT]);
						$now = date("F d Y H:i:s");
						if(is_resource($chatLog = fopen($chat, "a+"))){
							if(!is_string($created = fgets($chatLog)) || !str_contains($created, "Start: ")){
								fwrite($chatLog, "Start: Unknown\n");
								$created = "Unknown";
							}
							$contents = $data["daily_chat_log"];
							$contents["content"] = "({$data["region"]}) " . str_replace("\n", "", str_replace("Start: ", "", $created)) . " to " . $now;
							fwrite($chatLog, "End: $now\n");
							fclose($chatLog);
							$contents["file"] = curl_file_create($chat, null, "{$data["region"]}.log");

							$curl = curl_init($data["url"]);
							curl_setopt($curl, CURLOPT_TIMEOUT, 10);
							curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
							curl_setopt($curl, CURLOPT_POST, 1);
							curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type: multipart/form-data"]);
							curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
							curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
							curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
							curl_setopt($curl, CURLOPT_POSTFIELDS, $contents);
							curl_exec($curl);
							curl_close($curl);
						}
						file_put_contents($chat, "Start: $now\n");
					}else{
						$logFiles = igbinary_unserialize($this->logFiles);
						if(!is_resource($logResource = fopen($logFiles[$data["type"]], "ab"))){
							throw new RuntimeException("Cannot open log file");
						}
						$line = $data["buffer"];
						if($this->timestamp){
							$line = sprintf("[%s]: %s", date("F d Y H:i:s"), $line);
						}
						fwrite($logResource, $line . "\n");
						fclose($logResource);
						--$this->busy_score;
					}
				}
			}
			$this->sleep();
		}
	}
}