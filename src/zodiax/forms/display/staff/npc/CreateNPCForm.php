<?php

declare(strict_types=1);

namespace zodiax\forms\display\staff\npc;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use Webmozart\PathUtil\Path;
use zodiax\forms\types\CustomForm;
use zodiax\game\npc\NPCManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function array_values;
use function count;
use function file_exists;
use function file_get_contents;
use function json_decode;
use function scandir;
use function str_contains;
use function str_replace;

class CreateNPCForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["skins"], $extraData["animations"])){
				if(NPCManager::getNPCfromName($name = TextFormat::clean($data[0])) !== null){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::WHITE . $name . TextFormat::RED . " already exists");
					return;
				}
				if(NPCManager::addNPC($player->getLocation(), $player->getLocation()->getYaw(), $name, $data[1], $extraData["skins"][(int) $data[2]], ((float) $data[3]) * 0.1, $extraData["animations"][(int) $data[4]] ?? "")){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "NPC " . TextFormat::WHITE . $name . TextFormat::GREEN . " have been created");
				}else{
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Failed to create NPC " . TextFormat::WHITE . $name);
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Create " . TextFormat::WHITE . "NPC"));
		$skins = [];
		foreach(scandir($path = Path::join(PracticeCore::getResourcesFolder(), "npc")) as $file){
			if(!str_contains($file, ".json")){
				continue;
			}
			if(file_exists(Path::join($path, str_replace(".json", ".png", $file)))){
				$skins[] = str_replace(".json", "", $file);
			}
		}
		$animations = array_values(json_decode(file_get_contents(Path::join(PracticeCore::getResourcesFolder(), "npc", "animations.json")), true));
		if(count($skins) === 0){
			$form->addLabel(TextFormat::RED . "No skins available!");
		}else{
			$form->addInput("Name");
			$form->addInput("Format Name");
			$form->addDropdown("Skin", $skins, 0);
			$form->addSlider("Scale (x0.1)", 1, 30);
			$form->addDropdown("Animation", $animations, 0);
			$form->addExtraData("skins", $skins);
			$form->addExtraData("animations", $animations);
		}
		$player->sendForm($form);
	}
}