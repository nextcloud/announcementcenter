<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Tests\Activity;

use OCA\AnnouncementCenter\Activity\Setting;
use OCA\AnnouncementCenter\Tests\TestCase;
use OCP\Activity\ISetting;

class SettingTest extends TestCase {
	public function dataSettings() {
		return [
			[Setting::class],
		];
	}

	/**
	 * @dataProvider dataSettings
	 * @param string $settingClass
	 */
	public function testImplementsInterface($settingClass) {
		$setting = \OC::$server->query($settingClass);
		self::assertInstanceOf(ISetting::class, $setting);
	}

	/**
	 * @dataProvider dataSettings
	 * @param string $settingClass
	 */
	public function testGetIdentifier($settingClass) {
		/** @var ISetting $setting */
		$setting = \OC::$server->query($settingClass);
		self::assertIsString($setting->getIdentifier());
	}

	/**
	 * @dataProvider dataSettings
	 * @param string $settingClass
	 */
	public function testGetName($settingClass) {
		/** @var ISetting $setting */
		$setting = \OC::$server->query($settingClass);
		self::assertIsString($setting->getName());
	}

	/**
	 * @dataProvider dataSettings
	 * @param string $settingClass
	 */
	public function testGetPriority($settingClass) {
		/** @var ISetting $setting */
		$setting = \OC::$server->query($settingClass);
		$priority = $setting->getPriority();
		self::assertIsInt($setting->getPriority());
		self::assertGreaterThanOrEqual(0, $priority);
		self::assertLessThanOrEqual(100, $priority);
	}

	/**
	 * @dataProvider dataSettings
	 * @param string $settingClass
	 */
	public function testCanChangeStream($settingClass) {
		/** @var ISetting $setting */
		$setting = \OC::$server->query($settingClass);
		self::assertIsBool($setting->canChangeStream());
	}

	/**
	 * @dataProvider dataSettings
	 * @param string $settingClass
	 */
	public function testIsDefaultEnabledStream($settingClass) {
		/** @var ISetting $setting */
		$setting = \OC::$server->query($settingClass);
		self::assertIsBool($setting->isDefaultEnabledStream());
	}

	/**
	 * @dataProvider dataSettings
	 * @param string $settingClass
	 */
	public function testCanChangeMail($settingClass) {
		/** @var ISetting $setting */
		$setting = \OC::$server->query($settingClass);
		self::assertIsBool($setting->canChangeMail());
	}

	/**
	 * @dataProvider dataSettings
	 * @param string $settingClass
	 */
	public function testIsDefaultEnabledMail($settingClass) {
		/** @var ISetting $setting */
		$setting = \OC::$server->query($settingClass);
		self::assertIsBool($setting->isDefaultEnabledMail());
	}
}
