<?php

declare(strict_types=1);

namespace zodiax\forms\display\game\duel;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\player\info\duel\DuelInfo;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function count;
use function max;

class DuelHistoryForm{

	public static function onDisplay(Player $player, ...$args) : void{
		if(($session = PlayerManager::getSession($player)) === null){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if(($session = PlayerManager::getSession($player)) !== null && $session->isInHub() && $data !== null && isset($extraData["histories"])){
				if(($info = $session->getDuelInfo(count($extraData["histories"]) - 1 - (int) $data)) !== null){
					DuelInfoForm::onDisplay($player, $info);
				}
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Match " . TextFormat::WHITE . "History"));
		$form->setContent("");
		if(($size = count($duelHistory = $session->getDuelHistory())) <= 0){
			$form->addButton(TextFormat::GRAY . "None");
		}else{
			$end = max($size - 20, 0);
			for($index = $size - 1; $index >= $end; $index--){
				$duelInfo = $duelHistory[$index]["info"];
				if($duelInfo instanceof DuelInfo){
					$winnerDisplayName = $duelInfo->getWinnerDisplayName();
					$loserDisplayName = $duelInfo->getLoserDisplayName();
					$playerVsString = "";
					$resultStr = PracticeCore::COLOR . "D";
					if(!$duelInfo->isDraw()){
						if($duelInfo->getWinnerName() === $player->getName()){
							$playerVsString = TextFormat::WHITE . $winnerDisplayName . PracticeCore::COLOR . " vs " . TextFormat::WHITE . $loserDisplayName;
							$resultStr = TextFormat::GREEN . "W";
						}elseif($duelInfo->getLoserName() === $player->getName()){
							$playerVsString = TextFormat::WHITE . $loserDisplayName . PracticeCore::COLOR . " vs " . TextFormat::WHITE . $winnerDisplayName;
							$resultStr = TextFormat::RED . "L";
						}
					}else{
						$playerVsString = TextFormat::WHITE . $winnerDisplayName . PracticeCore::COLOR . " vs " . TextFormat::WHITE . $loserDisplayName;
					}
					$form->addButton($playerVsString . "\n" . $resultStr . TextFormat::WHITE . " | " . PracticeCore::COLOR . "Queued: " . TextFormat::WHITE . ($duelInfo->isRanked() ? "Ranked" : "Unranked") . " " . $duelInfo->getKit(), 0, $duelInfo->getTexture());
				}
			}
			$form->addExtraData("histories", $duelHistory);
		}
		$player->sendForm($form);
	}
}