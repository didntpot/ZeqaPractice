<?php

declare(strict_types=1);

namespace zodiax\tasks;

use pocketmine\utils\TextFormat;
use zodiax\misc\AbstractRepeatingTask;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use function array_rand;

class BroadcastTask extends AbstractRepeatingTask{

	private array $broadcastMessages = [
		TextFormat::YELLOW . "Someone who's not following the rules? Type " . TextFormat::RED . "/report " . TextFormat::YELLOW . "in order to report them!",
		Textformat::WHITE . "Vote for our server at " . TextFormat::GREEN . "vote.zeqa.net " . TextFormat::WHITE . "and receive special rewards!",
		Textformat::WHITE . "Join our Discord Server for more updates! (" . TextFormat::YELLOW . "discord.gg/zeqa" . Textformat::WHITE . ")",
		Textformat::WHITE . "Want to support our Network? You can do so by going into our store! (" . TextFormat::YELLOW . "store.zeqa.net" . Textformat::WHITE . ")"
	];

	public function __construct(){
		parent::__construct(PracticeUtil::minutesToTicks(5));
	}

	public function onUpdate(int $tickDifference) : void{
		$msg = PracticeCore::PREFIX . $this->broadcastMessages[array_rand($this->broadcastMessages)];
		foreach(PlayerManager::getOnlinePlayers() as $player){
			$player->sendMessage($msg);
		}
	}
}
