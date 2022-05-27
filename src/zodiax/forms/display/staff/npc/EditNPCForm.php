<?php

declare(strict_types=1);

namespace zodiax\forms\display\staff\npc;

use pocketmine\entity\Location;
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
use function round;
use function scandir;
use function str_contains;
use function str_replace;

class EditNPCForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || ($npc = NPCManager::getNPCfromName($args[0])) === null){
			return;
		}

		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null && isset($extraData["npc"], $extraData["skins"], $extraData["animations"])){
				$extraData["npc"]->editData(new Location((float) $data[6], (float) $data[7], (float) $data[8], $extraData["npc"]->getLocation()->getWorld(), (float) $data[9], (float) $data[11]), (float) $data[10], $data[2], $extraData["skins"][(int) $data[3]], ((float) $data[4]) * 0.1, $extraData["animations"][(int) $data[5]]);
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully edited NPC's data");
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Edit " . TextFormat::WHITE . "NPC"));
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
			$location = $player->getLocation();
			$form->addLabel("Your position x: {$location->getX()} y: {$location->getY()} z: {$location->getZ()} yaw: {$location->getYaw()} pitch: {$location->getPitch()}");
			$form->addLabel("Name: {$npc->getRealName()}");
			$form->addInput("Format Name", "", $npc->getFormatName());
			$form->addDropdown("Skin", $skins, 0);
			$form->addSlider("Scale (x0.1)", 1, 30);
			$form->addDropdown("Animation", $animations, 0);
			$location = $npc->getLocation();
			$x = (string) round($location->getX(), 2);
			$y = (string) round($location->getY(), 2);
			$z = (string) round($location->getZ(), 2);
			$yaw = (string) round($location->getYaw(), 2);
			$headYaw = (string) round($npc->headYaw, 2);
			$pitch = (string) round($location->getPitch(), 2);
			$form->addInput("X", $x, $x);
			$form->addInput("Y", $y, $y);
			$form->addInput("Z", $z, $z);
			$form->addInput("Yaw", $yaw, $yaw);
			$form->addInput("headYaw", $headYaw, $headYaw);
			$form->addInput("Pitch", $pitch, $pitch);
			$form->addExtraData("skins", $skins);
			$form->addExtraData("animations", $animations);
			$form->addExtraData("npc", $npc);
		}
		$player->sendForm($form);
	}
}