<?php

declare(strict_types=1);

namespace zodiax\forms\display\arena\edit\kit;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\arena\types\BlockInArena;
use zodiax\arena\types\DuelArena;
use zodiax\arena\types\EventArena;
use zodiax\arena\types\FFAArena;
use zodiax\arena\types\TrainingArena;
use zodiax\forms\types\SimpleForm;
use zodiax\kits\KitsManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function array_diff;
use function array_values;
use function count;

class ManageKitForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($arena = $args[0]) === null){
			return;
		}
		if(!isset($args[1]) || ($type = $args[1]) === null){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["arena"]) && isset($extraData["type"]) && isset($extraData["kits"]) && isset($extraData["kits"][$data])){
				$arena = $extraData["arena"];
				$type = $extraData["type"];
				$kit = $extraData["kits"][$data];
				if($type === "add" && count($extraData["kits"]) > 0 && ($arena instanceof DuelArena || $arena instanceof TrainingArena)){
					$kits = $arena->getKits();
					if(!isset($kit, $kits)){
						$kits[$kit] = $kit;
						$arena->setKits($kits);
						$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully added {$arena->getName()}'s kit");
					}
				}elseif($type === "manage" && count($extraData["kits"]) > 0 && $arena instanceof FFAArena){
					$arena->setKit(KitsManager::getKit($kit));
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully edited {$arena->getName()}'s kit");
				}elseif($type === "manage" && count($extraData["kits"]) > 0 && $arena instanceof EventArena){
					$arena->setKit(KitsManager::getKit($kit));
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully edited {$arena->getName()}'s kit");
				}elseif($type === "delete" && count($extraData["kits"]) > 1 && ($arena instanceof DuelArena || $arena instanceof TrainingArena)){
					$kits = $arena->getKits();
					if(isset($kit, $kits)){
						unset($kits[$kit]);
						$arena->setKits($kits);
						$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully deleted {$arena->getName()}'s kit");
					}
				}elseif($arena instanceof BlockInArena && count($extraData["kits"]) > 0){
					if($type === "attacker"){
						$arena->setAttackerKit(KitsManager::getKit($kit));
						$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully edited {$arena->getName()}'s attacker kit");
					}else{
						$arena->setDefenderKit(KitsManager::getKit($kit));
						$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully edited {$arena->getName()}'s defender kit");
					}
				}
			}
		});

		$kits = [];
		$title = ($type === "add" ? "Add " : ($type === "manage" ? "Manage " : "Delete "));
		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . $title . TextFormat::WHITE . "Kit"));
		$form->setContent("Choose whether to $type the {$arena->getName()}'s kits");
		if($type === "add"){
			$except = $arena->getKits();
			$duels = [];
			if($arena instanceof DuelArena){
				foreach(KitsManager::getDuelKits() as $duel){
					$duels[] = $duel->getName();
				}
			}elseif($arena instanceof TrainingArena){
				foreach(KitsManager::getTrainingKits() as $duel){
					$duels[] = $duel->getName();
				}
			}
			$kits = array_values(array_diff($duels, $except));
			if(count($kits) <= 0){
				$form->addButton(TextFormat::GRAY . "None");
			}else{
				foreach($kits as $kit){
					$form->addButton($kit, 0, KitsManager::getKit($kit)?->getMiscKitInfo()->getTexture() ?? "");
				}
			}
		}elseif($type === "manage" && $arena instanceof FFAArena){
			$kits = KitsManager::getFFAKits(true);
			if(count($kits) === 0){
				$form->addButton(TextFormat::GRAY . "None");
			}else{
				foreach($kits as $kit){
					$form->addButton($kit, 0, KitsManager::getKit($kit)?->getMiscKitInfo()->getTexture() ?? "");
				}
			}
		}elseif($type === "manage" && $arena instanceof EventArena){
			$kits = KitsManager::getEventKits(true);
			if(count($kits) === 0){
				$form->addButton(TextFormat::GRAY . "None");
			}else{
				foreach($kits as $kit){
					$form->addButton($kit, 0, KitsManager::getKit($kit)?->getMiscKitInfo()->getTexture() ?? "");
				}
			}
		}elseif($type === "delete" && ($arena instanceof DuelArena || $arena instanceof TrainingArena)){
			$kits = $arena->getKits();
			if(count($kits) <= 1){
				$form->addButton(TextFormat::GRAY . "None");
			}else{
				foreach($kits as $kit){
					$form->addButton($kit, 0, KitsManager::getKit($kit)?->getMiscKitInfo()->getTexture() ?? "");
				}
			}
		}elseif($arena instanceof BlockInArena){
			foreach(KitsManager::getKits() as $kit){
				if($kit->getMiscKitInfo()->isTrainingEnabled()){
					$kits[] = $kit->getName();
				}
			}
			if(count($kits) === 0){
				$form->addButton(TextFormat::GRAY . "None");
			}else{
				foreach($kits as $kit){
					$form->addButton($kit, 0, KitsManager::getKit($kit)?->getMiscKitInfo()->getTexture() ?? "");
				}
			}
		}
		$form->addExtraData("arena", $arena);
		$form->addExtraData("kits", $kits);
		$form->addExtraData("type", $type);
		$player->sendForm($form);
	}
}