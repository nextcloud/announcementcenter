<?php
/**
 * @copyright Copyright (c) 2016, Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
