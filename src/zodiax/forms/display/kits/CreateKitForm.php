<?php

declare(strict_types=1);

namespace zodiax\forms\display\kits;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\CustomForm;
use zodiax\kits\DefaultKit;
use zodiax\kits\info\EffectsInfo;
use zodiax\kits\info\KnockbackInfo;
use zodiax\kits\info\MiscKitInfo;
use zodiax\kits\KitsManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function str_contains;
use function trim;

class CreateKitForm{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new CustomForm(function(Player $player, $data, $extraData){
			if($data !== null){
				$kitName = trim(TextFormat::clean($data[1]));
				if(str_contains($kitName, " ")){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Name should not have space");
					return;
				}
				$defaultKit = new DefaultKit($kitName, $player->getInventory()->getContents(true), $player->getArmorInventory()->getContents(true), new EffectsInfo(), new MiscKitInfo(), new KnockbackInfo());
				if(KitsManager::add($defaultKit)){
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Successfully created kit " . TextFormat::GRAY . $kitName);
				}else{
					$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "$kitName already exists");
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Create " . TextFormat::WHITE . "Kit"));
		$form->addLabel("This creates a new kit from the items in your inventory");
		$form->addInput("Please provide the name of the kit that you want to create:");
		$player->sendForm($form);
	}
}