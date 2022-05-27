<?php

declare(strict_types=1);

namespace zodiax\player;

use DateTime;
use libasynCurl\Curl;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\QueryRegenerateEvent;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\network\mcpe\protocol\types\entity\Attribute as NetworkAttribute;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\player\Player;
use pocketmine\player\XboxLivePlayerInfo;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\InternetRequestResult;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlThread;
use Webmozart\PathUtil\Path;
use zodiax\data\database\DatabaseManager;
use zodiax\data\log\LogMonitor;
use zodiax\discord\DiscordUtil;
use zodiax\duel\misc\RequestHandler;
use zodiax\forms\display\basic\RulesForm;
use zodiax\game\npc\NPCManager;
use zodiax\game\world\BlockRemoverHandler;
use zodiax\misc\AbstractListener;
use zodiax\party\misc\InviteHandler;
use zodiax\party\PartyManager;
use zodiax\player\info\client\IDeviceIds;
use zodiax\player\info\StatsInfo;
use zodiax\player\misc\cosmetic\CosmeticManager;
use zodiax\player\misc\cosmetic\trade\TradeHandler;
use zodiax\player\misc\queue\QueueHandler;
use zodiax\player\misc\SettingsHandler;
use zodiax\player\misc\VanishHandler;
use zodiax\PracticeCore;
use zodiax\PracticeUtil;
use zodiax\proxy\TransferHandler;
use zodiax\ranks\RankHandler;
use zodiax\tasks\AutoRestartTask;
use zodiax\tasks\BroadcastTask;
use zodiax\training\misc\BlockInInviteHandler;
use zodiax\utils\ScoreboardUtil;
use function array_keys;
use function array_map;
use function array_shift;
use function base64_decode;
use function count;
use function date_create_from_format;
use function date_format;
use function explode;
use function file_exists;
use function file_get_contents;
use function is_array;
use function is_numeric;
use function is_string;
use function json_decode;
use function ksort;
use function mkdir;
use function rand;
use function str_contains;
use function str_replace;
use function stripos;
use function strlen;
use function strpos;
use function strtolower;
use function substr;

class PlayerManager extends AbstractListener{

	const BYPASS = ["IP" => ["103.123.40.4" => true], "Username" => ["Openware" => true, "ItsZodiaX" => true, "XoopYT" => true, "R8D" => true, "Y0F" => true, "Xory" => true]];

	private static array $names = [];
	private static array $displaynames = [];
	private static array $sessions = [];
	private static array $bypass = [];
	private static bool $globalMute = false;
	private static array $globalMuteBypass = [];
	private static array $clientInfoHolder = [];
	private static array $verifyInfoHolder = [];
	private static mixed $deviceModels = null;

