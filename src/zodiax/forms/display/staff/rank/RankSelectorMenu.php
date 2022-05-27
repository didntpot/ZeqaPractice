<?php

declare(strict_types=1);

namespace zodiax\forms\display\staff\rank;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\ranks\RankHandler;
use function array_values;

class RankSelectorMenu{

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== null){
				if(isset($extraData["ranks"]) && isset(array_values($extraData["ranks"])[$data]) && isset($extraData["formType"])){
					$rank = array_values($extraData["ranks"])[$data];
					$type = $extraData["formType"];
					switch($type){
						case "view" :
							ViewRankForm::onDisplay($player, $rank);
							break;
						case "edit" :
							EditRankForm::onDisplay($player, $rank);
							break;
						case "delete":
							if($rank === RankHandler::getDefaultRank()){
								$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You can not delete default rank");
							}elseif(RankHandler::removeRank($name = $rank->getName())){
								$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Successfully deleted rank " . TextFormat::WHITE . "$name");
							}
							break;
					}
				}
			}
		});

		$formType = $args[0] ?? "view";
		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Select " . TextFormat::WHITE . "Rank"));
		$form->setContent("Select the rank to edit or delete");
		$ranks = RankHandler::getRanks(false);
		foreach($ranks as $rank){
			$form->addButton($rank->getColor() . $rank->getName());
		}
		$form->addExtraData("formType", $formType);
		$form->addExtraData("ranks", $ranks);
		$player->sendForm($form);
	}
}