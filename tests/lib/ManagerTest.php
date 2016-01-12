<?php
/**
 * ownCloud - AnnouncementCenter App
 *
 * @author Joas Schilling
 * @copyright 2015 Joas Schilling nickvergessen@owncloud.com
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

namespace OCA\AnnouncementCenter\Tests\Lib;

use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Tests\TestCase;

/**
 * Class ManagerTest
 *
 * @package OCA\AnnouncementCenter\Tests\Lib
 * @group DB
 */
class ManagerTest extends TestCase {
	/** @var Manager */
	protected $manager;

	protected function setUp() {
		parent::setUp();
		$this->manager = new Manager(
			\OC::$server->getDatabaseConnection()
		);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedMessage Invalid ID
	 */
	public function testGetAnnouncementNotExist() {
		$this->manager->getAnnouncement(0);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedMessage Invalid subject
	 * @expectedCode 2
	 */
	public function testAnnounceNoSubject() {
		$this->manager->announce('', '', '', 0);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedMessage Invalid subject
	 * @expectedCode 1
	 */
	public function testAnnounceSubjectTooLong() {
		$this->manager->announce(str_repeat('a', 513), '', '', 0);
	}

	public function testAnnouncement() {
		$subject = 'subject' . "\n<html>";
		$message = 'message' . "\n<html>";
		$author = 'author';
		$time = time() - 10;

		$announcement = $this->manager->announce($subject, $message, $author, $time);
		$this->assertInternalType('int', $announcement['id']);
		$this->assertGreaterThan(0, $announcement['id']);
		$this->assertSame('subject &lt;html&gt;', $announcement['subject']);
		$this->assertSame('message<br />&lt;html&gt;', $announcement['message']);
		$this->assertSame('author', $announcement['author']);
		$this->assertSame($time, $announcement['time']);

		$this->assertEquals($announcement, $this->manager->getAnnouncement($announcement['id']));

		$this->assertEquals($announcement, $this->manager->getAnnouncement($announcement['id']));

		$this->assertEquals([$announcement], $this->manager->getAnnouncements(1));

		$this->manager->delete($announcement['id']);

		try {
			$this->manager->getAnnouncement($announcement['id']);
			$this->fail('Failed to delete the announcement');
		} catch (\InvalidArgumentException $e) {
			$this->assertInstanceOf('InvalidArgumentException', $e);
		}
	}
}
