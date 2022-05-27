<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\training\blockin\settings\player;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeUtil;

class BlockInRolesSetting{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($blockIn = $args[0]) === null){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInBlockIn() && $data !== null && isset($extraData["blockIn"], $extraData["roles"])){
				$extraData["blockIn"]->swapRoles($data, $extraData["roles"]);
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(TextFormat::YELLOW . "Roles " . TextFormat::WHITE . "Settings"));
		$roles = [];
		foreach($blockIn->getTeam1()->getPlayers() as $p){
			if(($p = PlayerManager::getPlayerExact($p)) !== null){
				$roles[$p->getName()] = 0;
				$form->addDropdown(TextFormat::YELLOW . $p->getDisplayName(), ["Attacker", "Defender", "Bow Defender"], 0);
			}
		}
		foreach($blockIn->getTeam2()->getPlayers() as $p){
			if(($p = PlayerManager::getPlayerExact($p)) !== null){
				$roles[$p->getName()] = 1;
				$form->addDropdown(TextFormat::YELLOW . $p->getDisplayName(), ["Attacker", "Defender", "Bow Defender"], 1);
			}
		}
		$form->addExtraData("blockIn", $blockIn);
		$form->addExtraData("roles", $roles);
		$player->sendForm($form);
	}
}
