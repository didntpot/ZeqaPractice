<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\training\blockin\settings;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\display\game\training\blockin\settings\armor\ArmorSelector;
use zodiax\forms\display\game\training\blockin\settings\defense\DefenseSelector;
use zodiax\forms\display\game\training\blockin\settings\player\BlockInPlayerMenu;
use zodiax\forms\types\SimpleForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;

class BlockInSettings{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($blockIn = $args[0]) === null){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInBlockIn() && $data !== null && isset($extraData["blockIn"])){
				switch($data){
					case 0:
						DefenseSelector::onDisplay($player, $extraData["blockIn"]);
						break;
					case 1:
						ArmorSelector::onDisplay($player, $extraData["blockIn"]);
						break;
					case 2:
						BlockInPlayerMenu::onDisplay($player, $extraData["blockIn"]);
						break;
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Block-In " . TextFormat::WHITE . "Settings"));
		$form->setContent("");
		$form->addButton(PracticeCore::COLOR . "Defense " . TextFormat::WHITE . "Selector", 0, "textures/ui/world_glyph_desaturated.png");
		$form->addButton(PracticeCore::COLOR . "Armor " . TextFormat::WHITE . "Selector", 0, "textures/ui/icon_armor.png");
		$form->addButton(PracticeCore::COLOR . "Player " . TextFormat::WHITE . "Manager", 0, "zeqa/textures/ui/more/party_duel.png");
		$form->addExtraData("blockIn", $blockIn);
		$player->sendForm($form);
	}
}
