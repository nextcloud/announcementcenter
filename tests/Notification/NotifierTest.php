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

namespace OCA\AnnouncementCenter\Tests\Notification;

use OCA\AnnouncementCenter\Model\Announcement;
use OCA\AnnouncementCenter\Model\AnnouncementDoesNotExistException;
use OCA\AnnouncementCenter\Notification\Notifier;
use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Tests\TestCase;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Notification\AlreadyProcessedException;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use PHPUnit\Framework\MockObject\MockObject;

class NotifierTest extends TestCase {
	/** @var Notifier */
	protected $notifier;

	/** @var Manager|MockObject */
	protected $manager;
	/** @var IManager|MockObject */
	protected $notificationManager;
	/** @var IUserManager|MockObject */
	protected $userManager;
	/** @var IFactory|MockObject */
	protected $factory;
	/** @var IURLGenerator|MockObject */
	protected $urlGenerator;
	/** @var IL10N|MockObject */
	protected $l;

	protected function setUp(): void {
		parent::setUp();

		$this->manager = $this->createMock(Manager::class);
		$this->notificationManager = $this->createMock(IManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->l = $this->createMock(IL10N::class);
		$this->l
			->method('t')
			->willReturnCallback(function ($string, $args) {
				return vsprintf($string, $args);
			});
		$this->factory = $this->createMock(IFactory::class);
		$this->factory
			->method('get')
			->willReturn($this->l);

		$this->notifier = new Notifier(
			$this->manager,
			$this->factory,
			$this->notificationManager,
			$this->userManager,
			$this->urlGenerator
		);
	}

	public function testPrepareWrongApp(): void {
		/** @var \OCP\Notification\INotification|MockObject $notification */
		$notification = $this->createMock(INotification::class);

		$notification->expects(self::once())
			->method('getApp')
			->willReturn('notifications');
		$notification->expects(self::never())
			->method('getSubject');

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Unknown app');
		$this->notifier->prepare($notification, 'en');
	}

	public function testPrepareWrongSubject(): void {
		/** @var \OCP\Notification\INotification|MockObject $notification */
		$notification = $this->createMock(INotification::class);

		$notification->expects(self::once())
			->method('getApp')
			->willReturn('announcementcenter');
		$notification->expects(self::once())
			->method('getSubject')
			->willReturn('wrong subject');

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Unknown subject');
		$this->notifier->prepare($notification, 'en');
	}

	public function testPrepareDoesNotExist(): void {
		/** @var INotification|MockObject $notification */
		$notification = $this->createMock(INotification::class);

		$notification->expects(self::once())
			->method('getApp')
			->willReturn('announcementcenter');
		$notification->expects(self::once())
			->method('getSubject')
			->willReturn('announced');
		$notification->expects(self::once())
			->method('getObjectId')
			->willReturn('42');

		$this->manager->expects(self::once())
			->method('getAnnouncement')
			->with(42, false)
			->willThrowException(new AnnouncementDoesNotExistException());

		$this->expectException(AlreadyProcessedException::class);
		$this->notifier->prepare($notification, 'en');
	}

	public function dataPrepare(): array {
		$message = "message\nmessage message message message message message message message message message message messagemessagemessagemessagemessagemessagemessage";
		return [
			['author', 'subject', 'message', 'message', '42', null, 'author announced “subject”', 'message'],
			['author1', 'subject', 'message', 'message', '42', 'Author', 'Author announced “subject”', 'message'],
			['author2', "subject\nsubject", $message, $message, '21', null, 'author2 announced “subject subject”', $message],
		];
	}

	/**
	 * @dataProvider dataPrepare
	 *
	 * @param string $author
	 * @param string $subject
	 * @param string $message
	 * @param string $plainMessage
	 * @param int $objectId
	 * @param ?string $userDisplayName
	 * @param string $expectedSubject
	 * @param string $expectedMessage
	 */
	public function testPrepare($author, $subject, $message, $plainMessage, $objectId, $userDisplayName, $expectedSubject, $expectedMessage): void {
		/** @var INotification|MockObject $notification */
		$notification = $this->createMock(INotification::class);

		$notification->expects(self::once())
			->method('getApp')
			->willReturn('announcementcenter');
		$notification->expects(self::once())
			->method('getSubject')
			->willReturn('announced');
		$notification->expects(self::once())
			->method('getSubjectParameters')
			->willReturn([$author]);
		$notification->expects(self::exactly(3))
			->method('getObjectId')
			->willReturn($objectId);

		$announcement = Announcement::fromParams([
			'subject' => $subject,
			'message' => $message,
			'plainMessage' => $plainMessage,
		]);

		$this->manager->expects(self::once())
			->method('getAnnouncement')
			->with($objectId, false)
			->willReturn($announcement);
		$this->userManager->expects(self::once())
			->method('getDisplayName')
			->with($author)
			->willReturn($userDisplayName);

		$notification->expects(self::once())
			->method('setParsedMessage')
			->with($expectedMessage)
			->willReturnSelf();
		$notification->expects(self::once())
			->method('setRichSubject')
			->with('{user} announced {announcement}', self::anything())
			->willReturnSelf();

		$notification->expects(self::once())
			->method('getRichSubjectParameters')
			->willReturn([
				'user' => [
					'type' => 'user',
					'id' => 'author',
					'name' => $userDisplayName ?? $author,
				],
				'announcement' => [
					'type' => 'announcement',
					'id' => $objectId,
					'name' => $announcement->getParsedSubject(),
				],
			]);

		$notification->expects(self::once())
			->method('setParsedSubject')
			->with($expectedSubject)
			->willReturnSelf();
		$notification->expects(self::once())
			->method('setLink')
			->willReturnSelf();
		$notification->expects(self::once())
			->method('setIcon')
			->willReturnSelf();

		$return = $this->notifier->prepare($notification, 'en');

		self::assertEquals($notification, $return);
	}
}
