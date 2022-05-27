<?php

declare(strict_types=1);

namespace zodiax\forms\display\basic;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function implode;
use function is_array;

class RulesForm{

	private static string $content = "";

	public static function onDisplay(Player $player, ...$args) : void{
		if(!isset($args[0]) || !is_array($playerData = $args[0])){
			return;
		}

		$form = new SimpleForm(function(Player $player, $data, $extraData){
			if($data !== 0){
				$theReason = TextFormat::BOLD . TextFormat::RED . "Network Kick" . "\n\n" . TextFormat::RESET;
				$theReason .= TextFormat::RED . "Reason " . TextFormat::DARK_GRAY . "» " . TextFormat::GRAY . "Disagree rules";
				$player->kick($theReason);
			}elseif(($session = PlayerManager::getSession($player)) !== null){
				$session->loadData($extraData["playerData"]);
				$session->getKitHolder()->init([]);
			}
		});

		$form->setTitle(PracticeUtil::formatTitle(PracticeCore::COLOR . "Rules " . TextFormat::WHITE . "Info"));
		$form->setContent(self::getContent());
		$form->addButton(TextFormat::BOLD . TextFormat::GREEN . "Agree", 0, "textures/ui/confirm.png");
		$form->addButton(TextFormat::BOLD . TextFormat::RED . "Disagree", 0, "textures/ui/cancel.png");
		$form->addExtraData("playerData", $playerData);
		$player->sendForm($form);
	}

	private static function getContent() : string{
		if(self::$content === ""){
			$content = [
				TextFormat::GRAY . "By joining in our server, you have agreed to follow our rules and we have all the rights to give Punishments"
				, ""
				, TextFormat::GRAY . "- Minimum of 10ms debounce time"
				, TextFormat::GRAY . "- If your mouse double clicks"
				, TextFormat::GRAY . "  be sure to use DC Prevent while playing"
				, TextFormat::GRAY . "- No hacking or any unfair advantages"
				, TextFormat::GRAY . "- No macros or firekeys"
				, TextFormat::GRAY . "- No hate Speech (Racism, Death Threats, etc.)"
				, TextFormat::GRAY . "- No using any clients that provide advantages (Toolbox)"
				, TextFormat::GRAY . "- No using 'No Hurt Cam'"
				, TextFormat::GRAY . "- No abusing bugs or glitches"
				, ""
				, TextFormat::GRAY . "If you happen to cheat on other servers"
				, TextFormat::GRAY . "Make sure you restart your pc when logging on to Zeqa"
			];
			self::$content = implode("\n", $content);
		}
		return self::$content;
	}
}