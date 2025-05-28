<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Tests\Controller;

use OCA\AnnouncementCenter\Controller\PageController;
use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Tests\TestCase;
use OCP\AppFramework\Services\IInitialState;
use OCP\Comments\ICommentsManager;
use OCP\IConfig;
use OCP\IRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @package OCA\AnnouncementCenter\Tests\Controller
 */
class PageControllerTest extends TestCase {
	protected IRequest&MockObject $request;
	protected Manager&MockObject $manager;
	protected ICommentsManager&MockObject $commentsManager;
	protected IConfig&MockObject $config;
	protected IInitialState&MockObject $initialState;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->manager = $this->createMock(Manager::class);
		$this->commentsManager = $this->createMock(ICommentsManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->initialState = $this->createMock(IInitialState::class);
	}

	protected function getController(): PageController {
		return new PageController(
			'announcementcenter',
			$this->request,
			$this->manager,
			$this->commentsManager,
			$this->config,
			$this->initialState
		);
	}

	public static function dataIndex(): array {
		return [
			[true, 'yes', true, 'no', false, 'no', false, 'no', false],
			[false, 'no', false, 'yes', true, 'yes', true, 'yes', true],
			[false, 'no', false, 'no', false, 'yes', true, 'yes', true],
		];
	}

	#[DataProvider('dataIndex')]
	public function testIndex(bool $isAdmin, string $createActivitiesConfig, bool $createActivities, string $createNotificationsConfig, bool $createNotifications, string $sendEmailsConfig, bool $sendEmails, string $allowCommentsConfig, bool $allowComments): void {
		$this->manager->method('checkIsAdmin')
			->willReturn($isAdmin);
		$this->config->method('getAppValue')
			->willReturnMap([
				['announcementcenter', 'create_activities', 'yes', $createActivitiesConfig],
				['announcementcenter', 'create_notifications', 'yes', $createNotificationsConfig],
				['announcementcenter', 'send_emails', 'yes', $sendEmailsConfig],
				['announcementcenter', 'allow_comments', 'yes', $allowCommentsConfig],
			]);

		$calls = [
			['isAdmin', $isAdmin],
			['createActivities', $createActivities],
			['createNotifications', $createNotifications],
			['sendEmails', $sendEmails],
			['allowComments', $allowComments],
			['activeId', 0],
		];
		$this->initialState->method('provideInitialState')
			->willReturnCallback(function () use (&$calls) {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
			});

		$controller = $this->getController();
		$response = $controller->index();

		self::assertSame('user', $response->getRenderAs());
		self::assertSame('main', $response->getTemplateName());
	}
}
