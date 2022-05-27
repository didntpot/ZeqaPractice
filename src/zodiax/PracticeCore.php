<?php

declare(strict_types=1);

namespace zodiax;

use pocketmine\command\Command;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\EnchantmentIds;
use pocketmine\data\bedrock\EntityLegacyIds;
use pocketmine\data\bedrock\PotionTypeIdMap;
use pocketmine\data\bedrock\PotionTypeIds;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\projectile\Arrow as PMArrow;
use pocketmine\event\EventPriority;
use pocketmine\event\server\NetworkInterfaceRegisterEvent;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\item\enchantment\Rarity;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\raklib\RakLibInterface;
use pocketmine\network\query\DedicatedQueryNetworkInterface;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\timings\TimingsHandler;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\world\World;
use UnexpectedValueException;
use Webmozart\PathUtil\Path;
use zodiax\arena\ArenaManager;
use zodiax\commands\basic\CosmeticsCommand;
use zodiax\commands\basic\DiscordCommand;
use zodiax\commands\basic\DisguiseCommand;
use zodiax\commands\basic\DuelCommand;
use zodiax\commands\basic\HostCommand;
use zodiax\commands\basic\HubCommand;
use zodiax\commands\basic\InfoCommand;
use zodiax\commands\basic\LeaderboardCommand;
use zodiax\commands\basic\ListCommand;
use zodiax\commands\basic\MoveServerCommand;
use zodiax\commands\basic\PingCommand;
use zodiax\commands\basic\RanksCommand;
use zodiax\commands\basic\RegionCommand;
use zodiax\commands\basic\ReplyCommand;
use zodiax\commands\basic\ReportCommand;
use zodiax\commands\basic\RulesCommand;
use zodiax\commands\basic\SettingsCommand;
use zodiax\commands\basic\ShopCommand;
use zodiax\commands\basic\SpectateCommand;
use zodiax\commands\basic\StatsCommand;
use zodiax\commands\basic\SuicideCommand;
use zodiax\commands\basic\VoteCommand;
use zodiax\commands\basic\WhisperCommand;
use zodiax\commands\staff\ArenaCommand;
use zodiax\commands\staff\BanCommand;
use zodiax\commands\staff\DailychatCommand;
use zodiax\commands\staff\FindPlayerCommand;
use zodiax\commands\staff\FlyCommand;
use zodiax\commands\staff\FreezeCommand;
use zodiax\commands\staff\GamemodeCommand;
use zodiax\commands\staff\GlobalmuteCommand;
use zodiax\commands\staff\HologramCommand;
use zodiax\commands\staff\KickCommand;
use zodiax\commands\staff\KitCommand;
use zodiax\commands\staff\MuteCommand;
use zodiax\commands\staff\NPCCommand;
use zodiax\commands\staff\PlayerInfoCommand;
use zodiax\commands\staff\RankCommand;
use zodiax\commands\staff\RestartCommand;
use zodiax\commands\staff\SetRankCommand;
use zodiax\commands\staff\StaffCommand;
use zodiax\commands\staff\TeleportCommand;
use zodiax\commands\staff\TestCommand;
use zodiax\commands\staff\UnbanCommand;
use zodiax\commands\staff\UnfreezeCommand;
use zodiax\commands\staff\UnmuteCommand;
use zodiax\commands\staff\VanishCommand;
use zodiax\commands\staff\WhitelistCommand;
use zodiax\commands\staff\XoopCommand;
use zodiax\data\database\DatabaseManager;
use zodiax\data\log\LogMonitor;
use zodiax\data\queue\AsyncTaskQueue;
use zodiax\data\timings\PracticeTimings;
use zodiax\discord\DiscordUtil;
use zodiax\duel\BotHandler;
use zodiax\duel\DuelHandler;
use zodiax\duel\ReplayHandler;
use zodiax\event\EventHandler;
use zodiax\game\enchantments\KnockbackEnchantment;
use zodiax\game\entity\CombatBot;
use zodiax\game\entity\DeathEntity;
use zodiax\game\entity\GenericHuman;
use zodiax\game\entity\projectile\Arrow;
use zodiax\game\entity\projectile\EnderPearl;
use zodiax\game\entity\projectile\FishingHook;
use zodiax\game\entity\projectile\Snowball;
use zodiax\game\entity\projectile\SplashPotion;
use zodiax\game\entity\replay\ReplayArrow;
use zodiax\game\entity\replay\ReplayHook;
use zodiax\game\entity\replay\ReplayHuman;
use zodiax\game\entity\replay\ReplayPearl;
use zodiax\game\entity\replay\ReplayPotion;
use zodiax\game\entity\replay\ReplaySnowball;
use zodiax\game\GameplayListener;
use zodiax\game\items\ItemHandler;
use zodiax\game\npc\NPCManager;
use zodiax\game\world\BlockRemoverHandler;
use zodiax\game\world\FormatConverter;
use zodiax\game\world\VoidGenerator;
use zodiax\kits\KitsManager;
use zodiax\misc\PracticeRakLibInterface;
use zodiax\party\duel\PartyDuelHandler;
use zodiax\player\misc\cosmetic\CosmeticManager;
use zodiax\player\misc\queue\QueueHandler;
use zodiax\player\PlayerManager;
use zodiax\proxy\ProxyListener;
use zodiax\proxy\ProxyTask;
use zodiax\proxy\WDPERakLibInterface;
use zodiax\tasks\DebugTask;
use zodiax\tasks\GarbageCollectorTask;
use zodiax\training\TrainingHandler;
use function array_shift;
use function explode;
use function str_replace;

