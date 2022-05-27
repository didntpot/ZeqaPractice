<?php

declare(strict_types=1);

namespace zodiax\forms\display\basic\servers;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\player\misc\queue\QueueHandler;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function array_values;
use function count;

class RegionMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["servers"]) && isset($extraData["servers"][$data])){
				ServerMenu::onDisplay($player, $extraData["servers"][$data]);
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Region " . TextFormat::WHITE . "Selector"));
		$form->setContent("");
		if(count($servers = QueueHandler::getQueryResults()) === 0){
			$form->addButton(TextFormat::GRAY . "None");
		}else{
			foreach($servers as $region => $server){
				$flag = false;
				$players = 0;
				foreach($server as $data){
					if($data["isonline"]){
						$flag = true;
					}
					$players += $data["players"];
				}
				$form->addButton(($flag ? PracticeCore::COLOR : TextFormat::RED) . $region . "\n" . TextFormat::WHITE . ($flag ? $players . PracticeCore::COLOR . " Playing" : "Offline"), 0, "zeqa/textures/ui/more/region.png");
			}
			$form->addExtraData("servers", array_values($servers));
		}
		$player->sendForm($form);
	}
}