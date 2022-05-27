<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\training\blockin\settings\player;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\training\misc\BlockInInviteHandler;
use function count;

class BlockInInviteForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($blockIn = $args[0]) === null){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInBlockIn() && $data !== null && isset($extraData["blockIn"], $extraData["players"])){
				$to = PlayerManager::getPlayerExact($name = $extraData["players"][$data[0]], true);
				if($to instanceof Player){
					BlockInInviteHandler::sendInvite($player, $to, $extraData["blockIn"]);
				}else{
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Can not find player $name");
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(TextFormat::GREEN . "Invite " . TextFormat::WHITE . "Player"));
		if(count($dropdownArr = PlayerManager::getListDisplayNames($player->getDisplayName())) > 0){
			$form->addDropdown("Invite to:", $dropdownArr);
			$form->addExtraData("blockIn", $blockIn);
			$form->addExtraData("players", $dropdownArr);
		}else{
			$form->addLabel(TextFormat::RED . "Nobody online");
		}
		$player->sendForm($form);
	}
}