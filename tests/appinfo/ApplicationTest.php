<?php

/**
 * ownCloud - AnnouncementCenter App
 *
 * @author Joas Schilling
 * @copyright 2014 Joas Schilling nickvergessen@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\AnnouncementCenter\Tests;

use OCA\AnnouncementCenter\AppInfo\Application;

class ApplicationTest extends TestCase {
	/** @var \OCA\Activity\AppInfo\Application */
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
		return array(
			array('PageController', 'OCA\AnnouncementCenter\Controller\PageController'),
		);
	}

	/**
	 * @dataProvider dataContainerQuery
	 * @param string $service
	 * @param string $expected
	 */
	public function testContainerQuery($service, $expected) {
		$this->assertTrue($this->container->query($service) instanceof $expected);
	}

	public function dataGetCurrentUser() {
		$user = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->any())
			->method('getUID')
			->willReturn('uid');

		return [
			[$user, 'uid'],
			[null, ''],
		];
	}

	/**
	 * @dataProvider dataGetCurrentUser
	 * @param mixed $user
	 * @param string $expected
	 */
	public function testGetCurrentUser($user, $expected) {
		$session = $this->getMockBuilder('OCP\IUserSession')
			->disableOriginalConstructor()
			->getMock();
		$session->expects($this->any())
			->method('getUser')
			->willReturn($user);

		$this->assertSame($expected, $this->invokePrivate($this->app, 'getCurrentUser', [$session]));
	}
}
