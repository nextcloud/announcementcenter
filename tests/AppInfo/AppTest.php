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

namespace OCA\AnnouncementCenter\Tests;

/**
 * Class AppTest
 *
 * @package OCA\AnnouncementCenter\Tests
 * @group DB
 */
use OCA\AnnouncementCenter\Notification\Notifier;
use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\Notification\IManager;

class AppTest extends TestCase {
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	protected $language;
	/** @var IFactory|\PHPUnit_Framework_MockObject_MockObject */
	protected $languageFactory;
	/** @var IManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $notificationManager;

	protected function setUp(): void {
		parent::setUp();

		$this->languageFactory = $this->getMockBuilder(IFactory::class)
			->disableOriginalConstructor()
			->getMock();
		$this->notificationManager = $this->getMockBuilder(IManager::class)
			->disableOriginalConstructor()
			->getMock();
		$this->language = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()
			->getMock();
		$this->language->expects($this->any())
			->method('t')
			->willReturnCallback(function ($string, $args) {
				return vsprintf($string, $args);
			});

		$this->overwriteService(IManager::class, $this->notificationManager);
		$this->overwriteService('L10NFactory', $this->languageFactory);
	}

	protected function tearDown(): void {
		$this->restoreService(IManager::class);
		$this->restoreService('L10NFactory');

		parent::tearDown();
	}

	public function testAppNotification() {
		$this->notificationManager->expects($this->once())
			->method('registerNotifierService')
			->with(Notifier::class);

		include(__DIR__ . '/../../appinfo/app.php');
	}
}
