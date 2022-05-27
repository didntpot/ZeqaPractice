<?php

declare(strict_types=1);

namespace zodiax\forms\display\basic;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\forms\types\SimpleForm;
use zodiax\PracticeCore;
use function implode;

class RanksInfoForm{

	private static string $content = "";

	public static function onDisplay(Player $player, ...$args) : void{
		$form = new SimpleForm(function(Player $player, $data, $extraData){

		});

		$form->setTitle("ranks");
		$form->setContent(self::getContent());
		$form->addButton(TextFormat::DARK_GRAY . "Close");
		$player->sendForm($form);
	}

	private static function getContent() : string{
		if(self::$content === ""){
			$content = [
				"Support our Network and recieve perks and rewards in return!",
				"",
				PracticeCore::isPackEnable() ? "" : TextFormat::BOLD . TextFormat::GREEN . "VOTER" . TextFormat::RESET,
				TextFormat::WHITE . "  Voting for our Network (" . TextFormat::GREEN . "/vote" . TextFormat::WHITE . "), recieve " . TextFormat::YELLOW . "150 Coins " . TextFormat::WHITE . "every time you vote and ability to " . TextFormat::LIGHT_PURPLE . "/host " . TextFormat::WHITE . "every " . TextFormat::RED . "60 minutes" . TextFormat::RESET . ".",
				"",
				PracticeCore::isPackEnable() ? "" : TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "BOOSTER" . TextFormat::RESET,
				TextFormat::WHITE . "  Boosting the Network's Discord Server (" . TextFormat::GREEN . "discord.gg/zeqa" . TextFormat::WHITE . "), recieve " . TextFormat::YELLOW . "200 coins" . TextFormat::WHITE . ", " . TextFormat::AQUA . "20 shards" . TextFormat::WHITE . " daily, " . TextFormat::AQUA . "4 Special Cosmetics " . TextFormat::WHITE . "and ability to " . TextFormat::LIGHT_PURPLE . "/host " . TextFormat::WHITE . "every " . TextFormat::RED . "60 minutes" . TextFormat::WHITE . ".",
				"",
				PracticeCore::isPackEnable() ? " " . TextFormat::RESET . TextFormat::WHITE . "& " . TextFormat::RESET . "" : TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "MEDIA " . TextFormat::RESET . TextFormat::WHITE . "& " . TextFormat::BOLD . TextFormat::DARK_PURPLE . "FAMOUS" . TextFormat::RESET,
				TextFormat::WHITE . "  Requirements are in our Discord, recieve " . TextFormat::YELLOW . TextFormat::YELLOW . "200 coins" . TextFormat::WHITE . ", " . TextFormat::AQUA . "20 shards" . TextFormat::WHITE . " daily, " . TextFormat::AQUA . "4 Special Cosmetics " . TextFormat::WHITE . "and ability to " . TextFormat::LIGHT_PURPLE . "/host " . TextFormat::WHITE . "every " . TextFormat::RED . "60 minutes" . TextFormat::WHITE . ".",
				"",
				PracticeCore::isPackEnable() ? " " . TextFormat::GREEN . "$7.49 " : TextFormat::BOLD . TextFormat::DARK_AQUA . "MVP " . TextFormat::RESET . TextFormat::GREEN . "$7.49 ",
				TextFormat::WHITE . "  Obtain it at our store and recieve " . TextFormat::YELLOW . "600 coins" . TextFormat::WHITE . ", " . TextFormat::AQUA . "60 shards" . TextFormat::WHITE . " daily, " . TextFormat::AQUA . "8 Special Cosmetics " . TextFormat::WHITE . "and ability to " . TextFormat::LIGHT_PURPLE . "/host " . TextFormat::WHITE . "every " . TextFormat::RED . "45 minutes" . TextFormat::WHITE . ".",
				"",
				PracticeCore::isPackEnable() ? " " . TextFormat::GREEN . "$14.99" : TextFormat::BOLD . TextFormat::BLUE . "MVP+ " . TextFormat::RESET . TextFormat::GREEN . "$14.99 ",
				TextFormat::WHITE . "  Obtain it at our store and recieve " . TextFormat::YELLOW . "1000 coins" . TextFormat::WHITE . ", " . TextFormat::AQUA . "100 shards" . TextFormat::WHITE . " daily, " . TextFormat::AQUA . "8 Special Cosmetics " . TextFormat::WHITE . "and ability to " . TextFormat::LIGHT_PURPLE . "/host " . TextFormat::WHITE . "every " . TextFormat::RED . "30 minutes" . TextFormat::WHITE . ".",
				"",
				TextFormat::WHITE . "  Three of our ranks (" . TextFormat::YELLOW . "Media, Famous and MVP+" . TextFormat::WHITE . ") has the ability to join and " . TextFormat::RED . "BYPASS " . TextFormat::WHITE . "full servers.",
				"",
				TextFormat::WHITE . "Store: " . TextFormat::YELLOW . "store.zeqa.net",
				TextFormat::WHITE . "Hosted by " . TextFormat::RED . "Apex Hosting"
			];
			self::$content = implode("\n", $content);
		}
		return self::$content;
	}
}