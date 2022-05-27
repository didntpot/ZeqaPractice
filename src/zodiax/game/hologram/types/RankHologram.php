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

class RankHologram extends Hologram{

	private string $content;

	public function __construct(Vector3 $vec3, World $world, bool $build){
		parent::__construct($vec3, $world);
		$content = [
			TextFormat::GRAY . "Support our server and receive a Rank",
			TextFormat::RESET, TextFormat::GREEN . "Voter " . TextFormat::DARK_GRAY . "- " . TextFormat::WHITE . "Vote for the server", TextFormat::DARK_GRAY . "- " . TextFormat::WHITE . "Every time you vote get 150 coins", TextFormat::DARK_GRAY . "- " . TextFormat::WHITE . "Vote at " . TextFormat::GREEN . "vote.zeqa.net" . TextFormat::WHITE . " then use " . TextFormat::GREEN . "/vote " . TextFormat::WHITE . "to claims rewards",
			TextFormat::RESET, TextFormat::LIGHT_PURPLE . "Booster " . TextFormat::DARK_GRAY . "- " . TextFormat::WHITE . "Boost Zeqa Discord " . TextFormat::LIGHT_PURPLE . "(discord.gg/zeqa)", TextFormat::DARK_GRAY . "- " . TextFormat::YELLOW . "200" . TextFormat::WHITE . " coins, " . TextFormat::AQUA . "20" . TextFormat::WHITE . " shards daily, " . TextFormat::AQUA . "4 " . TextFormat::WHITE . "Special Cosmetics, " . TextFormat::RED . "60 " . TextFormat::WHITE . "mins host cooldown",
			TextFormat::RESET, TextFormat::LIGHT_PURPLE . "Media " . TextFormat::DARK_GRAY . "- " . TextFormat::WHITE . "800 Subs," . TextFormat::DARK_PURPLE . " Famous " . TextFormat::DARK_GRAY . "- " . TextFormat::WHITE . "1,000 Subs", TextFormat::DARK_GRAY . "- " . TextFormat::YELLOW . "200" . TextFormat::WHITE . " coins, " . TextFormat::AQUA . "20" . TextFormat::WHITE . " shards daily, " . TextFormat::AQUA . "4 " . TextFormat::WHITE . "Special Cosmetics, " . TextFormat::RED . "45 " . TextFormat::WHITE . "mins host cooldown",
			TextFormat::RESET, TextFormat::DARK_AQUA . "MVP " . TextFormat::DARK_GRAY . "- " . TextFormat::GREEN . "$7.49", TextFormat::DARK_GRAY . "- " . TextFormat::YELLOW . "600" . TextFormat::WHITE . " coins, " . TextFormat::AQUA . "60" . TextFormat::WHITE . " shards daily, " . TextFormat::AQUA . "8 " . TextFormat::WHITE . "Special Cosmetics, " . TextFormat::RED . "45 " . TextFormat::WHITE . "mins host cooldown",
			TextFormat::RESET, TextFormat::BLUE . "MVP+ " . TextFormat::DARK_GRAY . "- " . TextFormat::GREEN . "$14.99", TextFormat::DARK_GRAY . "- " . TextFormat::YELLOW . "1000" . TextFormat::WHITE . " coins, " . TextFormat::AQUA . "100" . TextFormat::WHITE . " shards daily, " . TextFormat::AQUA . "8 " . TextFormat::WHITE . "Special Cosmetics, " . TextFormat::RED . "30 " . TextFormat::WHITE . "mins host cooldown",
			TextFormat::RESET, TextFormat::RED . "/host " . TextFormat::WHITE . "for " . TextFormat::GREEN . "Voter" . TextFormat::WHITE . ", " . TextFormat::LIGHT_PURPLE . "Booster" . TextFormat::WHITE . ", " . TextFormat::LIGHT_PURPLE . "Media" . TextFormat::WHITE . ", " . TextFormat::DARK_PURPLE . "Famous" . TextFormat::WHITE . ", " . TextFormat::DARK_AQUA . "MVP" . TextFormat::WHITE . ", " . TextFormat::BLUE . "MVP+",
			TextFormat::RESET, TextFormat::RED . "Bypass" . TextFormat::WHITE . " when server " . TextFormat::RED . "fulls " . TextFormat::WHITE . "for " . TextFormat::LIGHT_PURPLE . "Media" . TextFormat::WHITE . ", " . TextFormat::DARK_AQUA . "MVP" . TextFormat::WHITE . ", " . TextFormat::BLUE . "MVP+",
			TextFormat::RESET, TextFormat::GOLD . "Buy ranks at " . TextFormat::DARK_GRAY . "- " . TextFormat::YELLOW . "store.zeqa.net", TextFormat::WHITE . "Hosted by " . TextFormat::RED . "Apex Hosting"
		];
		$this->content = implode("\n", $content);
		if($build){
			$this->placeFloatingHologram(false);
		}
	}

	protected function placeFloatingHologram(bool $updateKey = true) : void{
		$title = TextFormat::BOLD . PracticeCore::COLOR . "Zeqa" . TextFormat::WHITE . " Ranks";
		if($this->floatingText === null){
			$this->floatingText = new FloatingTextParticle($this->content, $title);
		}else{
			$this->floatingText->setTitle($title);
			$this->floatingText->setText($this->content);
		}
		$this->world->addParticle($this->vec3, $this->floatingText);
	}
}