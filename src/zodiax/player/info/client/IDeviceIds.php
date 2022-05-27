<?php

declare(strict_types=1);

namespace zodiax\player\info\client;

interface IDeviceIds{

	const UNKNOWN = -1;
	const ANDROID = 1;
	const IOS = 2;
	const OSX = 3;
	const FIREOS = 4;
	const VRGEAR = 5;
	const VRHOLOLENS = 6;
	const WINDOWS_10 = 7;
	const WINDOWS_32 = 8;
	const DEDICATED = 9;
	const TVOS = 10;
	const PS4 = 11;
	const SWITCH = 12;
	const XBOX = 13;
	const LINUX = 20;

	const KEYBOARD = 1;
	const TOUCH = 2;
	const CONTROLLER = 3;
	const MOTION_CONTROLLER = 4;

	const DEVICE_OS_VALUES = [self::UNKNOWN => "Unknown", self::ANDROID => "Android", self::IOS => "iOS", self::OSX => "OSX", self::FIREOS => "FireOS", self::VRGEAR => "VRGear", self::VRHOLOLENS => "VRHololens", "Win10", self::WINDOWS_32 => "Win32", self::DEDICATED => "Dedicated", self::TVOS => "TVOS", self::PS4 => "PS4", self::SWITCH => "Nintendo Switch", self::XBOX => "Xbox", self::LINUX => "Linux"];

	const DEVICE_OS_VALUES_PACK_SUPPORT = [self::UNKNOWN => "", self::ANDROID => "", self::IOS => "", self::OSX => "OSX", self::FIREOS => "", self::VRGEAR => "VRGear", self::VRHOLOLENS => "VRHololens", self::WINDOWS_10 => "", self::WINDOWS_32 => "Win32", self::DEDICATED => "Dedicated", self::TVOS => "TVOS", self::PS4 => "", self::SWITCH => "", self::XBOX => "", self::LINUX => ""];

	const NON_PE_DEVICES = [self::PS4 => true, self::WINDOWS_10 => true, self::XBOX => true, self::LINUX => true];

	const INPUT_VALUES = [self::UNKNOWN => "Unknown", self::KEYBOARD => "Keyboard", self::TOUCH => "Touch", self::CONTROLLER => "Controller", self::MOTION_CONTROLLER => "Motion-Controller"];

	const UI_PROFILE_VALUES = [self::UNKNOWN => "Unknown", "Classic UI", "Pocket UI"];
}
