<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\AnnouncementCenter\Tests\Command;

use OCA\AnnouncementCenter\Command\RemoveNotifications;
use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Model\AnnouncementDoesNotExistException;
use OCA\AnnouncementCenter\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveNotificationsTest extends TestCase {
	protected Manager&MockObject $manager;
	protected LoggerInterface&MockObject $logger;
	protected InputInterface&MockObject $input;
	protected OutputInterface&MockObject $output;
	protected Command $removeNotificationsCommand;

	protected function setUp(): void {
		parent::setUp();

		$this->manager = $this->createMock(Manager::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->input = $this->getMockBuilder(InputInterface::class)
			->onlyMethods([
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

		$this->removeNotificationsCommand = new RemoveNotifications(
			$this->manager,
			$this->logger,
		);
	}

	public function testRemoveNotificationsSuccessfully(): void {
		$this->input->expects($this->once())
			->method('getArgument')
			->with('id')
			->willReturn(42);
		$this->manager->expects($this->once())
			->method('removeNotifications');
		$this->manager->expects($this->once())
			->method('getAnnouncement');
		$this->output->expects($this->atLeastOnce())
			->method('writeln');
		$this->logger->expects($this->atLeastOnce())
			->method('info');
		$result = self::invokePrivate($this->removeNotificationsCommand, 'execute', [$this->input, $this->output]);
		self::assertEquals(0, $result);
	}

	public function testRemoveNotificationsInvalidId(): void {
		$this->input->expects($this->once())
			->method('getArgument')
			->with('id')
			->willReturn('invalid');
		$result = self::invokePrivate($this->removeNotificationsCommand, 'execute', [$this->input, $this->output]);
		self::assertEquals(true, $result > 0);
	}

	public function testRemoveNotificationsDoesNotExist(): void {
		$this->input->expects($this->once())
			->method('getArgument')
			->with('id')
			->willReturn(42);
		$this->manager->expects($this->once())
			->method('getAnnouncement')
			->willThrowException(new AnnouncementDoesNotExistException('message'));
		$this->output->expects($this->atLeastOnce())
			->method('writeln');
		$result = self::invokePrivate($this->removeNotificationsCommand, 'execute', [$this->input, $this->output]);
		self::assertEquals(true, $result > 0);
	}
}
