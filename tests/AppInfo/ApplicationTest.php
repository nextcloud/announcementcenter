<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Tests\AppInfo;

use OCA\AnnouncementCenter\Activity\Provider;
use OCA\AnnouncementCenter\Activity\Setting;
use OCA\AnnouncementCenter\AppInfo\Application;
use OCA\AnnouncementCenter\Controller\PageController;
use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Migration\AnnouncementsGroupsLinks;
use OCA\AnnouncementCenter\Model\Announcement;
use OCA\AnnouncementCenter\Model\AnnouncementMapper;
use OCA\AnnouncementCenter\Model\Group;
use OCA\AnnouncementCenter\Model\GroupMapper;
use OCA\AnnouncementCenter\Notification\Notifier;
use OCA\AnnouncementCenter\NotificationQueueJob;
use OCA\AnnouncementCenter\Settings\Admin;
use OCA\AnnouncementCenter\Tests\TestCase;
use OCP\Activity\IProvider;
use OCP\Activity\ISetting;
use OCP\AppFramework\App;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\BackgroundJob\IJob;
use OCP\Migration\IRepairStep;
use OCP\Notification\INotifier;
use OCP\Settings\ISettings;

/**
 * Class ApplicationTest
 *
 * @package OCA\AnnouncementCenter\Tests
 * @group DB
 */
class ApplicationTest extends TestCase {
	/** @var \OCA\AnnouncementCenter\AppInfo\Application */
	protected $app;

	/** @var \OCP\AppFramework\IAppContainer */
	protected $container;

	protected function setUp(): void {
		parent::setUp();
		$this->app = new Application();
		$this->container = $this->app->getContainer();
	}

	public function testContainerAppName() {
		$this->app = new Application();
		self::assertEquals('announcementcenter', $this->container->getAppName());
	}

	public function dataContainerQuery(): array {
		return [
			[Setting::class, ISetting::class],
			[Provider::class, IProvider::class],
			[Application::class, App::class],
			[PageController::class, Controller::class],
			[AnnouncementsGroupsLinks::class, IRepairStep::class],
			[Notifier::class, INotifier::class],
			[Admin::class, ISettings::class],
			[NotificationQueueJob::class, IJob::class],
			[Manager::class, Manager::class],
			[Announcement::class, Entity::class],
			[AnnouncementMapper::class, QBMapper::class],
			[Group::class, Entity::class],
			[GroupMapper::class, QBMapper::class],
		];
	}

	/**
	 * @dataProvider dataContainerQuery
	 * @param string $service
	 * @param string $expected
	 */
	public function testContainerQuery(string $service, string $expected) {
		self::assertInstanceOf($expected, $this->container->query($service));
	}
}
