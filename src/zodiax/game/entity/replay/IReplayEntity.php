<?php

declare(strict_types=1);

namespace zodiax\game\entity\replay;

interface IReplayEntity{

	public function setPaused(bool $paused) : void;

	public function isPaused() : bool;
}
