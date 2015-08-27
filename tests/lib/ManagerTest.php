<?php

/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\AnnouncementCenter\Tests\Lib;

use OCA\AnnouncementCenter\Manager;

class ManagerTest extends \Test\TestCase {
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
		$subject = 'subject';
		$message = 'message';
		$author = 'author';
		$time = time() - 10;

		$id = $this->manager->announce($subject, $message, $author, $time);
		$this->assertInternalType('int', $id);
		$this->assertGreaterThan(0, $id);

		$this->assertEquals([
			'subject' => $subject,
			'message' => $message,
			'author' => $author,
			'time' => $time,
		], $this->manager->getAnnouncement($id));

		$this->assertEquals([[
			'subject' => $subject,
			'message' => $message,
			'author' => $author,
			'time' => $time,
		]], $this->manager->getAnnouncements(1));

		$this->manager->delete($id);

		try {
			$this->manager->getAnnouncement($id);
			$this->fail('Failed to delete the announcement');
		} catch (\InvalidArgumentException $e) {
			$this->assertInstanceOf('InvalidArgumentException', $e);
		}
	}
}