/**
 * TODO:
 *    - Reproduce disguise bug (Not able to find disguised player)
 *    - Store replay on database instead of keeping them cached on the server (use another thread on loading, saving binary format)
 *    - Replay like hive
 *    - Random team duels (fast queue)
 */
class PracticeCore extends PluginBase{

	const NAME = "Zeqa";
	const REPLAY = false;
	const PROXY = false;
	const DEBUG = false;
	const COLOR = TextFormat::YELLOW;
	const PREFIX = self::COLOR . "ZEQA" . TextFormat::DARK_GRAY . " » " . TextFormat::RESET;

	private static self $instance;
	private static string $dataFolder;
	private static string $pluginFolder;
	private static string $resourceFolder;
	private static bool $maintenance;
	private static string $region;
	private static string $api;
	private static string $vote;
	private static array $vpn;
	private static string $logo;
	private static array $packs;
	private static array $webhooks;
	private static array $servers;
	private static bool $lobby;
	private static bool $pack;
	private static int $counter;
	private static bool $restarting;

	public static function getResourcesFolder() : string{
		return self::$resourceFolder;
	}

	public static function getPluginFolder() : string{
		return self::$pluginFolder;
	}

	public static function getDataFolderPath() : string{
		return self::$dataFolder;
	}

	public static function isMaintenance() : bool{
		return self::$maintenance;
	}

	public static function getRegionInfo() : string{
		return self::$region;
	}

	public static function getApiInfo() : string{
		return self::$api;
	}

	public static function getVoteInfo() : string{
		return self::$vote;
	}

	public static function getVPNInfo() : array{
		return self::$vpn;
	}

	public static function getLogoInfo() : string{
		return self::$logo;
	}

	public static function getPacksInfo() : array{
		return self::$packs;
	}

	public static function getWebhookInfo() : array{
		return self::$webhooks;
	}

	public static function getServersInfo() : array{
		return self::$servers;
	}

	public static function isLobby() : bool{
		return self::$lobby;
	}

	public static function isPackEnable() : bool{
		return self::$pack;
	}

