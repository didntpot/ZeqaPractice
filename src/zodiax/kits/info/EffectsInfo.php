<?php

declare(strict_types=1);

namespace zodiax\kits\info;

use pocketmine\entity\effect\EffectInstance;
use zodiax\PracticeUtil;
use function count;
use function is_array;

class EffectsInfo{

	private array $effects;

	public function __construct(array $effects = []){
		$this->effects = $effects;
	}

	public static function decode($data) : EffectsInfo{
		if(is_array($data) && count($data) > 0){
			$effects = [];
			foreach($data as $effectData){
				if(($effect = PracticeUtil::arrToEffect($effectData)) !== null){
					$effects[$effect->getType()->getName()->getText()] = $effect;
				}
			}
			return new EffectsInfo($effects);
		}
		return new EffectsInfo();
	}

	public function addEffect(EffectInstance $instance) : void{
		$this->effects[$instance->getType()->getName()->getText()] = $instance;
	}

	public function removeEffect(EffectInstance $instance) : void{
		if(isset($this->effects[$text = $instance->getType()->getName()->getText()])){
			unset($this->effects[$text]);
		}
	}

	public function getEffects() : array{
		return $this->effects;
	}

	public function export() : array{
		$output = [];
		foreach($this->effects as $effect){
			$output[] = PracticeUtil::effectToArr($effect);
		}
		return $output;
	}
}