	/**
	 * @handleCancelled
	 */
	public function onPreLogin(PlayerPreLoginEvent $event) : void{
		if(PracticeCore::isMaintenance()){
			$event->setKickReason(PlayerPreLoginEvent::KICK_REASON_PLUGIN, TextFormat::BOLD . TextFormat::RED . "Network Maintenance\n\n" . TextFormat::RESET . TextFormat::GRAY . "Server is currently in maintenance, for\nmore information join " . PracticeCore::COLOR . "discord.gg/zeqa");
			return;
		}
		if(PracticeCore::isRestarting()){
			$event->setKickReason(PlayerPreLoginEvent::KICK_REASON_PLUGIN, TextFormat::BOLD . TextFormat::RED . "Network Restart");
			return;
		}
		$pInfo = $event->getPlayerInfo();
		if(!$pInfo instanceof XboxLivePlayerInfo || $pInfo->getXuid() === ""){
			$event->setKickReason(PlayerPreLoginEvent::KICK_REASON_PLUGIN, TextFormat::BOLD . TextFormat::RED . "You must login before playing");
			return;
		}
		$name = $pInfo->getUsername();
		if(isset(self::$verifyInfoHolder[$name]) && ($reason = self::$verifyInfoHolder[$name]) !== ""){
			$event->setKickReason(PlayerPreLoginEvent::KICK_REASON_PLUGIN, $reason);
			unset(self::$verifyInfoHolder[$name]);
			return;
		}
		if($event->isKickReasonSet(PlayerPreLoginEvent::KICK_REASON_SERVER_FULL)){
			if(!self::isBypassAble($name)){
				return;
			}
			$event->clearKickReason(PlayerPreLoginEvent::KICK_REASON_SERVER_FULL);
		}
		unset(self::$clientInfoHolder[$name]);
		unset(self::$verifyInfoHolder[$name]);
		$clientInfo = $pInfo->getExtraData();
		if($clientInfo["DeviceOS"] === DeviceOS::ANDROID){
			$first = explode(" ", $clientInfo["DeviceModel"])[0];
			if($first !== strtoupper($first)){
				$reason = TextFormat::BOLD . TextFormat::RED . "Network Kick" . "\n\n" . TextFormat::RESET;
				$reason .= TextFormat::RED . "Reason " . TextFormat::DARK_GRAY . "» " . TextFormat::GRAY . "Toolbox is not allowed" . "\n";
				$reason .= TextFormat::RED . "Kicked by " . TextFormat::DARK_GRAY . "» " . TextFormat::GRAY . "Zeqa Network";
				$event->setKickReason(PlayerPreLoginEvent::KICK_REASON_PLUGIN, $reason);
				return;
			}
		}
		$clientInfo["Username"] = $name;
		$clientInfo["Xuid"] = $pInfo->getXuid();
		self::$clientInfoHolder[$name] = $clientInfo;
		self::checkVPN($name, $event->getIp(), PracticeCore::getVPNInfo());
	}

