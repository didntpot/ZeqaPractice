<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\training\blockin\settings\armor;

use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function array_search;

class ArmorSelector{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($blockIn = $args[0]) === null){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInBlockIn() && $data !== null && isset($extraData["blockIn"])){
				$helmet = [VanillaItems::AIR(), VanillaItems::CHAINMAIL_HELMET(), VanillaItems::IRON_HELMET(), VanillaItems::DIAMOND_HELMET()];
				$chestplate = [VanillaItems::AIR(), VanillaItems::CHAINMAIL_CHESTPLATE(), VanillaItems::IRON_CHESTPLATE(), VanillaItems::DIAMOND_CHESTPLATE()];
				$leggings = [VanillaItems::AIR(), VanillaItems::CHAINMAIL_LEGGINGS(), VanillaItems::IRON_LEGGINGS(), VanillaItems::DIAMOND_LEGGINGS()];
				$boots = [VanillaItems::AIR(), VanillaItems::CHAINMAIL_BOOTS(), VanillaItems::IRON_BOOTS(), VanillaItems::DIAMOND_BOOTS()];
				$extraData["blockIn"]->setArmorSettings([$helmet[$data[1]], $chestplate[$data[2]], $leggings[$data[3]], $boots[$data[4]]], [$helmet[$data[6]], $chestplate[$data[7]], $leggings[$data[8]], $boots[$data[9]]]);
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Armor " . TextFormat::WHITE . "Selector"));
		[$attackers, $defenders] = $blockIn->getArmorSettings();
		$helmet = [VanillaItems::AIR()->getName(), VanillaItems::CHAINMAIL_HELMET()->getName(), VanillaItems::IRON_HELMET()->getName(), VanillaItems::DIAMOND_HELMET()->getName()];
		$chestplate = [VanillaItems::AIR()->getName(), VanillaItems::CHAINMAIL_CHESTPLATE()->getName(), VanillaItems::IRON_CHESTPLATE()->getName(), VanillaItems::DIAMOND_CHESTPLATE()->getName()];
		$leggings = [VanillaItems::AIR()->getName(), VanillaItems::CHAINMAIL_LEGGINGS()->getName(), VanillaItems::IRON_LEGGINGS()->getName(), VanillaItems::DIAMOND_LEGGINGS()->getName()];
		$boots = [VanillaItems::AIR()->getName(), VanillaItems::CHAINMAIL_BOOTS()->getName(), VanillaItems::IRON_BOOTS()->getName(), VanillaItems::DIAMOND_BOOTS()->getName()];
		$form->addLabel(PracticeCore::COLOR . "Attackers" . TextFormat::WHITE . " Armor");
		$form->addDropdown(TextFormat::GRAY . "Helmet", $helmet, array_search($attackers[0]->getName(), $helmet, true));
		$form->addDropdown(TextFormat::GRAY . "Chestplate", $chestplate, array_search($attackers[1]->getName(), $chestplate, true));
		$form->addDropdown(TextFormat::GRAY . "Leggings", $leggings, array_search($attackers[2]->getName(), $leggings, true));
		$form->addDropdown(TextFormat::GRAY . "Boots", $boots, array_search($attackers[3]->getName(), $boots, true));
		$form->addLabel(PracticeCore::COLOR . "Defenders" . TextFormat::WHITE . " Armor");
		$form->addDropdown(TextFormat::GRAY . "Helmet", $helmet, array_search($defenders[0]->getName(), $helmet, true));
		$form->addDropdown(TextFormat::GRAY . "Chestplate", $chestplate, array_search($defenders[1]->getName(), $chestplate, true));
		$form->addDropdown(TextFormat::GRAY . "Leggings", $leggings, array_search($defenders[2]->getName(), $leggings, true));
		$form->addDropdown(TextFormat::GRAY . "Boots", $boots, array_search($defenders[3]->getName(), $boots, true));
		$form->addExtraData("blockIn", $blockIn);
		$player->sendForm($form);
	}
}
