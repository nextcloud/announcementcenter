<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
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
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @package OCA\AnnouncementCenter\Tests\Controller
 */
class PageControllerTest extends TestCase {
	/** @var IRequest|MockObject */
	protected $request;
	/** @var Manager|MockObject */
	protected $manager;
	/** @var ICommentsManager|MockObject */
	protected $commentsManager;
	/** @var IConfig|MockObject */
	protected $config;
	/** @var IInitialState|MockObject */
	protected $initialState;

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

	public function dataIndex(): array {
		return [
			[true, 'yes', true, 'no', false, 'no', false, 'no', false],
			[false, 'no', false, 'yes', true, 'yes', true, 'yes', true],
			[false, 'no', false, 'no', false, 'yes', true, 'yes', true],
		];
	}

	/**
	 * @dataProvider dataIndex
	 *
	 * @param bool $isAdmin
	 * @param string $createActivitiesConfig
	 * @param bool $createActivities
	 * @param string $createNotificationsConfig
	 * @param bool $createNotifications
	 * @param string $sendEmailsConfig
	 * @param bool $sendEmails
	 * @param string $allowCommentsConfig
	 * @param bool $allowComments
	 */
	public function testIndex(bool $isAdmin, string $createActivitiesConfig, bool $createActivities, string $createNotificationsConfig, bool $createNotifications, string $sendEmailsConfig, bool $sendEmails, string $allowCommentsConfig, bool $allowComments) {
		$this->manager->method('checkIsAdmin')
			->willReturn($isAdmin);
		$this->config->method('getAppValue')
			->willReturnMap([
				['announcementcenter', 'create_activities', 'yes', $createActivitiesConfig],
				['announcementcenter', 'create_notifications', 'yes', $createNotificationsConfig],
				['announcementcenter', 'send_emails', 'yes', $sendEmailsConfig],
				['announcementcenter', 'allow_comments', 'yes', $allowCommentsConfig],
			]);

		$this->initialState->method('provideInitialState')
			->withConsecutive(
				['isAdmin', $isAdmin],
				['createActivities', $createActivities],
				['createNotifications', $createNotifications],
				['sendEmails', $sendEmails],
				['allowComments', $allowComments]
			);

		$controller = $this->getController();
		$response = $controller->index();

		self::assertSame('user', $response->getRenderAs());
		self::assertSame('main', $response->getTemplateName());
	}
}
