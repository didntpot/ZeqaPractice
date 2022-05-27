<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\training\blockin\settings\player;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function count;

class BlockInBlackListedForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($blockIn = $args[0]) === null || !isset($args[1]) || ($type = $args[1]) === null){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInBlockIn() && $data !== null && isset($extraData["blockIn"], $extraData["type"], $extraData["players"])){
				$blockIn = $extraData["blockIn"];
				$name = $extraData["players"][$data[0]];
				if($extraData["type"] === "add"){
					$blockIn->addToBlacklist(PlayerManager::getPlayerExact($name, true)?->getName() ?? "");
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully added " . TextFormat::GRAY . $name . TextFormat::GREEN . " to blacklist");
				}else{
					$blockIn->removeFromBlacklist($name);
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully removed " . TextFormat::GRAY . $name . TextFormat::GREEN . " from blacklist");
				}
			}
		});

		$title = $type === "add" ? TextFormat::GREEN . "Add " : TextFormat::RED . "Remove ";
		$form->setTitle(PracticeUtil::formatTitle($title . TextFormat::WHITE . "Settings"));
		if($type === "add"){
			$dropdownArr = [];
			$name = $player->getName();
			foreach(PlayerManager::getOnlinePlayers() as $pName => $p){
				if($pName !== $name && !$blockIn->isBlackListed($p) && !$blockIn->isPlayer($p)){
					$dropdownArr[] = $p->getDisplayName();
				}
			}
		}else{
			$dropdownArr = $blockIn->getBlacklisted();
		}
		if(count($dropdownArr) === 0){
			$label = $type === "add" ? TextFormat::RED . "There are not any players to add to the blacklist" : TextFormat::RED . "There is not anyone blacklisted to block-in match";
			$form->addLabel($label);
		}else{
			$form->addDropdown("Players:", $dropdownArr);
			$form->addExtraData("blockIn", $blockIn);
			$form->addExtraData("players", $dropdownArr);
			$form->addExtraData("type", $type);
		}
		$player->sendForm($form);
	}
}