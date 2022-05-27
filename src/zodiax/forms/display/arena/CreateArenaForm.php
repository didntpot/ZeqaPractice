<?php

declare(strict_types=1);

namespace zodiax\forms\display\arena;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use zodiax\arena\ArenaManager;
use zodiax\forms\types\CustomForm;
use zodiax\kits\KitsManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function in_array;
use function str_contains;
use function strtoupper;
use function trim;

class CreateArenaForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["worlds"]) && isset($extraData["worlds"][$data[2]]) && isset($extraData["games"]) && isset($extraData["games"][$data[3]]) && isset($extraData["kits"]) && isset($extraData["kits"][$data[4]])){
				$world = Server::getInstance()->getWorldManager()->getWorldByName($extraData["worlds"][$data[2]]);
				if($world === null){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Can not find $data[2] as a world");
					return true;
				}

				$kitName = $extraData["kits"][$data[4]];
				$kit = KitsManager::getKit($kitName);
				if($kit === null){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "$kitName does not exist");
					return true;
				}

				$game = strtoupper($extraData["games"][$data[3]]);
				if(!in_array($game, [ArenaManager::FFA, ArenaManager::DUEL, ArenaManager::BOT, ArenaManager::EVENT, ArenaManager::TRAINING, ArenaManager::BLOCK_IN], true)){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Unknown arena's type");
					return true;
				}
				if($game === ArenaManager::FFA && !$kit->getMiscKitInfo()->isFFAEnabled()){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "$kitName kit disabled FFA");
					return true;
				}elseif($game === ArenaManager::DUEL && !$kit->getMiscKitInfo()->isDuelsEnabled()){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "$kitName kit disabled Duel");
					return true;
				}elseif($game === ArenaManager::BOT && !$kit->getMiscKitInfo()->isBotEnabled()){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "$kitName kit disabled Bot");
					return true;
				}elseif($game === ArenaManager::EVENT && !$kit->getMiscKitInfo()->isEventEnabled()){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "$kitName kit disabled Event");
					return true;
				}elseif(($game === ArenaManager::TRAINING || $game === ArenaManager::BLOCK_IN) && !$kit->getMiscKitInfo()->isTrainingEnabled()){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "$kitName kit disabled Training");
					return true;
				}

				$arenaName = trim(TextFormat::clean($data[1]));
				if(str_contains($arenaName, " ")){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Name should not have space");
					return true;
				}

				$arena = ArenaManager::getArena($arenaName);
				if($arena !== null){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "$arenaName already exists . ");
					return true;
				}

				if(ArenaManager::createArena($arenaName, $kit, $world, $game)){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully created a new arena called $arenaName");
				}else{
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Can not create a new arena");
				}
			}
			return true;
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Create " . TextFormat::WHITE . "Arena"));
		$form->addLabel("This creates a new arena from the given name");
		$form->addInput("Please provide the name of the arena that you want to create:");
		$worlds = [];
		foreach(Server::getInstance()->getWorldManager()->getWorlds() as $world){
			$worlds[] = $world->getDisplayName();
		}
		$form->addDropdown("Please provide the name of the arena's world:", $worlds);
		$games = ["FFA", "Duel", "Bot", "Event", "Training", "BlockIn"];
		$form->addDropdown("Please provide the type of the arena:", $games);
		$kits = KitsManager::getKits(true);
		$form->addDropdown("Please provide the kit of the arena:", $kits);
		$form->addExtraData("worlds", $worlds);
		$form->addExtraData("games", $games);
		$form->addExtraData("kits", $kits);
		$player->sendForm($form);
	}
}