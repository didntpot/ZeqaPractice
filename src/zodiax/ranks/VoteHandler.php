<?php

declare(strict_types=1);

namespace zodiax\ranks;

use DateTime;
use libasynCurl\Curl;
use pocketmine\player\Player;
use pocketmine\utils\InternetRequestResult;
use pocketmine\utils\TextFormat;
use zodiax\player\info\StatsInfo;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use function date_format;
use function str_replace;

class VoteHandler{

	const VOTE_STATUS_NOT_VOTED = "0";
	const VOTE_STATUS_CLAIMABLE = "1";
	const VOTE_STATUS_CLAIMED = "2";

	private static array $voteInfoHolder = [];

	public static function getRank() : ?Rank{
		return RankHandler::getRank("Voter");
	}

	public static function processVote(Player $player) : void{
		self::$voteInfoHolder[$name = $player->getName()] = true;
		Curl::getRequest(self::getApiUrl("object=votes&element=claim&key=" . PracticeCore::getVoteInfo() . "&username=" . str_replace(" ", "%20", $name)), 10, [], function(?InternetRequestResult $voteResult) use ($name) : void{
			unset(self::$voteInfoHolder[$name]);
			if($voteResult !== null){
				if(($player = PlayerManager::getPlayerExact($name)) !== null){
					switch($voteResult->getBody()){
						case self::VOTE_STATUS_NOT_VOTED:
							$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You have not voted yet");
							break;
						case self::VOTE_STATUS_CLAIMABLE:
							self::$voteInfoHolder[$name = $player->getName()] = true;
							Curl::getRequest(self::getApiUrl("action=post&object=votes&element=claim&key=" . PracticeCore::getVoteInfo() . "&username=" . str_replace(" ", "%20", $name)), 10, [], function(?InternetRequestResult $voteResult) use ($name) : void{
								if($voteResult !== null){
									if(($session = PlayerManager::getSession($player = PlayerManager::getPlayerExact($name))) !== null){
										if($voteResult->getBody() === "1"){
											if(($rank = self::getRank()) !== null){
												$session->getStatsInfo()->addCurrency(StatsInfo::COIN, 150);
												$session->getRankInfo()->addRank($rank);
												$expiresTime = new DateTime("NOW");
												$expiresTime->modify("+1 days");
												$session->getDurationInfo()->setVoted(date_format($expiresTime, "Y-m-d-H-i"));
												$player->sendMessage(PracticeCore::PREFIX . TextFormat::GREEN . "Thank you for voting! You have received your rewards");
											}
										}else{
											$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Unable to claim your rewards, please try again later");
										}
									}
								}
								unset(self::$voteInfoHolder[$name]);
							});
							break;
						case self::VOTE_STATUS_CLAIMED:
							$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "You have already claimed your rewards");
							unset(self::$voteInfoHolder[$name]);
							break;
						default:
							$player->sendMessage(PracticeCore::PREFIX . TextFormat::RED . "Unable to claim your rewards, please try again later");
							unset(self::$voteInfoHolder[$name]);
							break;
					}
				}
			}
		});
	}

	public static function isInQueue(Player $player) : bool{
		return isset(self::$voteInfoHolder[$player->getName()]);
	}

	private static function getApiUrl(string $args) : string{
		return "https://minecraftpocket-servers.com/api/?" . $args;
	}
}