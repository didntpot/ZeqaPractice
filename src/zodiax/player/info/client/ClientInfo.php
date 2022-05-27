<?php

declare(strict_types=1);

namespace zodiax\player\info\client;

use Closure;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use poggit\libasynql\SqlThread;
use zodiax\data\database\DatabaseManager;
use zodiax\player\PlayerManager;
use function array_values;
use function count;
use function in_array;
use function is_array;
use function serialize;
use function strtolower;
use function trim;
use function unserialize;

class ClientInfo implements IDeviceIds{

	private string $version;
	private string $xuid;
	private string $deviceModelRaw;
	private string $deviceModel;
	private string $selfSignedID;
	private int $clientRandomID;
	private int $deviceOS;
	private string $deviceIdRaw;
	private int $inputAtLogin;
	private int $uiProfileAtLogin;
	private array $aliasData;
	private array $alts;

	public function init(array $clientData, array $deviceModels) : void{
		$this->version = $clientData["GameVersion"] ?? ProtocolInfo::MINECRAFT_VERSION;
		$deviceModel = (string) ($clientData["DeviceModel"] ?? "Unknown");
		$deviceOS = (int) ($clientData["DeviceOS"] ?? self::UNKNOWN);
		if(trim($deviceModel) === ""){
			switch($deviceOS){
				case self::ANDROID:
					$deviceOS = self::LINUX;
					$deviceModel = "Linux";
					break;
				case self::XBOX:
					$deviceModel = "Xbox One";
					break;
			}
		}
		$this->deviceModelRaw = $deviceModel;
		$this->deviceModel = $deviceModels[$this->deviceModelRaw] ?? $this->deviceModelRaw;
		$this->deviceOS = $deviceOS;
		$this->deviceIdRaw = (string) ($clientData["DeviceId"] ?? "Unknown");
		$this->inputAtLogin = (int) ($clientData["CurrentInputMode"] ?? self::UNKNOWN);
		$this->uiProfileAtLogin = (int) ($clientData["UIProfile"] ?? self::UNKNOWN);
		$this->clientRandomID = (int) ($clientData["ClientRandomId"] ?? self::UNKNOWN);
		$this->selfSignedID = (string) ($clientData["SelfSignedId"] ?? "Unknown");
		$this->xuid = (string) ($clientData["Xuid"] ?? "Unknown");
		$this->aliasData = ["ClientRandomId" => [(string) $this->clientRandomID], "DeviceId" => [$this->deviceIdRaw], "SelfSignedId" => [$this->selfSignedID], "Xuid" => [$this->xuid]];
		if(isset($clientData["alias"]) && $clientData["alias"] !== ""){
			$aliasData = unserialize($clientData["alias"]);
			if(is_array($aliasData)){
				if(isset($aliasData["ClientRandomId"])){
					if(!in_array((string) $this->clientRandomID, $aliasData["ClientRandomId"], true)){
						$aliasData["ClientRandomId"][] = (string) $this->clientRandomID;
					}
				}else{
					$aliasData["ClientRandomId"] = [(string) $this->clientRandomID];
				}
				if(isset($aliasData["DeviceId"])){
					if(!in_array($this->deviceIdRaw, $aliasData["DeviceId"], true)){
						$aliasData["DeviceId"][] = $this->deviceIdRaw;
					}
				}else{
					$aliasData["DeviceId"] = [$this->deviceIdRaw];
				}
				if(isset($aliasData["SelfSignedId"])){
					if(!in_array($this->selfSignedID, $aliasData["SelfSignedId"], true)){
						$aliasData["SelfSignedId"][] = $this->selfSignedID;
					}
				}else{
					$aliasData["SelfSignedId"] = [$this->selfSignedID];
				}
				if(isset($aliasData["Xuid"])){
					if(!in_array($this->xuid, $aliasData["Xuid"], true)){
						$aliasData["Xuid"][] = $this->xuid;
					}
				}else{
					$aliasData["Xuid"] = [$this->xuid];
				}
				$this->aliasData = $aliasData;
			}
		}
		foreach($this->aliasData as $key => $data){
			if(is_array($data)){
				foreach($data as $k => $value){
					$value = trim((string) $value);
					if(in_array($value, ["Unknown", "-1", ""], true)){
						unset($this->aliasData[$key][$k]);
					}else{
						$this->aliasData[$key][$k] = $value;
					}
				}
				$this->aliasData[$key] = array_values($this->aliasData[$key]);
				if(count($this->aliasData[$key]) === 0){
					unset($this->aliasData[$key]);
				}
			}
		}
		$this->alts = [$clientData["Username"] ?? $clientData["ThirdPartyName"]];
		if(isset($clientData["alts"])){
			$this->alts = $clientData["alts"];
		}
		PlayerManager::loadPlayerData($clientData["Username"] ?? $clientData["ThirdPartyName"]);
	}

	public function getVersion() : string{
		return $this->version;
	}

	public function getXuid() : string{
		return $this->xuid;
	}

	public function isPE() : bool{
		return !isset(self::NON_PE_DEVICES[$this->deviceOS]) && $this->getInputAtLogin() === self::TOUCH;
	}

	public function getInputAtLogin(bool $asString = false) : int|string{
		return $asString ? self::INPUT_VALUES[$this->inputAtLogin] ?? "Unknown" : $this->inputAtLogin;
	}

	public function getRawDeviceId() : string{
		return $this->deviceIdRaw;
	}

	public function getDeviceOS(bool $asString = false, bool $asUnicode = false) : int|string{
		return $asString ? ($asUnicode ? self::DEVICE_OS_VALUES_PACK_SUPPORT[$this->deviceOS] ?? "Unknown" : self::DEVICE_OS_VALUES[$this->deviceOS] ?? "Unknown") : $this->deviceOS;
	}

	public function getRawDeviceModel() : string{
		return $this->deviceModelRaw;
	}

	public function getDeviceModel() : string{
		return $this->deviceModel;
	}

	public function getUIProfile() : string{
		return self::UI_PROFILE_VALUES[$this->uiProfileAtLogin];
	}

	public function getClientRandomId() : int{
		return $this->clientRandomID;
	}

	public function getSelfSignedId() : string{
		return $this->selfSignedID;
	}

	public function getAltAccounts() : array{
		return $this->alts;
	}

	public function checkInput(int $input) : bool{
		if($this->inputAtLogin !== $input){
			$this->inputAtLogin = $input;
			return true;
		}
		return false;
	}

	public function save(string $name, Closure $closure) : void{
		if(isset($this->aliasData)){
			$lowername = strtolower($name);
			$data = serialize($this->aliasData);
			DatabaseManager::getExtraDatabase()->executeImplRaw([0 => "INSERT INTO PlayersData (name, sensitivename, alias) VALUES ('$lowername', '$name', '$data') ON DUPLICATE KEY UPDATE sensitivename = '$name', alias = '$data'"], [0 => []], [0 => SqlThread::MODE_GENERIC], $closure, $closure);
		}
	}
}