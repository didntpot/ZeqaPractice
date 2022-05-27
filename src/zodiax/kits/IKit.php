<?php

declare(strict_types=1);

namespace zodiax\kits;

use zodiax\game\behavior\kits\IKitHolderEntity;
use zodiax\kits\info\EffectsInfo;
use zodiax\kits\info\KnockbackInfo;
use zodiax\kits\info\MiscKitInfo;

interface IKit{

	public function giveTo(IKitHolderEntity $entity) : bool;

	public function getEffectsInfo() : EffectsInfo;

	public function getKnockbackInfo() : KnockbackInfo;

	public function getMiscKitInfo() : MiscKitInfo;

	public function getName() : string;

	public function getLocalName() : string;

	public function equals($kit) : bool;

	public function export() : array;
}