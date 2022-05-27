<?php

declare(strict_types=1);

namespace zodiax\forms\display\arena;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\arena\types\BlockInArena;
use zodiax\arena\types\DuelArena;
use zodiax\arena\types\EventArena;
use zodiax\arena\types\FFAArena;
use zodiax\forms\types\SimpleForm;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function implode;

class ViewArenaForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($arena = $args[0]) === null){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){

		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Arena " . TextFormat::WHITE . "Info"));
		$content = [];
		if($arena instanceof FFAArena){
			$content = ["Arena: " . $arena->getName(), "", "Kits: " . $arena->getKit()?->getName() ?? "", "", "World: " . $arena->getWorld()?->getFolderName() ?? "", "", "Interrupt: " . ($arena->canInterrupt() ? "True" : "False")];
		}elseif($arena instanceof DuelArena){
			$content = ["Arena: " . $arena->getName(), "", "Kits: " . implode(",", $arena->getKits()), "", "World: " . $arena->getWorld()];
		}elseif($arena instanceof EventArena){
			$content = ["Arena: " . $arena->getName(), "", "Kits: " . $arena->getKit()?->getName() ?? "", "", "World: " . $arena->getWorld()?->getFolderName() ?? ""];
		}elseif($arena instanceof BlockInArena){
			$content = ["Arena: " . $arena->getName(), "", "Attacker Kit: " . $arena->getAttackerKit()?->getName() ?? "", "Defender Kit: " . $arena->getDefenderKit()?->getName() ?? "", "", "World: " . $arena->getWorld()];
		}
		$form->setContent(implode("\n", $content));
		$form->addButton(TextFormat::BOLD . "Submit");
		$player->sendForm($form);
	}
}