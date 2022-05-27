<?php

declare(strict_types=1);

namespace zodiax\forms\display\basic\settings;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function count;
use function is_string;

class BuilderModeForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(($session = PlayerManager::getSession($player)) === null){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($data[0])){
				$session = PlayerManager::getSession($player);
				$session->getSettingsInfo()->getBuilderModeInfo()->setEnabled((bool) $data[0]);
				if(isset($extraData["worlds"])){
					$builderWorlds = $extraData["worlds"];
					foreach($data as $name => $value){
						if(is_string($name)){
							$session->getSettingsInfo()->getBuilderModeInfo()->setBuildEnabledInWorld($builderWorlds[$name], $value);
						}
					}
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Builder " . TextFormat::WHITE . "Mode"));
		$form->addToggle(PracticeCore::COLOR . "Builder " . TextFormat::WHITE . "Mode", $session->getSettingsInfo()->getBuilderModeInfo()->isEnabled());
		if(count($worlds = $session->getSettingsInfo()->getBuilderModeInfo()->getBuilderWorlds()) <= 0){
			$form->addLabel(TextFormat::GRAY . "None");
		}else{
			foreach($worlds as $name => $value){
				$form->addToggle($name, $value);
			}
			$form->addExtraData("worlds", $worlds);
		}
		$player->sendForm($form);
	}
}