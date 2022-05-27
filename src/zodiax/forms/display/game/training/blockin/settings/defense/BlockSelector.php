<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\training\blockin\settings\defense;

use pocketmine\block\VanillaBlocks;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\training\misc\DefenseGenerator;

class BlockSelector{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($blockIn = $args[0]) === null || !isset($args[1]) || ($defenseType = $args[1]) === null){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInBlockIn() && $data !== null && isset($extraData["blockIn"], $extraData["defenseType"])){
				$blocks = [VanillaBlocks::WOOL(), VanillaBlocks::OAK_PLANKS(), VanillaBlocks::END_STONE()];
				$first = $blocks[$data[0]];
				$second = null;
				if(isset($data[1])){
					$second = $blocks[$data[1]];
				}
				$third = null;
				if(isset($data[2])){
					$third = $blocks[$data[2]];
				}
				$layer = null;
				if(isset($data[3])){
					$layer = $blocks[$data[3]];
				}
				$extraData["blockIn"]->setDefenseType($extraData["defenseType"], $first, $second, $third, $layer);
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Block " . TextFormat::WHITE . "Selector"));
		$blocks = [VanillaBlocks::WOOL()->getName(), VanillaBlocks::OAK_PLANKS()->getName(), VanillaBlocks::END_STONE()->getName()];
		switch($defenseType){
			case DefenseGenerator::ONE_LAYER:
				$form->addDropdown(TextFormat::GRAY . "First Layer", $blocks, 0);
				break;
			case DefenseGenerator::TWO_LAYERS:
				$form->addDropdown(TextFormat::GRAY . "First Layer", $blocks, 0);
				$form->addDropdown(TextFormat::GRAY . "Second Layer", $blocks, 0);
				break;
			case DefenseGenerator::THREE_LAYERS:
				$form->addDropdown(TextFormat::GRAY . "First Layer", $blocks, 0);
				$form->addDropdown(TextFormat::GRAY . "Second Layer", $blocks, 0);
				$form->addDropdown(TextFormat::GRAY . "Three Layer", $blocks, 0);
				break;
			case DefenseGenerator::HADES:
			case DefenseGenerator::SQUARE_PYRAMID:
				$form->addDropdown(TextFormat::GRAY . "First Layer", $blocks, 0);
				$form->addDropdown(TextFormat::GRAY . "Second Layer", $blocks, 0);
				$form->addDropdown(TextFormat::GRAY . "Three Layer", $blocks, 0);
				$form->addDropdown(TextFormat::GRAY . "Square Layer", $blocks, 0);
				break;
		}
		$form->addExtraData("blockIn", $blockIn);
		$form->addExtraData("defenseType", $defenseType);
		$player->sendForm($form);
	}
}
