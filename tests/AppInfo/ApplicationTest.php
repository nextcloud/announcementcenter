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


use OCA\AnnouncementCenter\Activity\Extension;
use OCA\AnnouncementCenter\AppInfo\Application;
use OCA\AnnouncementCenter\BackgroundJob;
use OCA\AnnouncementCenter\Controller\PageController;
use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Migration\AnnouncementsGroupsLinks;
use OCA\AnnouncementCenter\Notification\Notifier;
use OCA\AnnouncementCenter\Settings\Admin;
use OCA\AnnouncementCenter\Tests\TestCase;
use OCP\Activity\IExtension;
use OCP\AppFramework\App;
use OCP\AppFramework\Controller;
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

	protected function setUp() {
		parent::setUp();
		$this->app = new Application();
		$this->container = $this->app->getContainer();
	}

	public function testContainerAppName() {
		$this->app = new Application();
		$this->assertEquals('announcementcenter', $this->container->getAppName());
	}

	public function dataContainerQuery() {
		return [
			[Extension::class, IExtension::class],
			[Application::class, App::class],
			['PageController', PageController::class],
			[PageController::class, Controller::class],
			[AnnouncementsGroupsLinks::class, IRepairStep::class],
			[Notifier::class, INotifier::class],
			[Admin::class, ISettings::class],
			[BackgroundJob::class, IJob::class],
			[Manager::class, Manager::class],
		];
	}

	/**
	 * @dataProvider dataContainerQuery
	 * @param string $service
	 * @param string $expected
	 */
	public function testContainerQuery($service, $expected) {
		$this->assertTrue($this->container->query($service) instanceof $expected);
	}
}
