<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Tests\Activity;

use OCA\AnnouncementCenter\Activity\Setting;
use OCA\AnnouncementCenter\Tests\TestCase;
use OCP\Activity\ISetting;
use PHPUnit\Framework\Attributes\DataProvider;

class SettingTest extends TestCase {
	public static function dataSettings(): array {
		return [
			[Setting::class],
		];
	}

	#[DataProvider('dataSettings')]
	public function testImplementsInterface(string $settingClass): void {
		$setting = \OCP\Server::get($settingClass);
		self::assertInstanceOf(ISetting::class, $setting);
	}

	#[DataProvider('dataSettings')]
	public function testGetIdentifier(string $settingClass): void {
		/** @var ISetting $setting */
		$setting = \OCP\Server::get($settingClass);
		self::assertIsString($setting->getIdentifier());
	}

	#[DataProvider('dataSettings')]
	public function testGetName(string $settingClass): void {
		/** @var ISetting $setting */
		$setting = \OCP\Server::get($settingClass);
		self::assertIsString($setting->getName());
	}

	#[DataProvider('dataSettings')]
	public function testGetPriority(string $settingClass): void {
		/** @var ISetting $setting */
		$setting = \OCP\Server::get($settingClass);
		$priority = $setting->getPriority();
		self::assertIsInt($setting->getPriority());
		self::assertGreaterThanOrEqual(0, $priority);
		self::assertLessThanOrEqual(100, $priority);
	}

	#[DataProvider('dataSettings')]
	public function testCanChangeStream(string $settingClass): void {
		/** @var ISetting $setting */
		$setting = \OCP\Server::get($settingClass);
		self::assertIsBool($setting->canChangeStream());
	}

	#[DataProvider('dataSettings')]
	public function testIsDefaultEnabledStream(string $settingClass): void {
		/** @var ISetting $setting */
		$setting = \OCP\Server::get($settingClass);
		self::assertIsBool($setting->isDefaultEnabledStream());
	}

	#[DataProvider('dataSettings')]
	public function testCanChangeMail(string $settingClass): void {
		/** @var ISetting $setting */
		$setting = \OCP\Server::get($settingClass);
		self::assertIsBool($setting->canChangeMail());
	}

	#[DataProvider('dataSettings')]
	public function testIsDefaultEnabledMail(string $settingClass): void {
		/** @var ISetting $setting */
		$setting = \OCP\Server::get($settingClass);
		self::assertIsBool($setting->isDefaultEnabledMail());
	}
}
