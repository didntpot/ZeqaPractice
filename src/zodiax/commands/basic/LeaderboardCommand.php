<?php

declare(strict_types=1);

namespace zodiax\commands\basic;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use zodiax\commands\PracticeCommand;
use zodiax\game\hologram\HologramHandler;
use zodiax\kits\KitsManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\ranks\RankHandler;
use function count;
use function implode;
use function in_array;
use function str_replace;
use function strtolower;

class LeaderboardCommand extends PracticeCommand{

	private string $keys;

	public function __construct(){
		parent::__construct("leaderboard", "Get the target leaderboard", "Usage: /leaderboard <key>", ["lb"]);
		parent::setPermission("practice.permission.leaderboard");
		$this->keys = "";
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->testPermission($sender) && $sender instanceof Player){
			if(count($args) >= 1){
				$key = strtolower($args[0]);
				if(in_array($key, HologramHandler::getHologramKeys(), true)){
					$text = HologramHandler::getEloHologramContentOf($key);
					$size = count($text);
					if($size <= 0){
						return true;
					}
					$title = PracticeUtil::centerText(TextFormat::GRAY . "» " . TextFormat::BOLD . PracticeCore::COLOR . KitsManager::getKit($key)->getName() . TextFormat::WHITE . " Leaderboards" . TextFormat::RESET . TextFormat::GRAY . " «", 148, true);
					$format = TextFormat::GRAY . " » {count}. " . PracticeCore::COLOR . "{name} " . TextFormat::DARK_GRAY . "(" . TextFormat::WHITE . "{elo}" . TextFormat::DARK_GRAY . ")" . TextFormat::GRAY . " «";
					$content = "";
					$count = 1;
					foreach($text as $name => $elo){
						$line = ($count === 10) ? "" : "\n";
						$content .= str_replace("{count}", (string) $count, str_replace("{name}", $name, str_replace("{elo}", (string) $elo, $format))) . $line;
						if($count++ === 11){
							break;
						}
					}
					$lineSeparator = TextFormat::GRAY . "";
					$result = ["title" => $title, "firstSeparator" => $lineSeparator, "content" => $content, "secondSeparator" => $lineSeparator];
					$sender->sendMessage(implode("\n", $result));
				}elseif(in_array($key, HologramHandler::getHologramKeys(false), true)){
					$text = HologramHandler::getStatsHologramContentOf($key);
					$size = count($text);
					if($size <= 0){
						return true;
					}
					$name = match ($key) {
						"kills" => "Kills",
						"deaths" => "Deaths",
						"coins" => "Coins",
						"shards" => "Shards",
						"bp" => "BattlePass"
					};
					$title = PracticeUtil::centerText(TextFormat::GRAY . "» " . TextFormat::BOLD . PracticeCore::COLOR . $name . TextFormat::WHITE . " Leaderboards" . TextFormat::RESET . TextFormat::GRAY . " «", 148, true);
					$format = TextFormat::GRAY . " » {count}. " . PracticeCore::COLOR . "{name} " . TextFormat::DARK_GRAY . "(" . TextFormat::WHITE . "{stat}" . TextFormat::DARK_GRAY . ")" . TextFormat::GRAY . " «";
					$content = "";
					$count = 1;
					foreach($text as $name => $stat){
						$line = ($count === 10) ? "" : "\n";
						$content .= str_replace("{count}", (string) $count, str_replace("{name}", $name, str_replace("{stat}", (string) $stat, $format))) . $line;
						if($count++ === 11){
							break;
						}
					}
					$lineSeparator = TextFormat::GRAY . "";
					$result = ["title" => $title, "firstSeparator" => $lineSeparator, "content" => $content, "secondSeparator" => $lineSeparator];
					$sender->sendMessage(implode("\n", $result));
				}else{
					$sender->sendMessage(PracticeCore::PREFIX . $this->getUsage());
					$sender->sendMessage($this->keys);
				}
				return true;
			}
			$sender->sendMessage(PracticeCore::PREFIX . $this->getUsage());
			if($this->keys === ""){
				$title = PracticeUtil::centerText(TextFormat::GRAY . "» " . TextFormat::BOLD . PracticeCore::COLOR . "Available" . TextFormat::WHITE . " Keys" . TextFormat::RESET . TextFormat::GRAY . " «", 148, true);
				$format = TextFormat::GRAY . " » " . PracticeCore::COLOR . "{key}" . TextFormat::GRAY . " => " . TextFormat::WHITE . "{name}" . TextFormat::GRAY . " «";
				$stats = PracticeUtil::centerText(TextFormat::GRAY . "» " . TextFormat::BOLD . PracticeCore::COLOR . "Stats" . TextFormat::WHITE . " Keys" . TextFormat::RESET . TextFormat::GRAY . " «", 148, true) . "\n";
				$keys = HologramHandler::getHologramKeys(false);
				$count = count($keys);
				foreach($keys as $key){
					$name = match ($key) {
						"kills" => "Kills",
						"deaths" => "Deaths",
						"coins" => "Coins",
						"shards" => "Shards",
						"bp" => "BattlePass"
					};
					$line = (--$count === 0) ? "" : "\n";
					$stats .= str_replace("{key}", $key, str_replace("{name}", $name, $format)) . $line;
				}
				$elos = PracticeUtil::centerText(TextFormat::GRAY . "» " . TextFormat::BOLD . PracticeCore::COLOR . "Elo" . TextFormat::WHITE . " Keys" . TextFormat::RESET . TextFormat::GRAY . " «", 148, true) . "\n";
				$keys = HologramHandler::getHologramKeys();
				$count = count($keys);
				foreach($keys as $key){
					$line = (--$count === 0) ? "" : "\n";
					$elos .= str_replace("{key}", $key, str_replace("{name}", KitsManager::getKit($key)?->getName() ?? "", $format)) . $line;
				}
				$lineSeparator = TextFormat::DARK_GRAY . "";
				$result = ["title" => $title, "firstSeparator" => $lineSeparator, "stats" => $stats, "secondSeparator" => $lineSeparator, "elos" => $elos, "thirdSeparator" => $lineSeparator];
				$this->keys = implode("\n", $result);
			}
			$sender->sendMessage($this->keys);
		}
		return true;
	}

	public function getRankPermission() : string{
		return RankHandler::PERMISSION_NONE;
	}
}