	public function onJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();
		$event->setJoinMessage("");
		if(isset(self::$verifyInfoHolder[$name = $player->getName()])){
			$reason = self::$verifyInfoHolder[$name];
			if($reason !== ""){
				PracticeCore::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player, $reason){
					if($player->isOnline()){
						$player->kick($reason);
					}
				}), 2);
				unset(self::$clientInfoHolder[$name]);
				return;
			}elseif(!PracticeCore::isLobby()){
				self::createSession($player);
			}
			unset(self::$verifyInfoHolder[$name]);
		}else{
			self::$verifyInfoHolder[$name] = "";
		}
		if(PracticeCore::isLobby()){
			self::createSession($player);
		}
		VanishHandler::hideVanishes($player);
	}

	public function onLeave(PlayerQuitEvent $event) : void{
		$event->setQuitMessage("");
		$player = $event->getPlayer();
		SettingsHandler::clearCache($player);
		NPCManager::despawnNPCs($player);
		unset(self::$names[$name = $player->getName()]);
		unset(self::$displaynames[$player->getDisplayName()]);
		if(($session = (self::$sessions[$name] ?? null)) !== null && $session->hasLoadedData()){
			$disguiseInfo = $session->getDisguiseInfo();
			if($disguiseInfo->isDisguised()){
				unset(self::$displaynames[$disguiseInfo->getDisguiseData()->getDisplayName()]);
			}
			if(!TransferHandler::isTransferring($name)){
				if(($arena = $session->getArena()) !== null){
					if(($killer = $session->getTarget()) !== null && ($ksession = self::getSession($killer)) !== null && ($karena = $ksession->getArena()) !== null && $arena->getName() === $karena->getName()){
						$vec3 = $player->getPosition()->asVector3();
						$optionalKiller = " ";
						$optionalVictim = " ";
						if($arena->getKit()->getName() === "Nodebuff"){
							$optionalKiller = 0;
							$optionalVictim = 0;
							foreach($killer->getInventory()->getContents() as $item){
								if($item->getId() === ItemIds::SPLASH_POTION){
									$optionalKiller++;
								}
							}
							foreach($player->getInventory()->getContents() as $item){
								if($item->getId() === ItemIds::SPLASH_POTION){
									$optionalVictim++;
								}
							}
							$optionalKiller = TextFormat::DARK_GRAY . " [" . TextFormat::WHITE . $optionalKiller . TextFormat::DARK_GRAY . "] " . TextFormat::RESET;
							$optionalVictim = TextFormat::DARK_GRAY . " [" . TextFormat::WHITE . $optionalVictim . TextFormat::DARK_GRAY . "] " . TextFormat::RESET;
						}
						$ksession->setThrowPearl(true, false);
						$ksession->setGapple(true, false);
						$ksession->setShootArrow(true, false);
						$session->setInCombat(null, false);
						$ksession->setInCombat(null, false);
						$ksession->getKitHolder()->setKit($arena->getKit());
						$killer->sendMessage(TextFormat::DARK_GRAY . "» " . $ksession->getItemInfo()->getKillPhraseMessage($killer, $player, $optionalKiller, $optionalVictim));
						$ksession->getStatsInfo()->addKill();
						$ksession->getStatsInfo()->addCurrency(StatsInfo::COIN, rand(StatsInfo::MIN_FFA_KILLER_COIN, StatsInfo::MAX_FFA_KILLER_COIN));
						$ksession->getStatsInfo()->addBp(rand(StatsInfo::MIN_FFA_KILLER_COIN, StatsInfo::MAX_FFA_KILLER_COIN));
						$session->getStatsInfo()->addDeath();
						$session->getStatsInfo()->addCurrency(StatsInfo::COIN, rand(StatsInfo::MIN_FFA_VICTIM_COIN, StatsInfo::MAX_FFA_VICTIM_COIN));
						$session->getStatsInfo()->addBp(rand(StatsInfo::MIN_FFA_VICTIM_COIN, StatsInfo::MAX_FFA_VICTIM_COIN));
						$settingInfo = $ksession->getSettingsInfo();
						if($settingInfo->isBlood()){
							$killer->getServer()->broadcastPackets([$killer], [LevelEventPacket::create(LevelEvent::PARTICLE_DESTROY, RuntimeBlockMapping::getInstance()->toRuntimeId(VanillaBlocks::REDSTONE()->getFullId(), $killer->getNetworkSession()->getProtocolId()), $vec3)]);
						}
						if($settingInfo->isLightning()){
							$killer->getServer()->broadcastPackets([$killer], [AddActorPacket::create($id = Entity::nextRuntimeId(), $id, "minecraft:lightning_bolt", $vec3, new Vector3(0, 0, 0), 0, 0, 0, array_map(function(Attribute $attr) : NetworkAttribute{
								return new NetworkAttribute($attr->getId(), $attr->getMinValue(), $attr->getMaxValue(), $attr->getValue(), $attr->getDefaultValue());
							}, $killer->getAttributeMap()->getAll()), [], [])]);
						}
					}
					$arena->removePlayer($player->getName());
				}elseif(($arena = $session->getSpectateArena()) !== null){
					$arena->removeSpectator($player->getName());
				}elseif(($party = PartyManager::getPartyFromPlayer($player)) !== null){
					$party->removePlayer($player);
				}
			}
			$msg = "";
			if(!$session->getSettingsInfo()->isSilentStaff()){
				$name = $player->getDisplayName();
				$disguiseInfo = $session->getDisguiseInfo();
				if($disguiseInfo->isDisguised()){
					$name = $disguiseInfo->getDisguiseData()->getDisplayName();
				}
				$msg = TextFormat::DARK_GRAY . "[" . TextFormat::RED . "-" . TextFormat::DARK_GRAY . "]" . TextFormat::RED . " $name";
			}
			if($msg !== ""){
				foreach(self::getOnlinePlayers() as $p){
					$p->sendMessage($msg);
				}
			}
			TradeHandler::removeOfferOf($player);
			RequestHandler::removeRequestsOf($player);
			InviteHandler::removeInvitesOf($player);
			BlockInInviteHandler::removeInvitesOf($player);
			VanishHandler::removeFromVanish($player);
			if($session->isFrozen()){
				$banTime = new DateTime("NOW");
				$banTime->modify("+30 days");
				$expires = "30 day(s) 0 hour(s) 0 min(s)";
				$duration = date_format($banTime, "Y-m-d-H-i");
				$reason = "Auto Ban (Logged off while frozen)";
				$lowerName = strtolower($name);
				$staff = $session->getFrozen();
				$announce = TextFormat::GRAY . "\n";
				$announce .= TextFormat::RED . "$staff banned $name\n";
				$announce .= TextFormat::RED . "Reason: " . TextFormat::WHITE . "$reason\n";
				$announce .= TextFormat::GRAY . "";
				foreach(PlayerManager::getOnlinePlayers() as $onlinePlayer){
					$onlinePlayer->sendMessage($announce);
				}
				self::getSession(self::getPlayerExact($staff))?->getStaffStatsInfo()->addBan();
				DatabaseManager::getMainDatabase()->executeImplRaw([0 => "INSERT INTO BansData (name, reason, duration, staff) VALUES ('$lowerName', '$reason', '$duration', '$staff') ON DUPLICATE KEY UPDATE reason = '$reason', duration = '$duration', staff = '$staff'"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);
				DiscordUtil::sendBan("**Banned (" . PracticeCore::getRegionInfo() . ")**\nPlayer: $name\nReason: $reason\nDuration: $expires\nStaff: $staff", true, 0xFF0000, "http://api.zeqa.net/api/players/avatars/" . str_replace(" ", "%20", $name));
			}
		}
		self::removeSession($player);
		ScoreboardUtil::updateSpawnScoreboard(ScoreboardUtil::ONLINE_PLAYERS);
	}

	public function onQueryUpdate(QueryRegenerateEvent $event) : void{
		if(PracticeCore::isLobby()){
			$online = count(PlayerManager::getOnlinePlayers());
			foreach(QueueHandler::getQueryResults() as $servers){
				foreach($servers as $server){
					$online += $server["players"];
				}
			}
			$event->getQueryInfo()->setPlayerCount($online);
			$event->getQueryInfo()->setMaxPlayerCount($online + 1);
		}
	}

	public static function initialize() : void{
		@mkdir(Path::join(PracticeCore::getDataFolderPath(), "players"));
		if(file_exists($deviceModels = Path::join(PracticeCore::getResourcesFolder(), "device", "device_models.json"))){
			self::$deviceModels = json_decode(file_get_contents($deviceModels), true);
		}
		$ranks = "";
		for($i = 1; $i <= 5; $i++){
			foreach(RankHandler::getBypassAbleRanks() as $rank){
				$ranks .= "rank$i = '$rank' OR ";
			}
		}
		$ranks = substr($ranks, 0, -4);
		PracticeCore::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() use ($ranks){
			DatabaseManager::getMainDatabase()->executeImplRaw([0 => "SELECT name FROM PlayerRanks WHERE $ranks"], [0 => []], [0 => SqlThread::MODE_SELECT], function(array $rows){
				foreach($rows[0]->getRows() as $row){
					self::$bypass[$row["name"]] = true;
				}
			}, null);
		}), PracticeUtil::hoursToTicks(1));

		CosmeticManager::initialize();
		PracticeCore::setRestart(false);

		new BlockRemoverHandler();
		new BroadcastTask();
		if(!PracticeCore::isLobby()){
			new AutoRestartTask();
		}
	}

	public static function changeDisplayName(string $oldName, string $newName) : void{
		if(($player = self::getPlayerExact($oldName, true)) !== null){
			self::$displaynames[$newName] = $player;
			unset(self::$displaynames[$oldName]);
			ksort(self::$displaynames, SORT_STRING);
		}
	}

	public static function getPlayerExact(string $name, bool $displayName = false) : ?Player{
		return $displayName ? (self::$displaynames[$name] ?? null) : (self::$names[$name] ?? null);
	}

	public static function getPlayerByPrefix(string $name) : ?Player{
		$found = null;
		$name = strtolower($name);
		$delta = PHP_INT_MAX;
		foreach(self::$displaynames as $displayname => $player){
			if(stripos((string) $displayname, $name) === 0){
				$curDelta = strlen($displayname) - strlen($name);
				if($curDelta < $delta){
					$found = $player;
					$delta = $curDelta;
				}
				if($curDelta === 0){
					break;
				}
			}
		}
		return $found;
	}

	public static function getListDisplayNames(string $player = null) : array{
		if($player === null){
			return array_keys(self::$displaynames);
		}
		$result = self::$displaynames;
		unset($result[$player]);
		return array_keys($result);
	}

	public static function getOnlinePlayers() : array{
		return self::$names;
	}

	public static function getAllSessions() : array{
		return self::$sessions;
	}

	public static function getOnlineStaffs() : array{
		$result = [];
		foreach(self::$sessions as $session){
			if($session->getRankInfo()->hasHelperPermissions()){
				$result[] = $session->getPlayer();
			}
		}
		return $result;
	}

	public static function getAllStaffSessions() : array{
		$result = [];
		foreach(self::$sessions as $session){
			if($session->getRankInfo()->hasHelperPermissions()){
				$result[] = $session;
			}
		}
		return $result;
	}

	public static function getSession(?Player $player) : ?PracticePlayer{
		if($player?->isOnline()){
			return self::$sessions[$player->getName()] ?? null;
		}
		return null;
	}

	public static function createSession(Player $player) : void{
		if(!empty($clientInfo = self::$clientInfoHolder[$name = $player->getName()] ?? [])){
			self::$names[$name] = $player;
			self::$displaynames[$player->getDisplayName()] = $player;
			self::$sessions[$name] = new PracticePlayer($player);
			self::$sessions[$name]->getClientInfo()->init($clientInfo, self::$deviceModels);
			ksort(self::$displaynames, SORT_STRING);
		}else{
			PracticeCore::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player){
				if($player->isOnline()){
					$player->kick(TextFormat::BOLD . TextFormat::RED . "You must login before playing");
				}
			}), 2);
		}
		unset(self::$clientInfoHolder[$name]);
	}

	public static function ableToCreateSession(string $name, string $reason = "") : void{
		if(isset(self::$verifyInfoHolder[$name])){
			if(($player = Server::getInstance()->getPlayerExact($name)) !== null && $player->isOnline() && $player->spawned){
				if($reason !== ""){
					PracticeCore::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player, $reason){
						if($player->isOnline()){
							$player->kick($reason);
						}
					}), 2);
					unset(self::$clientInfoHolder[$name]);
				}elseif(!PracticeCore::isLobby()){
					self::createSession($player);
				}
				unset(self::$verifyInfoHolder[$name]);
			}
		}else{
			self::$verifyInfoHolder[$name] = $reason;
		}
	}

	private static function removeSession(Player $player) : void{
		$session = self::$sessions[$name = $player->getName()] ?? null;
		$session?->saveData();
		$session?->destroyCycles();
		unset(self::$sessions[$name]);
	}

	public static function isBypassAble(string $player) : bool{
		return isset(self::$bypass[strtolower($player)]);
	}

	public static function setBypassAble(string $player, bool $bypass) : void{
		if($bypass){
			self::$bypass[strtolower($player)] = true;
		}else{
			unset(self::$bypass[strtolower($player)]);
		}
	}

	public static function isGlobalMute() : bool{
		return self::$globalMute;
	}

	public static function setGlobalMute(bool $mute) : void{
		self::$globalMute = $mute;
		self::$globalMuteBypass = [];

	}

	public static function isGlobalMuteBypassAble(string $player) : bool{
		return isset(self::$globalMuteBypass[strtolower($player)]);
	}

	public static function setGlobalMuteBypassAble(string $player, bool $bypass) : void{
		if($bypass){
			self::$globalMuteBypass[strtolower($player)] = true;
		}else{
			unset(self::$globalMuteBypass[strtolower($player)]);
		}
	}

	public static function loadPlayerData(string $name) : void{
		$session = self::$sessions[$name] ?? null;
		if($session instanceof PracticePlayer && ($player = $session->getPlayer()) !== null){
			$xuid = $session->getClientInfo()->getXuid();
			if($xuid !== "" && $xuid !== "Unknown"){
				$player->sendMessage(PracticeCore::PREFIX . TextFormat::YELLOW . "Loading your data...");
				DatabaseManager::getMainDatabase()->executeImplRaw([0 => "SELECT * FROM PlayerDuration, PlayerElo, PlayerItems, PlayerRanks, PlayerSettings, PlayerStats WHERE PlayerDuration.xuid = '$xuid' AND PlayerElo.xuid = '$xuid' AND PlayerItems.xuid = '$xuid' AND PlayerRanks.xuid = '$xuid' AND PlayerSettings.xuid = '$xuid' AND PlayerStats.xuid = '$xuid'"], [0 => []], [0 => SqlThread::MODE_SELECT], function(array $rows) use ($session){
					if(($player = $session->getPlayer()) !== null){
						if(isset($rows[0], $rows[0]->getRows()[0]) && ($xuid = $session->getClientInfo()?->getXuid()) !== null){
							$session->loadData($rows[0]->getRows()[0]);
							DatabaseManager::getExtraDatabase()->executeImplRaw([0 => "SELECT * FROM KitsData WHERE xuid = '$xuid'"], [0 => []], [0 => SqlThread::MODE_SELECT], function(array $rows) use ($session){
								if(($session->getPlayer()) !== null){
									if(isset($rows[0], $rows[0]->getRows()[0])){
										$rows = $rows[0]->getRows()[0];
										$kitsData = [];
										foreach($rows as $row => $data){
											if($row !== "xuid" && $row !== "name" && $data !== null){
												$kitsData[$row] = json_decode(base64_decode($data, true), true);
											}
										}
										$session->getKitHolder()?->init($kitsData);
									}
								}
							}, null);
						}else{
							RulesForm::onDisplay($player, []);
						}
					}
				}, null);
			}else{
				PracticeCore::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player){
					if($player->isOnline()){
						$player->kick(TextFormat::BOLD . TextFormat::RED . "You must login before playing");
					}
				}), 2);
			}
		}
	}

	public static function savePlayersData() : void{
		foreach(self::$sessions as $session){
			$session->saveData();
		}
	}

	private static function checkVPN(string $name, string $address, array $api) : void{
		if(!isset(self::BYPASS["IP"][$address]) && !isset(self::BYPASS["Username"][$name]) && count($api) > 0){
			$key = array_shift($api);
			Curl::getRequest("https://v2.api.iphub.info/ip/$address", 10, ["Content-Type: application/json", "X-Key: $key"], function(?InternetRequestResult $vpnResult) use ($name, $address, $api) : void{
				if($vpnResult !== null){
					if(is_array($vpnResponse = json_decode($vpnResult->getBody(), true)) && isset($vpnResponse["block"]) && (int) $vpnResponse["block"] === 1){
						if(isset($vpnResponse["isp"]) && $vpnResponse["isp"] === "CLOUDFLARENET"){
							self::checkAlias($name);
							return;
						}
						$reason = TextFormat::BOLD . TextFormat::RED . "Network Kick" . "\n\n" . TextFormat::RESET;
						$reason .= TextFormat::RED . "Reason " . TextFormat::DARK_GRAY . "» " . TextFormat::GRAY . "VPN is not allowed" . "\n";
						$reason .= TextFormat::RED . "Kicked by " . TextFormat::DARK_GRAY . "» " . TextFormat::GRAY . "Zeqa Network";
						self::ableToCreateSession($name, $reason);
						return;
					}
				}
				self::checkVPN($name, $address, $api);
			});
			return;
		}
		self::checkAlias($name);
	}

	private static function checkAlias(string $name) : void{
		Curl::getRequest("http://api.zeqa.net/api/players/alias/" . str_replace(" ", "%20", $name), 10, ["User-Agent: request", "Authorization: " . PracticeCore::getApiInfo()], function(?InternetRequestResult $altsResult) use ($name) : void{
			if($altsResult !== null){
				$body = $altsResult->getBody();
				if(str_contains($body, "{\"alts\":") && str_contains($body, "\"},{\"data\":\"")){
					$pos = strpos($body, "\"},{\"data\":\"");
					if(($data = substr($body, $pos + 12, -2)) !== ""){
						self::$clientInfoHolder[$name]["alias"] = $data;
						$alts = str_replace(" ", "%20", explode(",", substr($body, 9, $pos - 9)));
						if(is_string($alts)){
							$alts = [$alts];
						}
						self::$clientInfoHolder[$name]["alts"] = $alts;
						self::checkBan($name, $alts);
						return;
					}
				}
			}
			$clientRandomID = (string) (int) (self::$clientInfoHolder[$name]["ClientRandomId"] ?? IDeviceIds::UNKNOWN);
			$deviceIdRaw = (string) (self::$clientInfoHolder[$name]["DeviceId"] ?? "Unknown");
			$selfSignedID = (string) (self::$clientInfoHolder[$name]["SelfSignedId"] ?? "Unknown");
			$xuid = (string) (self::$clientInfoHolder[$name]["Xuid"] ?? "Unknown");
			DatabaseManager::getExtraDatabase()->executeImplRaw([0 => "SELECT sensitivename FROM PlayersData WHERE alias LIKE '%$clientRandomID%' OR alias LIKE '%$deviceIdRaw%' OR alias LIKE '%$selfSignedID%' OR alias LIKE '%$xuid%'"], [0 => []], [0 => SqlThread::MODE_SELECT], function(array $rows) use ($name){
				$alts = [];
				if(isset($rows[0], $rows[0]->getRows()[0], $rows[0]->getRows()[0]["sensitivename"])){
					$alts = str_replace(" ", "%20", $rows[0]->getRows()[0]["sensitivename"]);
					if(is_string($alts)){
						$alts = [$alts];
					}
				}
				$alts[] = str_replace(" ", "%20", $name);
				self::checkBan($name, $alts);
			}, null);
		});
	}

	private static function checkBan(string $name, array $alts) : void{
		if(count($alts) > 0){
			$alt = array_shift($alts);
			Curl::getRequest("http://api.zeqa.net/api/players/punishments/$alt", 10, ["User-Agent: request", "Authorization: " . PracticeCore::getApiInfo()], function(?InternetRequestResult $punishmentResult) use ($name, $alt, $alts) : void{
				if($punishmentResult !== null){
					if(is_array($punishmentResponse = json_decode($punishmentResult->getBody(), true)) && isset($punishmentResponse["name"], $punishmentResponse["reason"], $punishmentResponse["duration"], $punishmentResponse["staff"])){
						$flag = true;
						$remaintime = "Forever";
						if($punishmentResponse["duration"] !== "-1"){
							$bantime = new DateTime("NOW");
							$expiretime = date_create_from_format("Y-m-d-H-i", $punishmentResponse["duration"]);
							if($expiretime instanceof DateTime){
								if($expiretime < $bantime){
									$flag = false;
									$punishmentName = $punishmentResponse["name"];
									DatabaseManager::getMainDatabase()->executeImplRaw([0 => "DELETE FROM BansData WHERE name = '$punishmentName'"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);
								}else{
									$remaintime = $bantime->diff($expiretime);
									if(is_numeric($remaintime->days)){
										$remaintime = $remaintime->format("$remaintime->days day(s) , %h hour(s) , %i minute(s)");
									}else{
										$remaintime = $remaintime->format("%d day(s) , %h hour(s) , %i minute(s)");
									}
								}
							}
						}
						if($flag){
							$theReason = TextFormat::BOLD . TextFormat::RED . "Network Ban" . "\n\n" . TextFormat::RESET;
							$theReason .= TextFormat::RED . "Reason " . TextFormat::DARK_GRAY . "» " . TextFormat::GRAY . $punishmentResponse["reason"] . "\n";
							$theReason .= TextFormat::RED . "Duration " . TextFormat::DARK_GRAY . "» " . TextFormat::GRAY . $remaintime . "\n";
							$theReason .= TextFormat::GRAY . "Appeal at: " . TextFormat::RED . "discord.gg/zeqa";
							if(PracticeCore::isLobby()){
								Server::getInstance()->getLogger()->notice($log = "$name's punish: Name {$punishmentResponse["name"]}, Reason {$punishmentResponse["reason"]}, Duration $remaintime");
								LogMonitor::debugLog("PUNISHMENT: $log");
							}
							self::ableToCreateSession($name, $theReason);
							return;
						}
					}
					self::checkBan($name, $alts);
				}else{
					DatabaseManager::getMainDatabase()->executeImplRaw([0 => "SELECT * FROM BansData where name = '$alt'"], [0 => []], [0 => SqlThread::MODE_SELECT], function(array $rows) use ($name, $alts){
						if(isset($rows[0], $rows[0]->getRows()[0])){
							$punishmentResponse = $rows[0]->getRows()[0];
							$flag = true;
							$remaintime = "Forever";
							if($punishmentResponse["duration"] !== "-1"){
								$bantime = new DateTime("NOW");
								$expiretime = date_create_from_format("Y-m-d-H-i", $punishmentResponse["duration"]);
								if($expiretime instanceof DateTime){
									if($expiretime < $bantime){
										$flag = false;
										$punishmentName = $punishmentResponse["name"];
										DatabaseManager::getMainDatabase()->executeImplRaw([0 => "DELETE FROM BansData WHERE name = '$punishmentName'"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);
									}else{
										$remaintime = $bantime->diff($expiretime);
										if(is_numeric($remaintime->days)){
											$remaintime = $remaintime->format("$remaintime->days day(s) , %h hour(s) , %i minute(s)");
										}else{
											$remaintime = $remaintime->format("%d day(s) , %h hour(s) , %i minute(s)");
										}
									}
								}
							}
							if($flag){
								$theReason = TextFormat::BOLD . TextFormat::RED . "Network Ban" . "\n\n" . TextFormat::RESET;
								$theReason .= TextFormat::RED . "Reason " . TextFormat::DARK_GRAY . "» " . TextFormat::GRAY . $punishmentResponse["reason"] . "\n";
								$theReason .= TextFormat::RED . "Duration " . TextFormat::DARK_GRAY . "» " . TextFormat::GRAY . $remaintime . "\n";
								$theReason .= TextFormat::GRAY . "Appeal at: " . TextFormat::RED . "discord.gg/zeqa";
								if(PracticeCore::isLobby()){
									Server::getInstance()->getLogger()->notice($log = "$name's punish: Name {$punishmentResponse["name"]}, Reason {$punishmentResponse["reason"]}, Duration $remaintime");
									LogMonitor::debugLog("PUNISHMENT: $log");
								}
								self::ableToCreateSession($name, $theReason);
								return;
							}
						}
						self::checkBan($name, $alts);
					}, null);
				}
			});
			return;
		}
		self::ableToCreateSession($name);
	}
}