<?php

declare(strict_types=1);

namespace zodiax\forms\display\basic;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;

class RulesInfoForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){

		});

		$form->setTitle("rules");
		$form->setContent("");
		$form->addButton(TextFormat::DARK_GRAY . "Close");
		$player->sendForm($form);
	}
}