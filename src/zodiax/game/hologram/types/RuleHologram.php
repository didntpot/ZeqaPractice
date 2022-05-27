<?php

declare(strict_types=1);

namespace zodiax\game\hologram\types;

use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\world\World;
use zodiax\game\hologram\Hologram;
use zodiax\PracticeCore;
use function implode;

class RuleHologram extends Hologram{

	private string $content;

	public function __construct(Vector3 $vec3, World $world, bool $build){
		parent::__construct($vec3, $world);
		$content = [
			TextFormat::WHITE . "You can read our Network rules"
			, TextFormat::WHITE . "by typing " . PracticeCore::COLOR . "/rules" . TextFormat::WHITE . " in chat"
		];
		$this->content = implode("\n", $content);
		if($build){
			$this->placeFloatingHologram(false);
		}
	}

	protected function placeFloatingHologram(bool $updateKey = true) : void{
		$title = TextFormat::BOLD . PracticeCore::COLOR . "RULES";
		if($this->floatingText === null){
			$this->floatingText = new FloatingTextParticle($this->content, $title);
		}else{
			$this->floatingText->setTitle($title);
			$this->floatingText->setText($this->content);
		}
		$this->world->addParticle($this->vec3, $this->floatingText);
	}
}