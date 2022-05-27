<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\spectate;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\arena\types\FFAArena;
use zodiax\duel\types\BotDuel;
use zodiax\duel\types\PlayerDuel;
use zodiax\forms\types\SimpleForm;
use zodiax\party\duel\PartyDuel;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\training\types\BlockInPractice;
use zodiax\training\types\ClutchPractice;
use zodiax\training\types\ReducePractice;
use function array_merge;
use function count;

class TeleportForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($game = $args[0]) === null){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && ($session->isSpectateArena() || $session->isInSpectate()) && $data !== null && isset($extraData["game"]) && isset($extraData["available"]) && isset($extraData["available"][$data])){
				$game = $extraData["game"];
				$target = $extraData["available"][$data];
				if($target->isOnline() && $game->isPlayer($target)){
					$player->teleport($target->getPosition());
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Player " . TextFormat::WHITE . "Teleporter"));
		$form->setContent("");
		if($game instanceof FFAArena){
			if(count($players = $game->getPlayers()) <= 0){
				$form->addButton(TextFormat::GRAY . "None");
			}else{
				$availables = [];
				foreach($players as $p){
					if(($p = PlayerManager::getPlayerExact($p)) !== null){
						$availables[] = $p;
					}
				}
				if(count($availables) <= 0){
					$form->addButton(TextFormat::GRAY . "None");
				}else{
					foreach($availables as $available){
						$form->addButton(TextFormat::GRAY . $available->getDisplayName());
					}
					$form->addExtraData("game", $game);
					$form->addExtraData("available", $availables);
				}
			}
		}elseif($game instanceof PlayerDuel){
			$players = [$game->getPlayer1(), $game->getPlayer2()];
			$availables = [];
			foreach($players as $p){
				if(($p = PlayerManager::getPlayerExact($p)) !== null){
					$availables[] = $p;
				}
			}
			if(count($availables) <= 0){
				$form->addButton(TextFormat::GRAY . "None");
			}else{
				foreach($availables as $available){
					$form->addButton(TextFormat::GRAY . $available->getDisplayName());
				}
				$form->addExtraData("game", $game);
				$form->addExtraData("available", $availables);
			}
		}elseif($game instanceof BotDuel || $game instanceof ClutchPractice || $game instanceof ReducePractice){
			if(($p = PlayerManager::getPlayerExact($game->getPlayer())) === null){
				$form->addButton(TextFormat::GRAY . "None");
			}else{
				$form->addButton(TextFormat::GRAY . $p->getDisplayName());
				$form->addExtraData("game", $game);
				$form->addExtraData("available", [$p]);
			}
		}elseif($game instanceof PartyDuel || $game instanceof BlockInPractice){
			$players = array_merge($game->getTeam1()->getPlayers(), $game->getTeam2()->getPlayers());
			$availables = [];
			foreach($players as $p){
				if(($p = PlayerManager::getPlayerExact($p)) !== null){
					$availables[] = $p;
				}
			}
			if(count($availables) <= 0){
				$form->addButton(TextFormat::GRAY . "None");
			}else{
				foreach($availables as $available){
					$form->addButton(TextFormat::GRAY . $available->getDisplayName());
				}
				$form->addExtraData("game", $game);
				$form->addExtraData("available", $availables);
			}
		}
		$player->sendForm($form);
	}
}