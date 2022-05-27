<?php

declare(strict_types=1);

namespace zodiax\player\misc\cosmetic;

use InvalidArgumentException;
use pocketmine\entity\Skin;
use pocketmine\network\mcpe\protocol\types\ParticleIds;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlThread;
use RuntimeException;
use Webmozart\PathUtil\Path;
use zodiax\data\database\DatabaseManager;
use zodiax\player\misc\cosmetic\battlepass\BattlePass;
use zodiax\player\misc\cosmetic\gacha\GachaHandler;
use zodiax\player\misc\cosmetic\misc\CosmeticHolder;
use zodiax\player\misc\cosmetic\misc\CosmeticItem;
use zodiax\player\misc\cosmetic\misc\CosmeticQueue;
use zodiax\player\misc\cosmetic\misc\thread\CosmeticThread;
use zodiax\player\misc\cosmetic\misc\thread\CosmeticThreadPool;
use zodiax\player\PlayerManager;
use zodiax\PracticeCore;
use function base64_encode;
use function chr;
use function count;
use function file_exists;
use function file_get_contents;
use function getimagesize;
use function imagecolorat;
use function imagecreatefrompng;
use function imagedestroy;
use function in_array;
use function json_decode;
use function max;
use function method_exists;
use function mkdir;
use function ord;
use function round;
use function strlen;
use function strtolower;

class CosmeticManager{

	const ARTIFACT = 0;
	const CAPE = 1;
	const PROJECTILE = 2;
	const KILLPHRASE = 3;

	const DEFAULT = 0;
	const C = 1;
	const R = 2;
	const SR = 3;
	const UR = 4;
	const LIMITED = 5;

	private static string $defaultSkinData;
	private static string $defaultGeometryData;

	private static array $artifactDict = [];
	private static array $capeDict = [];
	private static array $projectileDict = [];
	private static array $killphraseDict = [];

	const BOUNDS_64_64 = 0;
	const BOUNDS_64_32 = self::BOUNDS_64_64;
	const BOUNDS_128_128 = 1;

	private static CosmeticThreadPool $cosmetic;
	private static array $cosmeticHolder;
	private static array $skinBounds;

