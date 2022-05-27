<?php

declare(strict_types=1);

namespace zodiax\event;

use pocketmine\player\Player;
use zodiax\arena\ArenaManager;
use zodiax\arena\types\EventArena;
use zodiax\misc\AbstractRepeatingTask;

class EventHandler extends AbstractRepeatingTask{

	private static array $activeEvents = [];

	public function __construct(){
		parent::__construct();
	}

	public static function addEvent(EventArena $arena) : void{
		self::$activeEvents[$arena->getName()] = new PracticeEvent($arena);
	}

	public static function removeEvent(EventArena $arena) : void{
		unset(self::$activeEvents[$arena->getName()]);
	}

	public static function getEvents() : array{
		$result = [];
		foreach(self::$activeEvents as $event){
			if(ArenaManager::getArena($event->getArena())?->getKit()?->getMiscKitInfo()->isEventEnabled()){
				$result[] = $event;
			}
		}
		return $result;
	}

	public static function getEventFromPlayer(string|Player $player) : ?PracticeEvent{
		foreach(self::$activeEvents as $event){
			if($event->isPlayer($player)){
				return $event;
			}
		}
		return null;
	}

	public static function getEventFromSpec(string|Player $player) : ?PracticeEvent{
		foreach(self::$activeEvents as $event){
			if($event->isSpectator($player)){
				return $event;
			}
		}
		return null;
	}

	public static function getEventFromArena(string $name) : ?PracticeEvent{
		return self::$activeEvents[$name] ?? null;
	}

	protected function onUpdate(int $tickDifference) : void{
		foreach(self::$activeEvents as $event){
			$event->update();
		}
	}
}
