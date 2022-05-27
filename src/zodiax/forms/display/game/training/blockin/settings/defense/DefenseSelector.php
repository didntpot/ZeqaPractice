<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\training\blockin\settings\defense;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\training\misc\DefenseGenerator;

class DefenseSelector{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($blockIn = $args[0]) === null){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInBlockIn() && $data !== null){
				if(isset(DefenseGenerator::DEFENSES_LIST[$data])){
					if($extraData["blockIn"]){
						BlockSelector::onDisplay($player, $extraData["blockIn"], $data);
					}
				}else{
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::GRAY . "Want more defense patterns? Feel free to join our discord and suggest it!");
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Defense " . TextFormat::WHITE . "Selector"));
		$form->setContent("");
		foreach(DefenseGenerator::DEFENSES_LIST as $defense){
			$form->addButton(TextFormat::GRAY . $defense, 0, "");
		}
		$form->addButton(TextFormat::GRAY . "Custom", 0, "");
		$form->addExtraData("blockIn", $blockIn);
		$player->sendForm($form);
	}
}
