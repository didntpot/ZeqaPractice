<?php

declare(strict_types=1);

namespace zodiax\utils;

use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use function is_float;
use function max;
use function min;

class Math{

	/**
	 * Returns value if greater than minValue, otherwise return minValue.
	 */
	public static function floor(int|float $value, int|float $minValue) : int|float{
		return max($value, $minValue);
	}

	/**
	 * Returns value if less than maxValue, otherwise return maxValue.
	 */
	public static function ceil(int|float $value, int|float $maxValue) : int|float{
		return min($value, $maxValue);
	}

	/**
	 * Clamps the value between a minimum and a maximum.
	 */
	public static function clamp(int|float $clampValue, int|float $min, int|float $max) : int|float{
		if($clampValue <= $min){
			return $min;
		}
		if($clampValue >= $max){
			return $max;
		}
		return $clampValue;
	}

	/**
	 * The dot product between two vector3s.
	 * -> If value == 1, then b is parallel to a in same direction.
	 * -> If value == -1, then b is parallel to a in opposite direction.
	 *
	 * @param Vector3 $a - Direction vector a.
	 * @param Vector3 $b - Direction vector b.
	 */
	public static function dot(Vector3 $a, Vector3 $b) : float{
		return $a->x * $b->x + $a->y * $b->y + $a->z * $b->z;
	}

	/**
	 * Linearly interpolates between a & b by the alpha.
	 *
	 * @param float|Location|Vector3 $a - The a value.
	 * @param float|Location|Vector3 $b - The b value.
	 * @param float                  $alpha - The alpha we are interpolating by.
	 *
	 * returns null if unsuccessful.
	 */
	public static function lerp(Vector3|Location|float $a, Vector3|Location|float $b, float $alpha) : Vector3|Location|float|int|null{
		if(is_float($a) && is_float($b)){
			return $a + ($b - $a) * $alpha;
		}
		if($a instanceof Vector3 && $b instanceof Vector3){
			$newX = self::lerp($a->x, $b->x, $alpha);
			$newY = self::lerp($a->y, $b->y, $alpha);
			$newZ = self::lerp($a->z, $b->z, $alpha);
			if($a instanceof Location && $b instanceof Location){
				$yaw = self::lerp($a->yaw, $b->yaw, $alpha);
				$pitch = self::lerp($a->pitch, $b->pitch, $alpha);
				return new Location($newX, $newY, $newZ, $a->getWorld(), $yaw, $pitch);
			}
			return new Vector3($newX, $newY, $newZ);
		}
		return null;
	}
}