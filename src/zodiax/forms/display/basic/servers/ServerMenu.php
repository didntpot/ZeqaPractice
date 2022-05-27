<?php

declare(strict_types=1);

namespace zodiax\forms\display\basic\servers;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\Form;
use zodiax\forms\types\SimpleForm;
use zodiax\player\misc\queue\QueueHandler;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function array_keys;
use function array_values;
use function count;
use function is_array;

class ServerMenu extends Form{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($servers = $args[0]) === null || !is_array($servers)){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["servers"])){
				$name = array_keys($extraData["servers"])[$data];
				$data = array_values($extraData["servers"])[$data];
				if(isset($data["ip"]) && isset($data["port"])){
					if(PlayerManager::isBypassAble($player->getName()) || $data["players"] < $data["maxplayers"]){
						$player->transfer($data["ip"], $data["port"]);
					}else{
						QueueHandler::addPlayer($player, $name);
					}
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Server " . TextFormat::WHITE . "Selector"));
		$form->setContent("");
		if(count($servers) === 0){
			$form->addButton(TextFormat::GRAY . "None");
		}else{
			foreach($servers as $name => $data){
				if($data["isonline"]){
					$form->addButton(TextFormat::GREEN . $name . "\n" . TextFormat::WHITE . $data["players"] . TextFormat::GRAY . " | " . PracticeCore::COLOR . $data["maxplayers"], 0, "zeqa/textures/ui/more/region.png");
				}else{
					$form->addButton(TextFormat::RED . $name . "\n" . TextFormat::WHITE . "X" . TextFormat::GRAY . " | " . PracticeCore::COLOR . "X", 0, "zeqa/textures/ui/more/region.png");
				}
			}
			$form->addExtraData("servers", $servers);
		}
		$player->sendForm($form);
	}
}