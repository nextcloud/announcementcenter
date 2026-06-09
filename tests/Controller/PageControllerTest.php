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
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Services\IInitialState;
use OCP\Comments\ICommentsManager;
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
	protected IInitialState&MockObject $initialState;
	protected IAppConfig&MockObject $appConfig;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->manager = $this->createMock(Manager::class);
		$this->commentsManager = $this->createMock(ICommentsManager::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
	}

	protected function getController(): PageController {
		return new PageController(
			'announcementcenter',
			$this->request,
			$this->manager,
			$this->commentsManager,
			$this->initialState,
			$this->appConfig,
		);
	}

	public static function dataIndex(): array {
		return [
			[true, true, true, false, false, false, false, false, false],
			[false, false, false, true, true, true, true, true, true],
			[false, false, false, false, false, true, true, true, true],
		];
	}

	#[DataProvider('dataIndex')]
	public function testIndex(bool $isAdmin, bool $createActivitiesConfig, bool $createActivities, bool $createNotificationsConfig, bool $createNotifications, bool $sendEmailsConfig, bool $sendEmails, bool $allowCommentsConfig, bool $allowComments): void {
		$this->manager->method('checkIsAdmin')
			->willReturn($isAdmin);
		$this->appConfig->method('getAppValueBool')
			->willReturnMap([
				['create_activities', true, $createActivitiesConfig],
				['create_notifications', true, $createNotificationsConfig],
				['send_emails', true, $sendEmailsConfig],
				['allow_comments', true, $allowCommentsConfig],
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
			->willReturnCallback(function () use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
			});

		$controller = $this->getController();
		$response = $controller->index();

		self::assertSame('user', $response->getRenderAs());
		self::assertSame('main', $response->getTemplateName());
	}
}
