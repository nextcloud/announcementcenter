<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2015-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Tests;

use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Model\Announcement;
use OCA\AnnouncementCenter\Model\AnnouncementDoesNotExistException;
use OCA\AnnouncementCenter\NotificationQueueJob;
use OCP\Activity\IEvent;
use OCP\Activity\IManager as IActivityManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * @group DB
 */
class BackgroundJobTest extends TestCase {
	protected IConfig&MockObject $config;
	protected ITimeFactory&MockObject $time;
	protected IUserManager&MockObject $userManager;
	protected IGroupManager&MockObject $groupManager;
	protected IActivityManager&MockObject $activityManager;
	protected INotificationManager&MockObject $notificationManager;
	protected IMailer&MockObject $mailer;
	protected LoggerInterface&MockObject $logger;
	protected Manager&MockObject $manager;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->time = $this->createMock(ITimeFactory::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->activityManager = $this->createMock(IActivityManager::class);
		$this->notificationManager = $this->createMock(INotificationManager::class);
		$this->mailer = $this->createMock(IMailer::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->manager = $this->createMock(Manager::class);
	}

	protected function getJob(array $methods = []) {
		if (empty($methods)) {
			return new NotificationQueueJob(
				$this->config,
				$this->time,
				$this->userManager,
				$this->groupManager,
				$this->activityManager,
				$this->notificationManager,
				$this->mailer,
				$this->logger,
				$this->manager
			);
		}

		return $this->getMockBuilder(NotificationQueueJob::class)
			->setConstructorArgs([
				$this->config,
				$this->time,
				$this->userManager,
				$this->groupManager,
				$this->activityManager,
				$this->notificationManager,
				$this->mailer,
				$this->logger,
				$this->manager,
			])
			->onlyMethods($methods)
			->getMock();
	}

	public function testRunThrows(): void {
		$job = $this->getJob(['createPublicity']);

		$this->manager->expects(self::once())
			->method('getAnnouncement')
			->with(23, true)
			->willThrowException(new AnnouncementDoesNotExistException());

		$job->expects(self::never())
			->method('createPublicity');

		self::invokePrivate($job, 'run', [[
			'id' => 23,
			'activities' => true,
			'notifications' => true,
		]]);
	}

	public static function dataRun(): array {
		return [
			[23, true, false],
			[42, false, true],
			[72, true, true],
		];
	}

	#[DataProvider('dataRun')]
	public function testRun(int $id, bool $activities, bool $notifications): void {
		$job = $this->getJob(['createPublicity']);

		$this->config->method('getAppValue')
			->with('guests', 'whitelist', '')
			->willReturn('');

		$announcement = $this->createMock(Announcement::class);

		$this->manager->expects(self::once())
			->method('getAnnouncement')
			->with($id, true)
			->willReturn($announcement);

		$job->expects(self::once())
			->method('createPublicity')
			->with($announcement, [
				'id' => $id,
				'activities' => $activities,
				'notifications' => $notifications,
			]);

		self::invokePrivate($job, 'run', [[
			'id' => $id,
			'activities' => $activities,
			'notifications' => $notifications,
		]]);
	}

	/**
	 * @return IUser&MockObject
	 */
	protected function getUserMock(string $uid, string $displayName, bool $loggedIn = true) {
		$user = $this->createMock(IUser::class);
		$user
			->method('getUID')
			->willReturn($uid);
		$user
			->method('getDisplayName')
			->willReturn($displayName);
		$user
			->method('getLastLogin')
			->willReturn($loggedIn ? 1234 : 0);
		$user
			->method('getEMailAddress')
			->willReturn($uid . '@example.org');
		$user
			->method('isEnabled')
			->willReturn(strpos($uid, 'disabled-') !== 0);
		return $user;
	}

	/**
	 * @param IUser[] $users
	 * @return MockObject
	 */
	protected function getGroupMock(array $users) {
		$group = $this->createMock(IGroup::class);
		$group
			->method('getUsers')
			->willReturn($users);
		return $group;
	}

	public static function dataCreatePublicity(): array {
		return [
			[['everyone'], true, [
				'activities' => true,
				'notifications' => false,
				'emails' => true,
			]],
			[['gid1', 'gid2'], false, [
				'activities' => false,
				'notifications' => true,
				'emails' => false,
			]],
		];
	}

	#[DataProvider('dataCreatePublicity')]
	public function testCreatePublicity(array $groups, bool $everyone, array $publicity): void {
		$event = $this->createMock(IEvent::class);
		$event->expects(self::once())
			->method('setApp')
			->with('announcementcenter')
			->willReturnSelf();
		$event->expects(self::once())
			->method('setType')
			->with('announcementcenter')
			->willReturnSelf();
		$event->expects(self::once())
			->method('setAuthor')
			->with('author')
			->willReturnSelf();
		$event->expects(self::once())
			->method('setTimestamp')
			->with(1337)
			->willReturnSelf();
		$event->expects(self::once())
			->method('setSubject')
			->with('announcementsubject', ['author' => 'author', 'announcement' => 10])
			->willReturnSelf();
		$event->expects(self::once())
			->method('setMessage')
			->with('announcementmessage', [])
			->willReturnSelf();
		$event->expects(self::once())
			->method('setObject')
			->with('announcement', 10)
			->willReturnSelf();

		$notification = $this->createMock(INotification::class);
		$notification->expects(self::once())
			->method('setApp')
			->with('announcementcenter')
			->willReturnSelf();
		$dateTime = new \DateTime();
		$dateTime->setTimestamp(1337);
		$notification->expects(self::once())
			->method('setDateTime')
			->with($dateTime)
			->willReturnSelf();
		$notification->expects(self::once())
			->method('setSubject')
			->with('announced', ['author'])
			->willReturnSelf();
		$notification->expects(self::once())
			->method('setObject')
			->with('announcement', 10)
			->willReturnSelf();

		$template = $this->createMock(IEMailTemplate::class);
		$template->expects(self::once())
			->method('setSubject')
			->with('subject')
			->willReturnSelf();
		$template->expects(self::once())
			->method('addHeader')
			->willReturnSelf();
		$template->expects(self::once())
			->method('addHeading')
			->with('subject')
			->willReturnSelf();
		$template
			->method('addBodyText')
			->with('message')
			->willReturnSelf();
		$template->expects(self::once())
			->method('addFooter')
			->willReturnSelf();

		$email = $this->createMock(IMessage::class);
		$email->expects(self::once())
			->method('useTemplate')
			->with($template)
			->willReturnSelf();

		$job = $this->getJob([
			'createPublicityEveryone',
			'createPublicityGroups',
		]);

		if ($everyone) {
			$job->expects(self::once())
				->method('createPublicityEveryone')
				->with('author', $event, $notification, $email, $publicity);
		} else {
			$job->expects(self::once())
				->method('createPublicityGroups')
				->with('author', $event, $notification, $email, $groups, $publicity);
		}

		$this->activityManager->expects(self::once())
			->method('generateEvent')
			->willReturn($event);
		$this->notificationManager->expects(self::once())
			->method('createNotification')
			->willReturn($notification);
		$this->mailer->expects(self::once())
			->method('createEMailTemplate')
			->willReturn($template);
		$this->mailer->expects(self::once())
			->method('createMessage')
			->willReturn($email);
		$this->time->method('getDateTime')
			->willReturn(new \DateTime());

		$announcement = Announcement::fromParams([
			'id' => 10,
			'user' => 'author',
			'time' => 1337,
			'subject' => 'subject',
			'message' => 'message',
			'plainMessage' => 'message',
		]);

		$this->manager->expects(self::once())
			->method('getGroups')
			->willReturn($groups);

		self::invokePrivate($job, 'createPublicity', [$announcement, $publicity, $email]);
	}

	public static function dataCreatePublicityEveryoneAndGroup(): array {
		return [
			[[
				'activities' => true,
				'notifications' => false,
				'emails' => true,
			], true, false, true],
			[[
				'activities' => false,
				'notifications' => true,
				'emails' => false,
			], false, true, false],
		];
	}

	#[DataProvider('dataCreatePublicityEveryoneAndGroup')]
	public function testCreatePublicityEveryone(array $publicity, bool $activities, bool $notifications, bool $emails): void {
		$event = $this->createMock(IEvent::class);
		$event->expects($activities ? self::exactly(5) : self::never())
			->method('setAffectedUser')
			->willReturnSelf();

		$notification = $this->createMock(INotification::class);
		$notification->expects($notifications ? self::exactly(4) : self::never())
			->method('setUser')
			->willReturnSelf();

		$email = $this->createMock(IMessage::class);
		$email->expects($emails ? self::exactly(3) : self::never())
			->method('setTo')
			->willReturnSelf();

		$this->mailer->expects(self::any())
			->method('validateMailAddress')
			->willReturn(true);

		$job = $this->getJob();
		$this->userManager->expects(self::once())
			->method('callForSeenUsers')
			->with(self::anything())
			->willReturnCallback(function ($callback) {
				$users = [
					$this->getUserMock('author', 'User One'),
					$this->getUserMock('u2', 'User Two'),
					$this->getUserMock('disabled-u3', 'User Three (disabled)'),
					$this->getUserMock('u4', 'User Four'),
					$this->getUserMock('u5', 'User Five'),
				];
				foreach ($users as $user) {
					$callback($user);
				}
			})
		;

		$this->activityManager->expects($activities ? self::exactly(5) : self::never())
			->method('publish');
		$this->notificationManager->expects($notifications ? self::exactly(4) : self::never())
			->method('notify');
		$this->mailer->expects($emails ? self::exactly(3) : self::never())
			->method('send');

		self::invokePrivate($job, 'createPublicityEveryone', ['author', $event, $notification, $email, $publicity]);
	}

	#[DataProvider('dataCreatePublicityEveryoneAndGroup')]
	public function testCreatePublicityGroups(array $publicity, bool $activities, bool $notifications, bool $emails): void {
		$event = $this->createMock(IEvent::class);
		$event->expects($activities ? self::exactly(4) : self::never())
			->method('setAffectedUser')
			->willReturnSelf();

		$notification = $this->createMock(INotification::class);
		$notification->expects($notifications ? self::exactly(3) : self::never())
			->method('setUser')
			->willReturnSelf();

		$email = $this->createMock(IMessage::class);
		$email->expects($emails ? self::exactly(3) : self::never())
			->method('setTo')
			->willReturnSelf();

		$job = $this->getJob();
		$this->groupManager->expects(self::exactly(4))
			->method('get')
			->willReturnMap([
				['gid0', null],
				['gid1', $this->getGroupMock([])],
				['gid2', $this->getGroupMock([
					$this->getUserMock('author', 'User One'),
					$this->getUserMock('u2', 'User Two'),
					$this->getUserMock('u3', 'User Three'),
				])],
				['gid3', $this->getGroupMock([
					$this->getUserMock('u3', 'User Three'),
					$this->getUserMock('u4', 'User Four', false),
					$this->getUserMock('u5', 'User Five'),
				])],
			]);

		$this->activityManager->expects($activities ? self::exactly(4) : self::never())
			->method('publish');
		$this->notificationManager->expects($notifications ? self::exactly(3) : self::never())
			->method('notify');

		self::invokePrivate($job, 'createPublicityGroups', ['author', $event, $notification, $email, ['gid0', 'gid1', 'gid2', 'gid3'], $publicity]);
	}
}
