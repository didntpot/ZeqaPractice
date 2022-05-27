<?php

declare(strict_types=1);

namespace zodiax\training\misc;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class DefenseGenerator{

	const ONE_LAYER = 0;
	const TWO_LAYERS = 1;
	const THREE_LAYERS = 2;
	const SQUARE_PYRAMID = 3;
	const HADES = 4;

	const DEFENSES_LIST = [
		self::ONE_LAYER => "One Layer",
		self::TWO_LAYERS => "Two Layers",
		self::THREE_LAYERS => "Three Layers",
		self::SQUARE_PYRAMID => "Square Pyramid",
		self::HADES => "Hades"
	];

	public static function generateDefenseByType(int $type, Position $mid, Block $first, ?Block $second = null, ?Block $third = null, ?Block $layer = null) : void{
		self::clear($mid);
		match ($type) {
			self::ONE_LAYER => self::generatePyramid($mid, [1 => $first]),
			self::TWO_LAYERS => self::generatePyramid($mid, [1 => $first, 2 => $second]),
			self::THREE_LAYERS => self::generatePyramid($mid, [1 => $first, 2 => $second, 3 => $third]),
			self::SQUARE_PYRAMID => self::generateSquarePyramid($mid, [1 => $first, 2 => $second, 3 => $third], $layer),
			self::HADES => self::generateHades($mid, [1 => $first, 2 => $second, 3 => $third], $layer),
		};
	}

	public static function clear(Position $mid) : void{
		$world = $mid->getWorld();
		$air = VanillaBlocks::AIR();
		for($i = 0; $i <= 3; $i++){
			for($j = -3; $j <= 3; $j++){
				for($k = -3; $k <= 3; $k++){
					$world->setBlock($mid->add($j, $i, $k), $air, false);
				}
			}
		}
	}

	private static function generatePyramid(Position $mid, array $blocks) : void{
		$world = $mid->getWorld();
		foreach($blocks as $layer => $block){
			foreach(self::getAllSide($mid, $layer) as $vector3){
				$world->setBlock($vector3, $block, false);
			}
		}
	}

	private static function generateSquarePyramid(Position $mid, array $blocks, Block $layer) : void{
		self::generatePyramid($mid, $blocks);
		foreach(self::getSquareLayer($mid) as $vector3){
			$mid->getWorld()->setBlock($vector3, $layer, false);
		}
	}

	private static function generateHades(Position $mid, array $blocks, Block $layer) : void{
		self::generatePyramid($mid, $blocks);
		foreach(self::getHadesLayer($mid) as $vector3){
			$mid->getWorld()->setBlock($vector3, $layer, false);
		}
	}

	//Hardcoded Stuff
	private static function getAllSide(Vector3 $vector3, int $layer = 1) : array{
		return match ($layer) {
			1 => [
				$vector3->add(0, 1, 0),
				$vector3->add(0, 0, -1),
				$vector3->add(0, 0, 1),
				$vector3->add(-1, 0, 0),
				$vector3->add(1, 0, 0),
			],
			2 => [
				$vector3->add(0, 2, 0),
				$vector3->add(0, 0, -2),
				$vector3->add(0, 0, 2),
				$vector3->add(-2, 0, 0),
				$vector3->add(2, 0, 0),

				$vector3->add(-1, 0, -1),
				$vector3->add(1, 0, -1),
				$vector3->add(-1, 0, 1),
				$vector3->add(1, 0, 1),

				$vector3->add(0, 1, -1),
				$vector3->add(0, 1, 1),
				$vector3->add(-1, 1, 0),
				$vector3->add(1, 1, 0),
			],
			3 => [
				$vector3->add(0, 3, 0),
				$vector3->add(0, 0, -3),
				$vector3->add(0, 0, 3),
				$vector3->add(-3, 0, 0),
				$vector3->add(3, 0, 0),

				$vector3->add(-1, 0, -2),
				$vector3->add(1, 0, -2),
				$vector3->add(-1, 0, 2),
				$vector3->add(1, 0, 2),
				$vector3->add(-2, 0, -1),
				$vector3->add(2, 0, -1),
				$vector3->add(-2, 0, 1),
				$vector3->add(2, 0, 1),

				$vector3->add(0, 1, -2),
				$vector3->add(0, 1, 2),
				$vector3->add(-2, 1, 0),
				$vector3->add(2, 1, 0),
				$vector3->add(1, 1, -1),
				$vector3->add(1, 1, 1),
				$vector3->add(-1, 1, -1),
				$vector3->add(-1, 1, 1),

				$vector3->add(0, 2, -1),
				$vector3->add(0, 2, 1),
				$vector3->add(-1, 2, 0),
				$vector3->add(1, 2, 0),
			],
			default => [],
		};
	}

	private static function getSquareLayer(Vector3 $vector3) : array{
		return [
			$vector3->add(2, 0, 2),
			$vector3->add(2, 0, -2),
			$vector3->add(-2, 0, 2),
			$vector3->add(-2, 0, -2),

			$vector3->add(3, 0, 1),
			$vector3->add(3, 0, 2),
			$vector3->add(3, 0, -1),
			$vector3->add(3, 0, -2),
			$vector3->add(-3, 0, 1),
			$vector3->add(-3, 0, 2),
			$vector3->add(-3, 0, -1),
			$vector3->add(-3, 0, -2),
			$vector3->add(1, 0, 3),
			$vector3->add(2, 0, 3),
			$vector3->add(-1, 0, 3),
			$vector3->add(-2, 0, 3),
			$vector3->add(1, 0, -3),
			$vector3->add(2, 0, -3),
			$vector3->add(-1, 0, -3),
			$vector3->add(-2, 0, -3),

			$vector3->add(3, 0, 3),
			$vector3->add(3, 0, -3),
			$vector3->add(-3, 0, 3),
			$vector3->add(-3, 0, -3),

			$vector3->add(2, 1, 1),
			$vector3->add(2, 1, -1),
			$vector3->add(-2, 1, 1),
			$vector3->add(-2, 1, -1),
			$vector3->add(1, 1, 2),
			$vector3->add(-1, 1, 2),
			$vector3->add(1, 1, -2),
			$vector3->add(-1, 1, -2),

			$vector3->add(2, 1, 2),
			$vector3->add(2, 1, -2),
			$vector3->add(-2, 1, 2),
			$vector3->add(-2, 1, -2),

			$vector3->add(1, 2, 1),
			$vector3->add(1, 2, -1),
			$vector3->add(-1, 2, 1),
			$vector3->add(-1, 2, -1),
		];
	}

	private static function getHadesLayer(Vector3 $vector3) : array{
		return [
			$vector3->add(0, 1, 3),
			$vector3->add(0, 1, -3),
			$vector3->add(3, 1, 0),
			$vector3->add(-3, 1, 0),

			$vector3->add(1, 1, 2),
			$vector3->add(1, 1, -2),
			$vector3->add(-1, 1, 2),
			$vector3->add(-1, 1, -2),
			$vector3->add(2, 1, 1),
			$vector3->add(2, 1, -1),
			$vector3->add(-2, 1, 1),
			$vector3->add(-2, 1, -1),

			$vector3->add(0, 2, 2),
			$vector3->add(0, 2, -2),
			$vector3->add(2, 2, 0),
			$vector3->add(-2, 2, 0),

			$vector3->add(1, 2, 1),
			$vector3->add(1, 2, -1),
			$vector3->add(-1, 2, 1),
			$vector3->add(-1, 2, -1),
		];
	}
}