	public static function initialize() : void{
		self::addCosmetic("0", "Default", self::ARTIFACT, self::DEFAULT, false);
		self::addCosmetic("1", "Antler", self::ARTIFACT, self::C);
		self::addCosmetic("2", "Backcap", self::ARTIFACT, self::C);
		self::addCosmetic("3", "Small Crown", self::ARTIFACT, self::C);
		self::addCosmetic("4", "Halo", self::ARTIFACT, self::C);
		self::addCosmetic("5", "Koala Hat", self::ARTIFACT, self::C);
		self::addCosmetic("6", "Classic Moustache", self::ARTIFACT, self::C);
		self::addCosmetic("7", "Question Mark", self::ARTIFACT, self::C);
		self::addCosmetic("8", "Rabbit Ears", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("9", "Thunder Cloud", self::ARTIFACT, self::C);
		self::addCosmetic("10", "UFO", self::ARTIFACT, self::C);
		self::addCosmetic("11", "Adidas Headband", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("12", "Nike Headband", self::ARTIFACT, self::LIMITED, false);
		self::addCosmetic("13", "Louis V. Headband", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("14", "Santa Hat", self::ARTIFACT, self::R);
		self::addCosmetic("15", "Witch Hat", self::ARTIFACT, self::R);
		self::addCosmetic("16", "Mini Angel Wing", self::ARTIFACT, self::SR);
		self::addCosmetic("17", "SWAT Shield", self::ARTIFACT, self::LIMITED, false);
		self::addCosmetic("18", "Katana Set", self::ARTIFACT, self::SR);
		self::addCosmetic("19", "Blaze Rod", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("20", "Blue Susanoo", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("21", "Angel Wing", self::ARTIFACT, self::UR);
		self::addCosmetic("22", "Red Kagune", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("23", "Blue Lightsaber", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("24", "Axe On Head", self::ARTIFACT, self::R);
		self::addCosmetic("25", "Leather Backpack", self::ARTIFACT, self::SR);
		self::addCosmetic("26", "Holding Banana", self::ARTIFACT, self::C);
		self::addCosmetic("27", "Cute Creeper", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("28", "Goat Horn", self::ARTIFACT, self::C);
		self::addCosmetic("29", "Gudoudame", self::ARTIFACT, self::LIMITED, false);
		self::addCosmetic("30", "Super Mini Angel Wing", self::ARTIFACT, self::R);
		self::addCosmetic("31", "Angel Wing 2", self::ARTIFACT, self::SR);
		self::addCosmetic("32", "Bald Headband", self::ARTIFACT, self::C);
		self::addCosmetic("33", "Black Angel Set", self::ARTIFACT, self::SR);
		self::addCosmetic("34", "Blue Wing", self::ARTIFACT, self::UR);
		self::addCosmetic("35", "Boxing Gloves", self::ARTIFACT, self::C);
		self::addCosmetic("36", "Bubble", self::ARTIFACT, self::UR);
		self::addCosmetic("37", "King Crown", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("38", "Devil Wing", self::ARTIFACT, self::SR);
		self::addCosmetic("39", "Dollar", self::ARTIFACT, self::C);
		self::addCosmetic("40", "Dragon Wing", self::ARTIFACT, self::UR);
		self::addCosmetic("41", "Ender Dragon Tail", self::ARTIFACT, self::SR);
		self::addCosmetic("42", "Ender Wing", self::ARTIFACT, self::SR);
		self::addCosmetic("43", "Fox", self::ARTIFACT, self::R);
		self::addCosmetic("44", "Sun Glasses", self::ARTIFACT, self::C);
		self::addCosmetic("45", "Headphone", self::ARTIFACT, self::C);
		self::addCosmetic("46", "Headphone & Note", self::ARTIFACT, self::UR);
		self::addCosmetic("47", "No.1 MLG RUSH", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("48", "Necktie", self::ARTIFACT, self::C);
		self::addCosmetic("49", "Phantom Wing", self::ARTIFACT, self::SR);
		self::addCosmetic("50", "Rabbit", self::ARTIFACT, self::R);
		self::addCosmetic("51", "Red Wing", self::ARTIFACT, self::UR);
		self::addCosmetic("52", "Rich Headband", self::ARTIFACT, self::SR);
		self::addCosmetic("53", "Sickle", self::ARTIFACT, self::C);
		self::addCosmetic("54", "Viking", self::ARTIFACT, self::R);
		self::addCosmetic("55", "Wave Bandanna", self::ARTIFACT, self::C);
		self::addCosmetic("56", "White Heart", self::ARTIFACT, self::R);
		self::addCosmetic("57", "Wither Head", self::ARTIFACT, self::SR);
		self::addCosmetic("58", "Purple Zeqa Bandanna", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("59", "Katana Wing", self::ARTIFACT, self::UR);
		self::addCosmetic("60", "Raijin Drums", self::ARTIFACT, self::SR);
		self::addCosmetic("61", "Shirasaya Flag", self::ARTIFACT, self::R);
		self::addCosmetic("62", "White Karate Gi", self::ARTIFACT, self::SR);
		self::addCosmetic("63", "Candy Cane", self::ARTIFACT, self::R);
		self::addCosmetic("64", "Green Yukata", self::ARTIFACT, self::UR);
		self::addCosmetic("65", "Pink Kimono", self::ARTIFACT, self::UR);
		self::addCosmetic("66", "Black Karate Gi", self::ARTIFACT, self::SR);
		self::addCosmetic("67", "Japan Headband", self::ARTIFACT, self::R);
		self::addCosmetic("68", "Tatakae", self::ARTIFACT, self::UR);
		self::addCosmetic("69", "Waist Lightsaber", self::ARTIFACT, self::C);
		self::addCosmetic("70", "Sumo Suit", self::ARTIFACT, self::UR);
		self::addCosmetic("71", "Cat Mask", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("72", "Dog Mask", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("73", "Little Doge", self::ARTIFACT, self::C);
		self::addCosmetic("74", "Little Frog", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("75", "Lean Pack", self::ARTIFACT, self::UR);
		self::addCosmetic("76", "Meme Glasses", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("77", "Menacing", self::ARTIFACT, self::SR);
		self::addCosmetic("78", "MLG Set", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("79", "Mushroom Cap", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("80", "Noob Mask", self::ARTIFACT, self::R);
		self::addCosmetic("81", "Pig Mask", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("82", "Push in P", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("83", "Virus Claws", self::ARTIFACT, self::SR);
		self::addCosmetic("84", "Keyboard Blade", self::ARTIFACT, self::C);
		self::addCosmetic("85", "Hacker Jetpack", self::ARTIFACT, self::R);
		self::addCosmetic("86", "Cyborg Suit", self::ARTIFACT, self::UR);
		self::addCosmetic("87", "Tech Wing", self::ARTIFACT, self::SR);
		self::addCosmetic("88", "Sky Island", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("89", "Muramasa", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("90", "Fire Wing", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("C1", "Hooded Man Costume", self::ARTIFACT, self::UR);
		self::addCosmetic("C2", "Reaper Costume", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("C3", "Banana Man Costume", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("C4", "Dilivery Boy Costume", self::ARTIFACT, self::SR);
		self::addCosmetic("C5", "Ducky Costume", self::ARTIFACT, self::UR);
		self::addCosmetic("C6", "Ghost Costume", self::ARTIFACT, self::SR);
		self::addCosmetic("C7", "Glitcher Costume", self::ARTIFACT, self::UR);
		self::addCosmetic("C8", "Jerry Costume", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("C9", "Pepe Costume", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("C10", "Pumpkin Man Costume", self::ARTIFACT, self::UR);
		self::addCosmetic("C11", "Puppet Monkey Costume", self::ARTIFACT, self::SR);
		self::addCosmetic("C12", "Spooderman Costume", self::ARTIFACT, self::SR);
		self::addCosmetic("C13", "Stonks Costume", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("C14", "Shrek Costume", self::ARTIFACT, self::R);
		self::addCosmetic("C15", "Robot Creeper Costume", self::ARTIFACT, self::UR);
		self::addCosmetic("C16", "Golden Totem Costume", self::ARTIFACT, self::LIMITED);
		self::addCosmetic("9999", "PROOF OF CONCEPT 128x", self::ARTIFACT, self::LIMITED);

		self::addCosmetic("0", "Default", self::CAPE, self::DEFAULT, false);
		self::addCosmetic("1", "Black Butterfly", self::CAPE, self::C);
		self::addCosmetic("2", "Dark Cross", self::CAPE, self::C);
		self::addCosmetic("3", "Day Dream", self::CAPE, self::C);
		self::addCosmetic("4", "Fake Wing", self::CAPE, self::C);
		self::addCosmetic("5", "Black L", self::CAPE, self::C);
		self::addCosmetic("6", "Orangest", self::CAPE, self::C);
		self::addCosmetic("7", "Chimera", self::CAPE, self::C);
		self::addCosmetic("8", "Darkblue L", self::CAPE, self::C);
		self::addCosmetic("9", "Double07", self::CAPE, self::C);
		self::addCosmetic("10", "White Slash", self::CAPE, self::C);
		self::addCosmetic("11", "Golden Heart", self::CAPE, self::C);
		self::addCosmetic("12", "Katana Symbol", self::CAPE, self::C);
		self::addCosmetic("13", "Herz", self::CAPE, self::C);
		self::addCosmetic("14", "BlackHeart", self::CAPE, self::C);
		self::addCosmetic("15", "Brown Chicken", self::CAPE, self::R);
		self::addCosmetic("16", "Crowned Clown", self::CAPE, self::R);
		self::addCosmetic("17", "Uchiha", self::CAPE, self::R);
		self::addCosmetic("18", "Sunset", self::CAPE, self::R);
		self::addCosmetic("19", "TikTok", self::CAPE, self::LIMITED, false);
		self::addCosmetic("20", "PePe", self::CAPE, self::R);
		self::addCosmetic("21", "Polar Bear", self::CAPE, self::R);
		self::addCosmetic("22", "Nike White", self::CAPE, self::LIMITED, false);
		self::addCosmetic("23", "No Man Sky", self::CAPE, self::SR);
		self::addCosmetic("24", "Mashmello", self::CAPE, self::SR);
		self::addCosmetic("25", "Hacker", self::CAPE, self::SR);
		self::addCosmetic("26", "The Starry Night", self::CAPE, self::SR);
		self::addCosmetic("27", "White Wolf", self::CAPE, self::LIMITED, false);
		self::addCosmetic("28", "The Rich", self::CAPE, self::UR);
		self::addCosmetic("29", "Bruhhh", self::CAPE, self::UR);
		self::addCosmetic("30", "BED", self::CAPE, self::UR);
		self::addCosmetic("31", "XOOP TWERKING", self::CAPE, self::LIMITED);
		self::addCosmetic("32", "1 MILL", self::CAPE, self::C);
		self::addCosmetic("33", "Three Blade", self::CAPE, self::C);
		self::addCosmetic("34", "Adam Warlock", self::CAPE, self::R);
		self::addCosmetic("35", "Purple Assasin", self::CAPE, self::LIMITED);
		self::addCosmetic("36", "Bear", self::CAPE, self::R);
		self::addCosmetic("37", "Black Hole", self::CAPE, self::LIMITED);
		self::addCosmetic("38", "Pastel MLG", self::CAPE, self::C);
		self::addCosmetic("39", "CawPe", self::CAPE, self::R);
		self::addCosmetic("40", "Shiba Inu", self::CAPE, self::SR);
		self::addCosmetic("41", "White Chicken", self::CAPE, self::R);
		self::addCosmetic("42", "Duck", self::CAPE, self::C);
		self::addCosmetic("43", "Fanstasic Blue", self::CAPE, self::R);
		self::addCosmetic("44", "Cute Cowboy", self::CAPE, self::LIMITED);
		self::addCosmetic("45", "Pastel Cute Face", self::CAPE, self::R);
		self::addCosmetic("46", "Yellow Demonic Smile", self::CAPE, self::C);
		self::addCosmetic("47", "Black Devil Smile", self::CAPE, self::R);
		self::addCosmetic("48", "Christmas 2021 - Snowman", self::CAPE, self::LIMITED);
		self::addCosmetic("49", "Christmas 2021 - Pine", self::CAPE, self::LIMITED);
		self::addCosmetic("50", "Christmas 2021 - Candy", self::CAPE, self::LIMITED);
		self::addCosmetic("51", "Christmas 2021 - Reindeer", self::CAPE, self::LIMITED);
		self::addCosmetic("52", "Ninja", self::CAPE, self::SR);
		self::addCosmetic("53", "Rising Sun", self::CAPE, self::C);
		self::addCosmetic("54", "Sakura Tree", self::CAPE, self::C);
		self::addCosmetic("55", "Cherry Blossom", self::CAPE, self::C);
		self::addCosmetic("56", "The Great Wave", self::CAPE, self::UR);
		self::addCosmetic("57", "Twitch", self::CAPE, self::LIMITED);
		self::addCosmetic("58", "Youtube", self::CAPE, self::LIMITED);
		self::addCosmetic("59", "Zeqa Staff", self::CAPE, self::LIMITED, false);
		self::addCosmetic("60", "Samurai Helmet", self::CAPE, self::R);
		self::addCosmetic("61", "Rick Roll", self::CAPE, self::LIMITED);
		self::addCosmetic("62", "Valentine 2022 - Bed Heart", self::CAPE, self::LIMITED);
		self::addCosmetic("63", "Valentine 2022 - Rose", self::CAPE, self::LIMITED);
		self::addCosmetic("64", "Zeqentine 2022", self::CAPE, self::LIMITED);
		self::addCosmetic("65", "Valentine 2022 - Black Heart", self::CAPE, self::LIMITED);
		self::addCosmetic("66", "Valentine 2022 - Teddy", self::CAPE, self::LIMITED);
		self::addCosmetic("67", "Valentine 2022 - White Heart", self::CAPE, self::LIMITED);
		self::addCosmetic("68", "Baby Yoda", self::CAPE, self::R);
		self::addCosmetic("69", "Confused Winnie", self::CAPE, self::C);
		self::addCosmetic("70", "Cukiee", self::CAPE, self::SR);
		self::addCosmetic("71", "Emlu", self::CAPE, self::SR);
		self::addCosmetic("72", "Minion", self::CAPE, self::UR);
		self::addCosmetic("73", "Tea Time Kermit", self::CAPE, self::UR);
		self::addCosmetic("74", "R3 X ZEQA", self::CAPE, self::LIMITED);
		self::addCosmetic("75", "Missing Texture", self::CAPE, self::UR);
		self::addCosmetic("76", "Code", self::CAPE, self::UR);
		self::addCosmetic("77", "Console", self::CAPE, self::C);
		self::addCosmetic("78", "Smart Phone", self::CAPE, self::SR);
		self::addCosmetic("79", "Controller", self::CAPE, self::R);
		self::addCosmetic("80", "Nintenre Switch", self::CAPE, self::SR);
		self::addCosmetic("81", "Easter 2022 - Basket", self::CAPE, self::LIMITED);
		self::addCosmetic("82", "Easter 2022 - Bunny", self::CAPE, self::LIMITED);
		self::addCosmetic("83", "Easter 2022 - Moai", self::CAPE, self::LIMITED);
		self::addCosmetic("84", "Easter 2022 - Egg", self::CAPE, self::LIMITED);
		self::addCosmetic("85", "Easter 2022 - Basket 2", self::CAPE, self::LIMITED);
		self::addCosmetic("86", "Easter 2022 - Bunny 2", self::CAPE, self::LIMITED);
		self::addCosmetic("87", "Berry", self::CAPE, self::LIMITED);
		self::addCosmetic("88", "Kirby", self::CAPE, self::LIMITED);
		self::addCosmetic("89", "Rose", self::CAPE, self::LIMITED);
		self::addCosmetic("90", "Scroll", self::CAPE, self::LIMITED);
		self::addCosmetic("91", "Trophy", self::CAPE, self::LIMITED);
		self::addCosmetic("92", "Earth Board", self::CAPE, self::LIMITED);

		self::addCosmetic("0", "Default", self::PROJECTILE, self::DEFAULT, false, "0");
		self::addCosmetic("1", "Explode", self::PROJECTILE, self::R, true, (string) ParticleIds::EXPLODE);
		self::addCosmetic("2", "Splash", self::PROJECTILE, self::C, true, (string) ParticleIds::SPLASH);
		self::addCosmetic("3", "Water Splash", self::PROJECTILE, self::C, true, (string) ParticleIds::WATER_SPLASH);
		self::addCosmetic("4", "Critical", self::PROJECTILE, self::C, true, (string) ParticleIds::CRITICAL);
		self::addCosmetic("5", "Smoke", self::PROJECTILE, self::LIMITED, false, (string) ParticleIds::SMOKE);
		self::addCosmetic("6", "Mob Spell", self::PROJECTILE, self::LIMITED, false, (string) ParticleIds::MOB_SPELL);
		self::addCosmetic("7", "Flame", self::PROJECTILE, self::SR, true, (string) ParticleIds::FLAME);
		self::addCosmetic("8", "Lava", self::PROJECTILE, self::R, true, (string) ParticleIds::LAVA);
		self::addCosmetic("9", "Redstone", self::PROJECTILE, self::R, true, (string) ParticleIds::REDSTONE);
		self::addCosmetic("10", "Heart", self::PROJECTILE, self::SR, true, (string) ParticleIds::HEART);
		self::addCosmetic("11", "Enchantment Table", self::PROJECTILE, self::UR, true, (string) ParticleIds::ENCHANTMENT_TABLE);
		self::addCosmetic("12", "Villager Happy", self::PROJECTILE, self::UR, true, (string) ParticleIds::VILLAGER_HAPPY);
		self::addCosmetic("13", "Villager Angry", self::PROJECTILE, self::LIMITED, false, (string) ParticleIds::VILLAGER_ANGRY);

		self::addCosmetic("0", "Default", self::KILLPHRASE, self::DEFAULT, false, "{v}{ov}" . TextFormat::GRAY . "was killed by {k}{ok}");
		self::addCosmetic("1", "Yeeted", self::KILLPHRASE, self::UR, true, "{v}{ov}" . TextFormat::GRAY . "was yeeted by {k}{ok}");
		self::addCosmetic("2", "Kicked Out", self::KILLPHRASE, self::R, true, "{v}{ov}" . TextFormat::GRAY . "was kicked out by {k}{ok}");
		self::addCosmetic("3", "Smited", self::KILLPHRASE, self::C, true, "{v}{ov}" . TextFormat::GRAY . "was smited by {k}{ok}");
		self::addCosmetic("4", "Couldn't Withstand", self::KILLPHRASE, self::R, true, "{v}{ov}" . TextFormat::GRAY . "couldn't withstand {k}{ok}");
		self::addCosmetic("5", "Shook Hand", self::KILLPHRASE, self::UR, true, "{v}{ov}" . TextFormat::GRAY . "lost and shook hands with {k}{ok}");
		self::addCosmetic("6", "Knocked Out", self::KILLPHRASE, self::C, true, "{v}{ov}" . TextFormat::GRAY . "was knocked out by {k}{ok}");
		self::addCosmetic("7", "Stabbed2Death", self::KILLPHRASE, self::R, true, "{v}{ov}" . TextFormat::GRAY . "was stabbed to death by {k}{ok}");
		self::addCosmetic("8", "Grilled", self::KILLPHRASE, self::SR, true, "{v}{ov}" . TextFormat::GRAY . "was grilled by {k}{ok}");
		self::addCosmetic("9", "Too Scared", self::KILLPHRASE, self::C, true, "{v}{ov}" . TextFormat::GRAY . "was too scared of {k}{ok}");
		self::addCosmetic("10", "Snapped", self::KILLPHRASE, self::C, true, "{v}{ov}" . TextFormat::GRAY . "was snapped by {k}{ok}");
		self::addCosmetic("11", "Destroyed", self::KILLPHRASE, self::SR, true, "{v}{ov}" . TextFormat::GRAY . "got destroyed by {k}{ok}");
		self::addCosmetic("12", "Slapped", self::KILLPHRASE, self::SR, true, "{v}{ov}" . TextFormat::GRAY . "was slapped by {k}{ok}");
		self::addCosmetic("13", "Back2Bed", self::KILLPHRASE, self::UR, true, "{k}{ok}" . TextFormat::GRAY . "sent {v}{ov}" . TextFormat::GRAY . "to bed");
		self::addCosmetic("14", "Voting", self::KILLPHRASE, self::LIMITED, false, "{k}{ok}" . TextFormat::GRAY . "voted {v}{ov}" . TextFormat::GRAY . "out");
		self::addCosmetic("15", "Cooked", self::KILLPHRASE, self::LIMITED, false, "{v}{ov}" . TextFormat::GRAY . "was cooked by {k}{ok}");
		self::addCosmetic("16", "Almost Banned", self::KILLPHRASE, self::LIMITED, false, "{v}{ov}" . TextFormat::GRAY . "almost got banned by {k}{ok}");
		self::addCosmetic("17", "Work To Death", self::KILLPHRASE, self::LIMITED, true, "{v}{ov}" . TextFormat::GRAY . "was forced to work till death by {k}{ok}");
		self::addCosmetic("18", "Buried in Money", self::KILLPHRASE, self::LIMITED, true, "{v}{ov}" . TextFormat::GRAY . "was buried in money till death by {k}{ok}");
		self::addCosmetic("19", "To The Moon", self::KILLPHRASE, self::LIMITED, true, "{k}{ok}" . TextFormat::GRAY . "sent {v}{ov}" . TextFormat::GRAY . "to the MOON");
		self::addCosmetic("20", "Kimino Toriko", self::KILLPHRASE, self::C, true, "{v}{ov}" . TextFormat::GRAY . "kimino toriko {k}{ok}" . TextFormat::GRAY . "at first sight");
		self::addCosmetic("21", "O Mai Wa Mou Shindeiru", self::KILLPHRASE, self::SR, true, "{k}{ok}" . TextFormat::GRAY . ": O Mai Wa Mou Shindeiru, {v}{ov}" . TextFormat::GRAY . ":Nani!");
		self::addCosmetic("22", "MUDA MUDA MUDA", self::KILLPHRASE, self::R, true, "{k}{ok}" . TextFormat::GRAY . "MUDA MUDA MUDA MUDA MUDA {v}{ov}" . TextFormat::GRAY . "till death");
		self::addCosmetic("23", "Be My Valentine", self::KILLPHRASE, self::LIMITED, true, "{v}{ov}" . TextFormat::GRAY . " was forced to be {k}{ok}'s" . TextFormat::GRAY . "valentine until the end of time");
		self::addCosmetic("24", "Rick Rolling", self::KILLPHRASE, self::C, true, "{k}{ok}" . TextFormat::GRAY . "rick rolled {v}{ov}" . TextFormat::GRAY . "1,000 times");
		self::addCosmetic("25", "Xooper Cool", self::KILLPHRASE, self::C, true, "{v}{ov}" . TextFormat::GRAY . "was killed by a xooper cool player named {k}{ok}");
		self::addCosmetic("26", "Xooperior", self::KILLPHRASE, self::R, true, "{k}{ok}" . TextFormat::GRAY . "is way more xooperior than {v}{ov}");
		self::addCosmetic("27", "BioHack", self::KILLPHRASE, self::R, true, "{k}{ok}" . TextFormat::GRAY . "hacked and destroyed {v}{ov}'s" . TextFormat::GRAY . "brains");
		self::addCosmetic("28", "RTX3090", self::KILLPHRASE, self::C, true, "{v}{ov}" . TextFormat::GRAY . "was smashed by {k}{ok}" . TextFormat::GRAY . "using an RTX3090");
		self::addCosmetic("29", "Bitcoin", self::KILLPHRASE, self::R, true, "{k}{ok}" . TextFormat::GRAY . "stole all of {v}{ov}'s" . TextFormat::GRAY . " bitcoins");
		self::addCosmetic("30", "Disconnect", self::KILLPHRASE, self::C, true, "{k}{ok}" . TextFormat::GRAY . "forced {v}{ov}" . TextFormat::GRAY . "to disconnect");
		self::addCosmetic("31", "Salute", self::KILLPHRASE, self::LIMITED, true, "{v}{ov}" . TextFormat::GRAY . "salute {k}{ok}" . TextFormat::GRAY . "for beating him");
		self::addCosmetic("32", "I Pet You", self::KILLPHRASE, self::LIMITED, true, "{k}{ok}" . TextFormat::GRAY . "pet {v}{ov}" . TextFormat::GRAY . "for his cutie");

		GachaHandler::initialize();
		BattlePass::initialize();

		self::$defaultSkinData = CosmeticManager::getSkinDataFromPNG(Path::join(PracticeCore::getResourcesFolder(), "cosmetic", "default_skin.png"));
		self::$defaultGeometryData = file_get_contents(Path::join(PracticeCore::getResourcesFolder(), "cosmetic", "default_geometry.json"));

		self::$cosmeticHolder = [];
		$cubes = self::getCubes(json_decode('{"bones":[{"name":"body","cubes":[{"size":[8,12,4],"uv":[16,16]}]},{"name":"head","cubes":[{"size":[8,8,8],"uv":[0,0]}]},{"name":"rightArm","cubes":[{"size":[4,12,4],"uv":[40,16]}]},{"name":"leftArm","cubes":[{"size":[4,12,4],"uv":[32,48]}]},{"name":"rightLeg","cubes":[{"size":[4,12,4],"uv":[0,16]}]},{"name":"leftLeg","cubes":[{"size":[4,12,4],"uv":[16,48]}]}]}', true));
		self::$skinBounds[self::BOUNDS_64_64] = self::getSkinBounds($cubes);
		self::$skinBounds[self::BOUNDS_128_128] = self::getSkinBounds($cubes, 2.0);

		@mkdir(Path::join(PracticeCore::getDataFolderPath(), "players", "skin"));
		if(PracticeCore::isLobby()){
			@mkdir(Path::join(PracticeCore::getDataFolderPath(), "players", "head"));
		}
		$workers = (new Config(Path::join(PracticeCore::getDataFolderPath(), "settings.yml")))->get("database")["worker-limit"];
		$class_loaders = [];
		$devirion = Server::getInstance()->getPluginManager()->getPlugin("DEVirion");
		if($devirion !== null){
			if(!method_exists($devirion, "getVirionClassLoader")){
				throw new RuntimeException();
			}
			$class_loaders[] = Server::getInstance()->getLoader();
			$class_loaders[] = $devirion->getVirionClassLoader();
		}

		self::$cosmetic = new CosmeticThreadPool();
		$workers = 1;
		for($i = 0; $i < $workers; $i++){
			$thread = new CosmeticThread(self::$cosmetic->getNotifier());
			if(count($class_loaders) > 0){
				$thread->setClassLoaders($class_loaders);
			}
			self::$cosmetic->addWorker($thread);
		}
		self::$cosmetic->start();
	}

	private static function addCosmetic(string $id, string $displayName, int $type, int $rarity, $isTradable = true, ?string $content = null){
		$item = new CosmeticItem($id, $displayName, $type, $rarity, $content, $isTradable);
		switch($type){
			case self::ARTIFACT :
				$item->setContent(self::getArtifactData(Path::join(PracticeCore::getResourcesFolder(), "cosmetic", "artifact", "$id.json")));
				self::$artifactDict[$id] = $item;
				break;
			case self::CAPE :
				$item->setContent(self::getSkinDataFromPNG(Path::join(PracticeCore::getResourcesFolder(), "cosmetic", "cape", "$id.png")));
				self::$capeDict[$id] = $item;
				break;
			case self::PROJECTILE :
				self::$projectileDict[$id] = $item;
				break;
			case self::KILLPHRASE :
				self::$killphraseDict[$id] = $item;
				break;
		}
	}

	public static function getAllArtifact() : array{
		return self::$artifactDict;
	}

	public static function getArtifactFromId(string $id) : ?CosmeticItem{
		return self::$artifactDict[$id] ?? null;
	}

	public static function getAllCape() : array{
		return self::$capeDict;
	}

	public static function getCapeFromId(string $id) : ?CosmeticItem{
		return self::$capeDict[$id] ?? null;
	}

	public static function getAllProjectile() : array{
		return self::$projectileDict;
	}

	public static function getProjectileFromId(string $id) : ?CosmeticItem{
		return self::$projectileDict[$id] ?? null;
	}

	public static function getAllKillPhrase() : array{
		return self::$killphraseDict;
	}

	public static function getKillPhraseFromId(string $id) : ?CosmeticItem{
		return self::$killphraseDict[$id] ?? null;
	}

	public static function getCosmeticFromId(int $type, string $id) : ?CosmeticItem{
		return match ($type) {
			self::ARTIFACT => self::getArtifactFromId($id),
			self::CAPE => self::getCapeFromId($id),
			self::PROJECTILE => self::getProjectileFromId($id),
			self::KILLPHRASE => self::getKillPhraseFromId($id),
		};
	}

	public static function getCosmeticItemFromList(array $ids, int $type, int $mode = 0) : array{
		$items = [];
		if($mode === 1){
			foreach($ids as $id){
				if(($item = self::getCosmeticFromId($type, $id)) !== null){
					$items[] = $item->getDisplayName();
				}
			}
		}elseif($mode === 2){
			foreach($ids as $id){
				if(($item = self::getCosmeticFromId($type, $id)) !== null){
					$items[] = $item->getRarity();
				}
			}
		}else{
			foreach($ids as $id){
				if(($item = self::getCosmeticFromId($type, $id)) !== null){
					$items[] = $item;
				}
			}
		}
		return $items;
	}

	public static function updateHolder(string $name, string $skinData) : void{
		if(isset(self::$cosmeticHolder[$name])){
			self::$cosmeticHolder[$name]->setSkin($skinData);
			unset(self::$cosmeticHolder[$name]);
		}
	}

	public static function saveDefaultSkin(Player $player, Skin $skin) : void{
		self::$cosmetic->getLeastBusyWorker()->queue(new CosmeticQueue(CosmeticQueue::SAVE, $player->getName(), self::getSkinTransparencyPercentage($skinData = $skin->getSkinData()) > 4, $skinData));
	}

	public static function setStrippedSkin(Player $player, Skin $skin, bool $save = false) : void{
		$name = $player->getName();
		$skinData = $skin->getSkinData();
		$geometryData = self::$defaultGeometryData;
		$artifactPath = Path::join(PracticeCore::getDataFolderPath(), "players", "skin", "$name.png");
		$needToMerge = true;
		$itemInfo = PlayerManager::getSession($player)->getItemInfo();
		if($itemInfo->getArtifact(true) !== ""){
			$artifactPath = Path::join(PracticeCore::getResourcesFolder(), "cosmetic", "artifact", "{$itemInfo->getArtifact()}.png");
			if(($itemInfo->getArtifact()[0] ?? "") === "C"){
				$needToMerge = false;
			}
			$geometryData = $itemInfo->getArtifact(true);
		}
		self::$cosmeticHolder[$name] = new CosmeticHolder($player, new Skin($skin->getSkinId(), $skinData, $itemInfo->getCape(true), ($skin->getGeometryName() === "geometry.humanoid.customSlim" ? "geometry.humanoid.customSlim" : "geometry.humanoid.custom"), $geometryData));
		$worker = self::$cosmetic->getLeastBusyWorker();
		if($save){
			$worker->queue(new CosmeticQueue(CosmeticQueue::SAVE, $name, self::getSkinTransparencyPercentage($skinData) > 4 || !in_array($skin->getGeometryName(), ["geometry.humanoid.customSlim", "geometry.humanoid.custom"], true), $skinData));
		}
		$worker->queue(new CosmeticQueue(CosmeticQueue::LOAD, $name, $needToMerge, $artifactPath));
	}

	public static function getSkinTransparencyPercentage(string $skinData) : int{
		switch(strlen($skinData)){
			case 8192:
				$maxX = 64;
				$maxY = 32;
				$bounds = self::$skinBounds[self::BOUNDS_64_32];
				break;
			case 16384:
				$maxX = 64;
				$maxY = 64;
				$bounds = self::$skinBounds[self::BOUNDS_64_64];
				break;
			case 65536:
				$maxX = 128;
				$maxY = 128;
				$bounds = self::$skinBounds[self::BOUNDS_128_128];
				break;
			default:
				throw new InvalidArgumentException("Inappropriate skin data length: " . strlen($skinData));
		}
		$transparentPixels = $pixels = 0;
		foreach($bounds as $bound){
			if($bound["max"]["x"] > $maxX || $bound["max"]["y"] > $maxY){
				continue;
			}
			for($y = $bound["min"]["y"]; $y <= $bound["max"]["y"]; $y++){
				for($x = $bound["min"]["x"]; $x <= $bound["max"]["x"]; $x++){
					$key = (($maxX * $y) + $x) * 4;
					$a = ord($skinData[$key + 3]);
					if($a < 127){
						++$transparentPixels;
					}
					++$pixels;
				}
			}
		}
		return (int) round($transparentPixels * 100 / max(1, $pixels));
	}

	public static function getSkinDataFromPNG(string $path) : string{
		$bytes = "";
		if(!file_exists($path)){
			return $bytes;
		}
		$img = imagecreatefrompng($path);
		[$width, $height] = getimagesize($path);
		for($y = 0; $y < $height; ++$y){
			for($x = 0; $x < $width; ++$x){
				$argb = imagecolorat($img, $x, $y);
				$bytes .= chr(($argb >> 16) & 0xff) . chr(($argb >> 8) & 0xff) . chr($argb & 0xff) . chr((~($argb >> 24) << 1) & 0xff);
			}
		}
		imagedestroy($img);
		return $bytes;
	}

	public static function getCubes(array $geometryData) : array{
		$cubes = [];
		foreach($geometryData["bones"] as $bone){
			if(!isset($bone["cubes"])){
				continue;
			}
			if($bone["mirror"] ?? false){
				throw new InvalidArgumentException("Unsupported geometry data");
			}
			foreach($bone["cubes"] as $cubeData){
				$cube = [];
				$cube["x"] = $cubeData["size"][0];
				$cube["y"] = $cubeData["size"][1];
				$cube["z"] = $cubeData["size"][2];
				$cube["uvX"] = $cubeData["uv"][0];
				$cube["uvY"] = $cubeData["uv"][1];
				$cubes[] = $cube;
			}
		}
		return $cubes;
	}

	public static function getSkinBounds(array $cubes, float $scale = 1.0) : array{
		$bounds = [];
		foreach($cubes as $cube){
			$x = (int) ($scale * $cube["x"]);
			$y = (int) ($scale * $cube["y"]);
			$z = (int) ($scale * $cube["z"]);
			$uvX = (int) ($scale * $cube["uvX"]);
			$uvY = (int) ($scale * $cube["uvY"]);
			$bounds[] = ["min" => ["x" => $uvX + $z, "y" => $uvY], "max" => ["x" => $uvX + $z + (2 * $x) - 1, "y" => $uvY + $z - 1]];
			$bounds[] = ["min" => ["x" => $uvX, "y" => $uvY + $z], "max" => ["x" => $uvX + (2 * ($z + $x)) - 1, "y" => $uvY + $z + $y - 1]];
		}
		return $bounds;
	}

	public static function getArtifactData(string $path) : string{
		if(!file_exists($path)){
			return "";
		}
		return file_get_contents($path);
	}

	public static function getServerDefaultSkin(Skin $skin) : Skin{
		return new Skin($skin->getSkinId(), self::$defaultSkinData, "", "geometry.humanoid.custom", self::$defaultGeometryData);
	}

	public static function triggerGarbageCollector() : void{
		self::$cosmetic->triggerGarbageCollector();
	}

	public static function shutdown() : void{
		self::$cosmetic->shutdown();
	}

	public static function saveSkinData(string $name) : void{
		if(file_exists($path = Path::join(PracticeCore::getDataFolderPath(), "players", "head", "$name.png"))){
			$lowername = strtolower($name);
			$data = base64_encode(file_get_contents($path));
			DatabaseManager::getExtraDatabase()->executeImplRaw([0 => "INSERT INTO PlayersData (name, sensitivename, skin) VALUES ('$lowername', '$name', '$data') ON DUPLICATE KEY UPDATE sensitivename = '$name', skin = '$data'"], [0 => []], [0 => SqlThread::MODE_GENERIC], function(){ }, null);
		}
	}
}
