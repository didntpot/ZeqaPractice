<?php

declare(strict_types=1);

namespace zodiax\discord;

use DateTime;
use zodiax\discord\misc\Embed;
use zodiax\discord\misc\Message;
use zodiax\discord\misc\Webhook;
use zodiax\PracticeCore;

class DiscordUtil{

	private static array $webhooks;
	private static Embed $embed;
	private static Message $message;

	public static function initialize() : void{
		$webhookInfo = PracticeCore::getWebhookInfo();
		self::$webhooks["status"] = new Webhook($webhookInfo["status"]);
		self::$webhooks["logs"] = new Webhook($webhookInfo["logs"]);
		self::$webhooks["ban"] = new Webhook($webhookInfo["ban"]);
		self::$webhooks["sync"] = new Webhook($webhookInfo["sync"]);
		self::$embed = new Embed();
		self::$message = new Message();
	}

	public static function sendStatus(bool $online) : void{
		self::$message->setUsername(PracticeCore::NAME);
		self::$message->setAvatarURL(PracticeCore::getLogoInfo());
		self::$embed->setTitle("Status");
		self::$embed->setDescription(PracticeCore::getRegionInfo() . " is now " . ($online ? "online" : "offline"));
		self::$embed->setColor(($online ? 0x00FF00 : 0xFF0000));
		self::$embed->setThumbnail(PracticeCore::getLogoInfo());
		self::$embed->setTimestamp(new DateTime("NOW"));
		if(!$online && !PracticeCore::isRestarting()){
			self::$message->setContent("'" . PracticeCore::getRegionInfo() . "' Crashed <@440475546821066755>");
		}
		self::$message->addEmbed(self::$embed);
		$webhook = clone self::$webhooks["status"];
		$webhook->send(self::$message);
		self::$message->clear();
		self::$embed->clear();
	}

	public static function sendLogs(string $description, bool $embed, int $color = 0xFCD403, string $thumbnail = "") : void{
		self::$message->setUsername(PracticeCore::NAME);
		self::$message->setAvatarURL(PracticeCore::getLogoInfo());
		if($embed){
			self::$embed->setDescription($description);
			self::$embed->setColor($color);
			self::$embed->setTimestamp(new DateTime("NOW"));
			self::$message->addEmbed(self::$embed);
		}else{
			self::$message->setContent($description);
		}
		$webhook = clone self::$webhooks["logs"];
		$webhook->send(self::$message);
		self::$message->clear();
		self::$embed->clear();
	}

	public static function sendBan(string $description, bool $embed, int $color = 0xFCD403, string $thumbnail = "") : void{
		self::$message->setUsername(PracticeCore::NAME);
		self::$message->setAvatarURL(PracticeCore::getLogoInfo());
		if($embed){
			self::$embed->setDescription($description);
			self::$embed->setColor($color);
			if($thumbnail !== ""){
				self::$embed->setThumbnail($thumbnail);
			}
			self::$embed->setTimestamp(new DateTime("NOW"));
			self::$message->addEmbed(self::$embed);
		}else{
			self::$message->setContent($description);
		}
		$webhook = clone self::$webhooks["ban"];
		$webhook->send(self::$message);
		self::$message->clear();
		self::$embed->clear();
	}

	public static function sendSyncLogs(string $description) : void{
		self::$message->setUsername(PracticeCore::NAME);
		self::$message->setAvatarURL(PracticeCore::getLogoInfo());
		self::$message->setContent($description);
		$webhook = clone self::$webhooks["sync"];
		$webhook->send(self::$message);
		self::$message->clear();
	}
}