	public static function setRestart(bool $restart) : void{
		self::$restarting = $restart;
		if($restart){
			self::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() : void{
				if(self::$counter > 0){
					$msg = PracticeCore::PREFIX . TextFormat::RED . "Restarting in " . self::$counter . "...";
					foreach(PlayerManager::getOnlinePlayers() as $player){
						$player->sendMessage($msg);
					}
				}elseif(self::$counter === 0){
					PlayerManager::savePlayersData();
					foreach(PlayerManager::getOnlinePlayers() as $player){
						$player->kick(TextFormat::BOLD . TextFormat::RED . "Network Restart");
					}
					DatabaseManager::getMainDatabase()->waitAll();
					DatabaseManager::getExtraDatabase()->waitAll();
				}elseif(self::$counter === -60){
					Server::getInstance()->shutdown();
				}
				self::$counter--;
			}), PracticeUtil::secondsToTicks(1));
		}
	}

	public static function isRestarting() : bool{
		return self::$restarting;
	}

	public static function getInstance() : PracticeCore{
		return self::$instance;
	}

	protected function onLoad() : void{
		self::$instance = $this;

		self::$counter = 20;
		self::$restarting = true;
		self::$dataFolder = str_replace("\\", DIRECTORY_SEPARATOR, str_replace("/", DIRECTORY_SEPARATOR, $this->getDataFolder()));
		self::$pluginFolder = str_replace("\\", DIRECTORY_SEPARATOR, str_replace("/", DIRECTORY_SEPARATOR, Path::join($this->getFile(), "src", "zodiax")));
		self::$resourceFolder = str_replace("\\", DIRECTORY_SEPARATOR, str_replace("/", DIRECTORY_SEPARATOR, Path::join($this->getFile(), "resources")));

		$config = new Config(self::$dataFolder . "settings.yml", Config::YAML);
		self::$maintenance = false;
		if($config->exists("maintenance")){
			self::$maintenance = $config->get("maintenance");
		}
		self::$region = self::NAME;
		if($config->exists("region")){
			self::$region = ($config->get("region") === "") ? self::NAME : $config->get("region");
		}
		self::$lobby = self::$region === "Lobby";
		self::$api = "";
		if($config->exists("api")){
			self::$api = $config->get("api");
		}
		self::$vote = "";
		if($config->exists("vote")){
			self::$vote = $config->get("vote");
		}
		self::$vpn = [];
		if($config->exists("vpn")){
			self::$vpn = (array) $config->get("vpn");
		}
		self::$logo = "";
		if($config->exists("logo")){
			self::$logo = $config->get("logo");
		}
		self::$packs = [];
		if($config->exists("texture_packs")){
			$info = (array) $config->get("texture_packs");
			foreach($info as $pack_Id => $encryption_Key){
				self::$packs[$pack_Id] = $encryption_Key;
			}
		}
		self::$pack = !empty(self::$packs);
		self::$webhooks = ["status" => "", "ban" => "", "logs" => "", "chat" => "", "sync" => ""];
		if($config->exists("webhooks")){
			self::$webhooks = (array) $config->get("webhooks");
		}
		self::$servers = [];
		if($config->exists("servers")){
			$servers = (array) $config->get("servers");
			foreach($servers as $region => $server){
				self::$servers[$region] = [];
				foreach($server as $name => $data){
					$data = explode(":", $data);
					self::$servers[$region][$name] = ["ip" => $data[0], "port" => (int) $data[1]];
				}
			}
		}

		$this->registerGenerators();
	}

	protected function onEnable() : void{
		if(PracticeCore::PROXY){
			$this->getServer()->getPluginManager()->registerEvent(NetworkInterfaceRegisterEvent::class, function(NetworkInterfaceRegisterEvent $event) : void{
				$network = $event->getInterface();
				if($network instanceof DedicatedQueryNetworkInterface){
					$event->cancel();
					return;
				}
				if($network instanceof RakLibInterface && !$network instanceof WDPERakLibInterface){
					$event->cancel();
					$this->getServer()->getNetwork()->registerInterface(new WDPERakLibInterface($this->getServer(), $this->getServer()->getIp(), $this->getServer()->getPort(), false));
					if($this->getServer()->getConfigGroup()->getConfigBool("enable-ipv6", true)){
						$this->getServer()->getNetwork()->registerInterface(new WDPERakLibInterface($this->getServer(), $this->getServer()->getIpV6(), $this->getServer()->getPort(), true));
					}
				}
			}, EventPriority::NORMAL, $this, true);

			new ProxyListener();
			new ProxyTask();
		}else{
			$this->getServer()->getPluginManager()->registerEvent(NetworkInterfaceRegisterEvent::class, function(NetworkInterfaceRegisterEvent $event) : void{
				$network = $event->getInterface();
				if($network instanceof DedicatedQueryNetworkInterface){
					$event->cancel();
					return;
				}
				if($network instanceof RakLibInterface && !$network instanceof PracticeRakLibInterface){
					$event->cancel();
					$this->getServer()->getNetwork()->registerInterface(new PracticeRakLibInterface($this->getServer(), $this->getServer()->getIp(), $this->getServer()->getPort(), false));
					if($this->getServer()->getConfigGroup()->getConfigBool("enable-ipv6", true)){
						$this->getServer()->getNetwork()->registerInterface(new PracticeRakLibInterface($this->getServer(), $this->getServer()->getIpV6(), $this->getServer()->getPort(), true));
					}
				}
			}, EventPriority::NORMAL, $this, true);
		}

		LogMonitor::initialize();
		AsyncTaskQueue::initialize();

		$this->registerEntities();
		$this->initEnchantments();
		$this->registerCommands();

		KitsManager::initialize();
		ArenaManager::initialize();
		ItemHandler::initialize();
		NPCManager::initialize();
		DatabaseManager::initialize();
		QueueHandler::initialize();
		DiscordUtil::initialize();
		PracticeTimings::initialize();
		if(self::DEBUG){
			TimingsHandler::setEnabled();
		}

		new PlayerManager();
		new GameplayListener();

		if(self::DEBUG){
			new DebugTask();
		}
		new GarbageCollectorTask();

		new BotHandler();
		new DuelHandler();
		if(self::REPLAY){
			new ReplayHandler();
		}
		new EventHandler();
		new PartyDuelHandler();
		new TrainingHandler();

		$this->getServer()->getNetwork()->setName(TextFormat::BOLD . self::COLOR . "Ze" . TextFormat::WHITE . "qa");
		DiscordUtil::sendStatus(true);
		LogMonitor::debugLog("SERVER: STARTED");
	}

	private function convertWorldGeneratorToVoid(string $world){
		$path = Path::join(Server::getInstance()->getDataPath(), "worlds", $world) . DIRECTORY_SEPARATOR;
		$providers = Server::getInstance()->getWorldManager()->getProviderManager()->getMatchingProviders($path);
		$providerClass = array_shift($providers);
		$provider = $providerClass->fromPath($path);
		$converter = new FormatConverter($provider, Server::getInstance()->getWorldManager()->getProviderManager()->getDefault(), Path::join(Server::getInstance()->getDataPath(), "backups", "worlds"), Server::getInstance()->getLogger());
		$converter->execute();
	}

	private function registerEntities() : void{
		EntityFactory::getInstance()->register(GenericHuman::class, function(World $world, CompoundTag $nbt) : GenericHuman{
			return new GenericHuman(EntityDataHelper::parseLocation($nbt, $world), GenericHuman::parseSkinNBT($nbt), $nbt);
		}, ["GenericHuman"]);
		EntityFactory::getInstance()->register(CombatBot::class, function(World $world, CompoundTag $nbt) : CombatBot{
			return new CombatBot(EntityDataHelper::parseLocation($nbt, $world), CombatBot::parseSkinNBT($nbt), $nbt);
		}, ["CombatBot"]);
		EntityFactory::getInstance()->register(DeathEntity::class, function(World $world, CompoundTag $nbt) : DeathEntity{
			return new DeathEntity(EntityDataHelper::parseLocation($nbt, $world), DeathEntity::parseSkinNBT($nbt), $nbt);
		}, ["DeathEntity"]);
		EntityFactory::getInstance()->register(EnderPearl::class, function(World $world, CompoundTag $nbt) : EnderPearl{
			return new EnderPearl(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
		}, ["ThrownEnderpearl", "minecraft:ender_pearl"], EntityLegacyIds::ENDER_PEARL);
		EntityFactory::getInstance()->register(FishingHook::class, function(World $world, CompoundTag $nbt) : FishingHook{
			return new FishingHook(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
		}, ["FishingHook", "minecraft:fishing_hook"], EntityLegacyIds::FISHING_HOOK);
		EntityFactory::getInstance()->register(Snowball::class, function(World $world, CompoundTag $nbt) : Snowball{
			return new Snowball(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
		}, ["Snowball", "minecraft:snowball"], EntityLegacyIds::SNOWBALL);
		EntityFactory::getInstance()->register(Arrow::class, function(World $world, CompoundTag $nbt) : Arrow{
			return new Arrow(EntityDataHelper::parseLocation($nbt, $world), null, $nbt->getByte(PMArrow::TAG_CRIT, 0) === 1, $nbt);
		}, ["Arrow", "minecraft:arrow"], EntityLegacyIds::ARROW);
		EntityFactory::getInstance()->register(SplashPotion::class, function(World $world, CompoundTag $nbt) : SplashPotion{
			$potionType = PotionTypeIdMap::getInstance()->fromId($nbt->getShort("PotionId", PotionTypeIds::WATER));
			if($potionType === null){
				throw new UnexpectedValueException("No such potion type");
			}
			return new SplashPotion(EntityDataHelper::parseLocation($nbt, $world), null, $potionType, $nbt);
		}, ["ThrownPotion", "minecraft:potion", "thrownpotion"], EntityLegacyIds::SPLASH_POTION);
		EntityFactory::getInstance()->register(ReplayArrow::class, function(World $world, CompoundTag $nbt) : ReplayArrow{
			return new ReplayArrow(EntityDataHelper::parseLocation($nbt, $world), null, $nbt->getByte(PMArrow::TAG_CRIT, 0) === 1, $nbt);
		}, ["ReplayArrow"]);
		EntityFactory::getInstance()->register(ReplayHook::class, function(World $world, CompoundTag $nbt) : ReplayHook{
			return new ReplayHook(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
		}, ["ReplayHook"]);
		EntityFactory::getInstance()->register(ReplaySnowball::class, function(World $world, CompoundTag $nbt) : ReplaySnowball{
			return new ReplaySnowball(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
		}, ["ReplaySnowball"]);
		EntityFactory::getInstance()->register(ReplayHuman::class, function(World $world, CompoundTag $nbt) : ReplayHuman{
			return new ReplayHuman(EntityDataHelper::parseLocation($nbt, $world), ReplayHuman::parseSkinNBT($nbt), $nbt);
		}, ["ReplayHuman"]);
		EntityFactory::getInstance()->register(ReplayPearl::class, function(World $world, CompoundTag $nbt) : ReplayPearl{
			return new ReplayPearl(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
		}, ["ReplayPearl"]);
		EntityFactory::getInstance()->register(ReplayPotion::class, function(World $world, CompoundTag $nbt) : ReplayPotion{
			$potionType = PotionTypeIdMap::getInstance()->fromId($nbt->getShort("PotionId", PotionTypeIds::WATER));
			if($potionType === null){
				throw new UnexpectedValueException("No such potion type");
			}
			return new ReplayPotion(EntityDataHelper::parseLocation($nbt, $world), null, $potionType, $nbt);
		}, ["ReplayPotion"]);
	}

	private function initEnchantments() : void{
		EnchantmentIdMap::getInstance()->register(EnchantmentIds::KNOCKBACK, new KnockbackEnchantment(KnownTranslationFactory::enchantment_knockback(), Rarity::UNCOMMON, ItemFlags::SWORD, ItemFlags::NONE, 2));
	}

	private function registerCommands() : void{
		$this->unregisterCommand("checkperm");
		$this->unregisterCommand("gamerule");
		$this->unregisterCommand("checkperm");
		$this->unregisterCommand("mixer");
		$this->unregisterCommand("suicide");
		$this->unregisterCommand("gamemode");
		$this->unregisterCommand("ban");
		$this->unregisterCommand("ban-ip");
		$this->unregisterCommand("banlist");
		$this->unregisterCommand("kick");
		$this->unregisterCommand("pardon");
		$this->unregisterCommand("pardon-ip");
		$this->unregisterCommand("tp");
		$this->unregisterCommand("tell");
		$this->unregisterCommand("me");
		$this->unregisterCommand("clear");
		$this->unregisterCommand("ver");
		$this->unregisterCommand("whitelist");
		$this->unregisterCommand("list");
		$this->registerCommand(new CosmeticsCommand());
		$this->registerCommand(new DiscordCommand());
		$this->registerCommand(new DisguiseCommand());
		$this->registerCommand(new DuelCommand());
		$this->registerCommand(new HostCommand());
		$this->registerCommand(new HubCommand());
		$this->registerCommand(new InfoCommand());
		$this->registerCommand(new LeaderboardCommand());
		$this->registerCommand(new ListCommand());
		if(PracticeCore::PROXY){
			$this->registerCommand(new MoveServerCommand());
		}
		$this->registerCommand(new PingCommand());
		$this->registerCommand(new RanksCommand());
		$this->registerCommand(new RegionCommand());
		$this->registerCommand(new ReplyCommand());
		$this->registerCommand(new ReportCommand());
		$this->registerCommand(new RulesCommand());
		$this->registerCommand(new SettingsCommand());
		$this->registerCommand(new ShopCommand());
		$this->registerCommand(new SpectateCommand());
		$this->registerCommand(new StatsCommand());
		$this->registerCommand(new SuicideCommand());
		$this->registerCommand(new VoteCommand());
		$this->registerCommand(new WhisperCommand());
		$this->registerCommand(new ArenaCommand());
		$this->registerCommand(new BanCommand());
		$this->registerCommand(new DailychatCommand());
		$this->registerCommand(new FindPlayerCommand());
		$this->registerCommand(new FlyCommand());
		$this->registerCommand(new FreezeCommand());
		$this->registerCommand(new GamemodeCommand());
		$this->registerCommand(new GlobalmuteCommand());
		$this->registerCommand(new HologramCommand());
		$this->registerCommand(new KickCommand());
		$this->registerCommand(new KitCommand());
		$this->registerCommand(new MuteCommand());
		$this->registerCommand(new NPCCommand());
		$this->registerCommand(new PlayerInfoCommand());
		$this->registerCommand(new RankCommand());
		$this->registerCommand(new RestartCommand());
		$this->registerCommand(new SetRankCommand());
		$this->registerCommand(new StaffCommand());
		$this->registerCommand(new TeleportCommand());
		$this->registerCommand(new UnbanCommand());
		$this->registerCommand(new UnfreezeCommand());
		$this->registerCommand(new UnmuteCommand());
		$this->registerCommand(new VanishCommand());
		$this->registerCommand(new WhitelistCommand());
		$this->registerCommand(new XoopCommand());
		$this->registerCommand(new TestCommand());
	}

	private function unregisterCommand(string $commandName) : void{
		$commandMap = $this->getServer()->getCommandMap();
		if(($cmd = $commandMap->getCommand($commandName)) !== null){
			$commandMap->unregister($cmd);
		}
	}

	private function registerCommand(Command $command) : void{
		$this->getServer()->getCommandMap()->register($command->getName(), $command);
	}

	private function registerGenerators() : void{
		GeneratorManager::getInstance()->addGenerator(VoidGenerator::class, "void", fn() => null, true);
	}

	protected function onDisable() : void{
		DiscordUtil::sendStatus(false);
		BlockRemoverHandler::shutdown();
		CosmeticManager::shutdown();
		QueueHandler::shutdown();
		DatabaseManager::getMainDatabase()->close();
		DatabaseManager::getExtraDatabase()->close();
		LogMonitor::debugLog("SERVER: CLOSED");
		LogMonitor::shutdown();
	}
}
