<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\AnnouncementCenter\Tests\Command;

use OCA\AnnouncementCenter\Command\Announce;
use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Model\NotificationType;
use OCA\AnnouncementCenter\Tests\TestCase;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AnnounceCommandTest extends TestCase {
	protected IUserManager|MockObject $userManager;
	protected ITimeFactory|MockObject $time;
	protected Manager|MockObject $manager;
	protected NotificationType $notificationType;
	protected LoggerInterface|MockObject $logger;
	protected Command $announceCommand;
	protected InputInterface|MockObject $input;
	protected OutputInterface|MockObject $output;

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->time = $this->createMock(ITimeFactory::class);
		$this->manager = $this->createMock(Manager::class);
		$this->notificationType = new NotificationType();
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->input = $this->getMockBuilder(InputInterface::class)
			->setMethods([
				'getArgument',
				'getOption',
				'getFirstArgument',
				'hasParameterOption',
				'getParameterOption',
				'bind',
				'validate',
				'getArguments',
				'setArgument',
				'hasArgument',
				'getOptions',
				'isInteractive',
				'hasOption',
				'setOption',
				'setInteractive',
			])
			->getMock();
		$this->output = $this->createMock(OutputInterface::class);

		$this->announceCommand = new Announce(
			$this->userManager,
			$this->time,
			$this->manager,
			$this->notificationType,
			$this->logger,
		);
	}

	public function dataCorrect() {
		return [
			// user | subject | message | group | acitivites | notifications | emails | comments | scheduleTime | deleteTime
			['nextcloud', 'TestSubject', 'TestMessage', ['everyone'], false, false, false, false, null, null],  // no notification type
			['nextcloud', 'TestSubject', 'TestMessage', ['everyone'], true, false, false, false, null, null],
			['nextcloud', 'TestSubject', 'TestMessage', ['everyone'], false, true, false, false, null, null],
			['nextcloud', 'TestSubject', 'TestMessage', ['everyone'], false, false, true, false, null, null],
			['nextcloud', 'TestSubject', 'TestMessage', null, false, false, true, false, null, null],
			['nextcloud', 'TestSubject', 'TestMessage', ['everyone'], false, false, true, false, 11, null],
			['nextcloud', 'TestSubject', 'TestMessage', ['everyone'], false, false, true, false, 'tomorrow', null],
			['nextcloud', 'TestSubject', 'TestMessage', ['everyone'], false, false, true, false, 11, 12],
			['nextcloud', 'TestSubject', 'TestMessage', ['everyone'], false, false, true, false, null, 11],
			['nextcloud', 'TestSubject', 'TestMessage', ['group1', 'group2'], false, false, true, false, null, null],
		];
	}

	public function dataException() {
		return [
			// user | subject | message | group | acitivites | notifications | emails | comments | scheduleTime | deleteTime
			['invalid', 'TestSubject', 'TestMessage', ['everyone'], false, false, false, false, null, null],  // invalid user
			['nextcloud', 'TestSubject', 'TestMessage', ['everyone'], true, false, false, false, 0, null],  // scheduled in past
			['nextcloud', 'TestSubject', 'TestMessage', ['everyone'], true, false, false, false, null, 0],  // scheduled in past
			['nextcloud', 'TestSubject', 'TestMessage', ['everyone'], true, false, false, false, 12, 11],  // deletion before publ.
			['nextcloud', 'TestSubject', 'TestMessage', ['everyone'], true, false, false, false, null, 'at christmas eve'],  // invalid time
		];
	}

	public function setupInput($user, $subject, $message, $group, $activites, $notifications, $emails, $comments, $scheduleTime, $deleteTime) {
		$argumentCallback = function (string $property) use ($user, $subject, $message) {
			switch ($property) {
				case 'user':
					return $user;
				case 'message':
					return $message;
				case 'subject':
					return $subject;
				default:
					throw new \InvalidArgumentException('Unknown property ' . $property);
			}
		};

		$optionCallback = function (string $property) use ($activites, $notifications, $emails, $comments, $scheduleTime, $deleteTime, $group) {
			switch ($property) {
				case 'activities':
					return $activites;
				case 'notifications':
					return $notifications;
				case 'emails':
					return $emails;
				case 'comments':
					return $comments;
				case 'schedule-time':
					return $scheduleTime;
				case 'delete-time':
					return $deleteTime;
				case 'group':
					return is_null($group) ? ['everyone'] : $group;
				default:
					throw new \InvalidArgumentException('Unknown property ' . $property);
			}
		};
		$this->input->expects($this->atLeastOnce())
			->method('getArgument')
			->willReturnCallback($argumentCallback);
		$this->input->expects($this->any())
			->method('getOption')
			->willReturnCallback($optionCallback);
	}

	/**
	 * @dataProvider dataCorrect
	 */
	public function testExecuteSuccessfully($user, $subject, $message, $group, $activites, $notifications, $emails, $comments, $scheduleTime, $deleteTime) {
		$this->setupInput($user, $subject, $message, $group, $activites, $notifications, $emails, $comments, $scheduleTime, $deleteTime);
		$this->userManager->expects($this->once())
			->method('userExists')
			->willReturn($user !== 'invalid');
		$this->time->expects($this->any())
			->method('getTime')
			->willReturn(10);
		$this->manager->expects($this->once())
			->method('announce');
		$this->output->expects($this->atLeastOnce())
			->method('writeln');
		$result = self::invokePrivate($this->announceCommand, 'execute', [$this->input, $this->output]);
		self::assertEquals(0, $result);
	}

	/**
	 * @dataProvider dataException
	 */
	public function testExecuteException($user, $subject, $message, $group, $activites, $notifications, $emails, $comments, $scheduleTime, $deleteTime) {
		$this->setupInput($user, $subject, $message, $group, $activites, $notifications, $emails, $comments, $scheduleTime, $deleteTime);
		$this->userManager->expects($this->once())
			->method('userExists')
			->willReturn($user !== 'invalid');
		$this->time->expects($this->any())
			->method('getTime')
			->willReturn(10);

		$result = self::invokePrivate($this->announceCommand, 'execute', [$this->input, $this->output]);
		self::assertequals(true, $result > 0);
	}
}
