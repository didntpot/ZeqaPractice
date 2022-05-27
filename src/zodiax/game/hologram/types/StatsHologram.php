<?php

declare(strict_types=1);

namespace zodiax\game\hologram\types;

use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\world\World;
use zodiax\game\hologram\Hologram;
use zodiax\game\hologram\HologramHandler;
use zodiax\PracticeCore;
use function count;

class StatsHologram extends Hologram{

	public function __construct(Vector3 $vec3, World $world, bool $build){
		parent::__construct($vec3, $world);
		$this->hologramKey = HologramHandler::getHologramKeys(false);
		if($build){
			$this->placeFloatingHologram(false);
		}
	}

	protected function placeFloatingHologram(bool $updateKey = true) : void{
		$key = $this->hologramKey[$this->currentKey];
		$text = HologramHandler::getStatsHologramContentOf($key);
		$numberOfKeys = count($this->hologramKey);
		if($updateKey){
			$this->currentKey = ($this->currentKey + 1);
		}
		if($this->currentKey === $numberOfKeys){
			$this->currentKey = 0;
		}
		$size = count($text);
		if($size <= 0){
			return;
		}
		$key = match ($key) {
			"kills" => "Kills",
			"deaths" => "Deaths",
			"coins" => "Coins",
			"shards" => "Shards",
			"bp" => "BattlePass",
			default => $key
		};
		$title = PracticeCore::COLOR . TextFormat::BOLD . $key . TextFormat::WHITE . " Leaderboards";
		$content = "";
		$count = 1;
		foreach($text as $name => $elo){
			if($count === 11){
				break;
			}
			$content .= "\n" . TextFormat::GRAY . $count . ". " . PracticeCore::COLOR . $name . " " . TextFormat::DARK_GRAY . "(" . TextFormat::WHITE . $elo . TextFormat::DARK_GRAY . ")";
			$count++;
		}
		if($this->floatingText === null){
			$this->floatingText = new FloatingTextParticle($content, $title);
		}else{
			$this->floatingText->setTitle($title);
			$this->floatingText->setText($content);
		}
		$this->world->addParticle($this->vec3, $this->floatingText);
	}
}