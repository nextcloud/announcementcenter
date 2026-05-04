<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Tests\Settings;

use OCA\AnnouncementCenter\Settings\Admin;
use OCA\AnnouncementCenter\Tests\TestCase;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Services\IInitialState;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

class AdminTest extends TestCase {
	private Admin $admin;
	protected IInitialState&MockObject $initialState;
	protected IAppConfig&MockObject $appConfig;

	protected function setUp(): void {
		parent::setUp();
		$this->initialState = $this->createMock(IInitialState::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->admin = new Admin($this->appConfig, $this->initialState);
	}

	public static function dataGetForm(): array {
		return [
			[
				[
					['create_activities', true, true],
					['create_notifications', true, true],
					['send_emails', true, true],
					['allow_comments', true, true],
				],
				['admin'], true, true, true, true,
			],
			[
				[
					['create_activities', true, false],
					['create_notifications', true, true],
					['send_emails', true, true],
					['allow_comments', true, true],
				],
				['admin'], false, true, true, true,
			],
			[
				[
					['create_activities', true, true],
					['create_notifications', true, false],
					['send_emails', true, true],
					['allow_comments', true, true],
				],
				['admin'], true, false, true, true,
			],
			[
				[
					['create_activities', true, true],
					['create_notifications', true, true],
					['send_emails', true, false],
					['allow_comments', true, true],
				],
				['admin'], true, true, false, true,
			],
			[
				[
					['create_activities', true, true],
					['create_notifications', true, true],
					['send_emails', true, true],
					['allow_comments', true, false],
				],
				['admin'], true, true, true, false,
			],
			[
				[
					['create_activities', true, false],
					['create_notifications', true, false],
					['send_emails', true, false],
					['allow_comments', true, false],
				],
				['admin', 'group2'], false, false, false, false,
			],
		];
	}

	#[DataProvider('dataGetForm')]
	public function testGetForm(array $configMap, array $adminGroups, bool $createActivities, bool $createNotifications, bool $sendEmails, bool $allowComments): void {
		$this->appConfig->expects(self::exactly(4))
			->method('getAppValueBool')
			->willReturnMap($configMap);
		$this->appConfig->expects(self::once())
			->method('getAppValueArray')
			->willReturnMap([
				['admin_groups', ['admin'], $adminGroups],
			]);

		$this->initialState->method('provideInitialState')
			->willReturnCallback(function ($key, $data) use ($adminGroups, $createActivities, $createNotifications, $sendEmails, $allowComments): void {
				switch ($key) {
					case 'admin_groups':
						self::assertEquals($adminGroups, $data);
						break;
					case 'create_activities':
						self::assertEquals($createActivities, $data);
						break;
					case 'create_notifications':
						self::assertEquals($createNotifications, $data);
						break;
					case 'send_emails':
						self::assertEquals($sendEmails, $data);
						break;
					case 'allow_comments':
						self::assertEquals($allowComments, $data);
						break;
				}
			});

		$expected = new TemplateResponse('announcementcenter', 'admin', [], '');
		self::assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection(): void {
		self::assertSame('additional', $this->admin->getSection());
	}

	public function testGetPriority(): void {
		self::assertSame(55, $this->admin->getPriority());
	}
}
