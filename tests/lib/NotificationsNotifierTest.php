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

use OCA\AnnouncementCenter\NotificationsNotifier;
use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Tests\TestCase;
use OCP\IL10N;
use OCP\L10N\IFactory;

class NotificationsNotifierTest extends TestCase {
	/** @var NotificationsNotifier */
	protected $notifier;

	/** @var Manager|\PHPUnit_Framework_MockObject_MockObject */
	protected $manager;
	/** @var IFactory|\PHPUnit_Framework_MockObject_MockObject */
	protected $factory;
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	protected $l;

	protected function setUp() {
		parent::setUp();

		$this->manager = $this->getMockBuilder('OCA\AnnouncementCenter\Manager')
			->disableOriginalConstructor()
			->getMock();
		$this->l = $this->getMockBuilder('OCP\IL10N')
			->disableOriginalConstructor()
			->getMock();
		$this->l->expects($this->any())
			->method('t')
			->willReturnCallback(function($string, $args) {
				return vsprintf($string, $args);
			});
		$this->factory = $this->getMockBuilder('OCP\L10N\IFactory')
			->disableOriginalConstructor()
			->getMock();
		$this->factory->expects($this->any())
			->method('get')
			->willReturn($this->l);

		$this->notifier = new NotificationsNotifier(
			$this->manager,
			$this->factory
		);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testPrepareWrongApp() {
		/** @var \OC\Notification\INotification|\PHPUnit_Framework_MockObject_MockObject $notification */
		$notification = $this->getMockBuilder('OC\Notification\INotification')
			->disableOriginalConstructor()
			->getMock();

		$notification->expects($this->once())
			->method('getApp')
			->willReturn('notifications');
		$notification->expects($this->never())
			->method('getSubject');

		$this->notifier->prepare($notification, 'en');
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testPrepareWrongSubject() {
		/** @var \OC\Notification\INotification|\PHPUnit_Framework_MockObject_MockObject $notification */
		$notification = $this->getMockBuilder('OC\Notification\INotification')
			->disableOriginalConstructor()
			->getMock();

		$notification->expects($this->once())
			->method('getApp')
			->willReturn('announcementcenter');
		$notification->expects($this->once())
			->method('getSubject')
			->willReturn('wrong subject');

		$this->notifier->prepare($notification, 'en');
	}

	public function dataPrepare() {
		$subject = "subject\nsubject subject subject subject subject subject subject subject subject subject subject subject subject subject subject subject subject subject";
		$subjectTrim = 'subject subject subject subject subject subject subject subject subject subject subject subject subject…';
		$message = "message\nmessage message message message message message message message message message message messagemessagemessagemessagemessagemessagemessage";
		$messageTrim = 'message message message message message message message message message message message message messagemessagemessagemes…';
		return [
			['author', 'subject', 'message', 42, 'author announced “subject”', 'message'],
			['author2', $subject, $message, 21, 'author2 announced “' . $subjectTrim . '”', $messageTrim],
		];
	}

	/**
	 * @dataProvider dataPrepare
	 *
	 * @param string $author
	 * @param string $subject
	 * @param string $message
	 * @param int $objectId
	 * @param string $expectedSubject
	 * @param string $expectedMessage
	 */
	public function testPrepare($author, $subject, $message, $objectId, $expectedSubject, $expectedMessage) {
		/** @var \OC\Notification\INotification|\PHPUnit_Framework_MockObject_MockObject $notification */
		$notification = $this->getMockBuilder('OC\Notification\INotification')
			->disableOriginalConstructor()
			->getMock();

		$notification->expects($this->once())
			->method('getApp')
			->willReturn('announcementcenter');
		$notification->expects($this->once())
			->method('getSubject')
			->willReturn('announced');
		$notification->expects($this->once())
			->method('getSubjectParameters')
			->willReturn([$author]);
		$notification->expects($this->once())
			->method('getObjectId')
			->willReturn($objectId);

		$this->manager->expects($this->once())
			->method('getAnnouncement')
			->with($objectId, false)
			->willReturn([
				'subject' => $subject,
				'message' => $message,
			]);

		$notification->expects($this->once())
			->method('setParsedMessage')
			->with($expectedMessage)
			->willReturnSelf();
		$notification->expects($this->once())
			->method('setParsedSubject')
			->with($expectedSubject)
			->willReturnSelf();

		$return = $this->notifier->prepare($notification, 'en');

		$this->assertEquals($notification, $return);
	}

	public function te1stAnnouncement() {
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
