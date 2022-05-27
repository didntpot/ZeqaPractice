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

class BlockInKickForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($blockIn = $args[0]) === null){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInBlockIn() && $data !== null && isset($extraData["blockIn"], $extraData["players"])){
				$target = PlayerManager::getPlayerExact($name = $extraData["players"][$data[0]], true);
				if($target instanceof Player){
					$blockIn = $extraData["blockIn"];
					if($blockIn->isPlayer($target)){
						$blockIn->removePlayer($target, (bool) $data[1]);
						$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully kicked " . TextFormat::GRAY . $name . TextFormat::GREEN . " from block-in match");
						$target->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You have been kicked from block-in match");
					}else{
						$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . $name . TextFormat::GRAY . " is no longer in block-in match");
					}
				}else{
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Can not find player $name");
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(TextFormat::RED . "Kick " . TextFormat::WHITE . "Player"));
		$name = $player->getName();
		$dropdownArr = [];
		foreach($blockIn->getTeam1()->getPlayers() as $p){
			if($p !== $name){
				$dropdownArr[] = PlayerManager::getPlayerExact($p)?->getDisplayName();
			}
		}
		foreach($blockIn->getTeam2()->getPlayers() as $p){
			if($p !== $name){
				$dropdownArr[] = PlayerManager::getPlayerExact($p)?->getDisplayName();
			}
		}
		if(count($dropdownArr) > 0){
			$form->addDropdown(PracticeCore::COLOR . "Players:", $dropdownArr);
			$form->addToggle(TextFormat::RED . "Add to Blacklist:", false);
			$form->addExtraData("blockIn", $blockIn);
			$form->addExtraData("players", $dropdownArr);
		}else{
			$form->addLabel(TextFormat::RED . "Nobody in block-in match");
		}
		$player->sendForm($form);
	}
}